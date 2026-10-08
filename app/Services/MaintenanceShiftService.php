<?php

namespace App\Services;

use App\Support\StagePlanHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Dời lịch BT-HC-TI (stage_code = 8) bị lịch sản xuất đè lên.
 *
 * Khi sắp lịch tự động, chiến dịch được xếp xuyên qua các lịch BT-HC-TI chưa bắt đầu để không bị cắt
 * khoảng (SchedualController::$campaignSkipsMaintenance, plugin Kiểm soát tồn BTP). Sau đó mỗi lịch
 * BT-HC-TI còn đè lên lô sản xuất trong cùng phòng được dời ra khe trống sớm nhất từ giờ cũ trở đi
 * (kể cả ngày nghỉ: bảo trì vốn hay xếp vào ngày nghỉ khi phòng không chạy). Khe đó làm lịch trễ hạn BT
 * (quy tắc như viền đỏ trên Gantt, xem dueLimit) thì lấy khe trống trước giờ cũ, gần giờ cũ nhất, mà vẫn
 * kịp hạn; không có khe nào kịp thì vẫn dời ra sau và báo trễ. Lịch từng bị bước này dời mà đang trễ hạn
 * (không bị đè nữa) cũng được kéo về khe sớm hơn còn kịp hạn; lịch bảo trì tự xếp trễ thì không động tới.
 * Các dòng cùng phòng, cùng giờ (nhiều thiết bị một lịch) dời cùng nhau, giữ nguyên độ dài và vệ sinh.
 */
class MaintenanceShiftService
{
    // Đầu chuỗi type_of_change khi ghi lịch sử; người gọi nối thêm nguồn (sắp lịch tự động / kiểm soát tồn BTP)
    public const HISTORY_PREFIX = 'Dời BT-HC-TI sau lịch sản xuất';
    /**
     * @return array<int, array{room: string, title: string, from: string, to: string, rows: int, due: ?string, late: bool}> lịch đã dời
     */
    public function shiftOverlapping(string $production, string $typeOfChange): array
    {
        $now = now()->format('Y-m-d H:i:s');

        $maint = DB::table('stage_plan as sp')
            ->leftJoin('plan_master as pm', 'pm.id', '=', 'sp.plan_master_id')
            ->where('sp.deparment_code', $production)
            ->where('sp.stage_code', 8)
            ->where('sp.active', 1)
            ->where('sp.finished', 0)
            ->whereNull('sp.actual_start')
            ->whereNotNull('sp.start')
            ->whereNotNull('sp.resourceId')
            ->where('sp.start', '>=', $now)
            ->orderBy('sp.start')
            ->get(['sp.id', 'sp.resourceId', 'sp.start', 'sp.end', 'sp.start_clearning', 'sp.end_clearning', 'sp.title', 'sp.code',
                'pm.expected_date', 'pm.actual_batch']);
        if ($maint->isEmpty()) {
            return [];
        }

        $roomIds = $maint->pluck('resourceId')->unique()->values()->all();
        $prod = [];
        foreach (DB::table('stage_plan')
            ->whereIn('resourceId', $roomIds)
            ->whereBetween('stage_code', [3, 7])
            ->where('active', 1)
            ->where('finished', 0)
            ->whereNotNull('start')
            ->whereRaw('COALESCE(end_clearning, end) > ?', [$now])
            ->get(['resourceId', 'start', 'end', 'end_clearning']) as $r) {
            $prod[(int) $r->resourceId][] = [strtotime($r->start), strtotime($r->end_clearning ?: $r->end)];
        }
        foreach (app(RoomOccupancyService::class)->heldUntil($roomIds) as $roomId => [$from, $to]) {
            $prod[(int) $roomId][] = [$from->getTimestamp(), $to->getTimestamp()];
        }

        $roomCodes = DB::table('room')->whereIn('id', $roomIds)->pluck('code', 'id');
        // Khe trống sớm nhất được dời sớm về: từ bây giờ, làm tròn lên 15 phút
        $nowTs = (int) ceil(time() / 900) * 900;

        // Dòng từng bị bước này dời (stage_plan_history không có index stage_plan_id: quét một lần)
        $autoShifted = array_flip(DB::table('stage_plan_history')
            ->where('type_of_change', 'like', self::HISTORY_PREFIX . '%')
            ->whereIn('stage_plan_id', $maint->pluck('id')->all())
            ->distinct()->pluck('stage_plan_id')->map(fn($id) => (int) $id)->all());

        $groups = $maint->groupBy(fn($r) => $r->resourceId . '|' . $r->start . '|' . $r->end . '|' . $r->end_clearning);
        $moved = [];
        $updates = [];
        foreach ($groups as $rows) {
            $first = $rows->first();
            $roomId = (int) $first->resourceId;
            $blocks = $prod[$roomId] ?? [];
            $start = strtotime($first->start);
            $len = strtotime($first->end_clearning ?: $first->end) - $start;

            $t = $start;
            for ($k = 0; $k < 500; $k++) {
                $clashEnd = null;
                foreach ($blocks as [$a, $b]) {
                    if ($a < $t + $len && $b > $t) {
                        $clashEnd = max($clashEnd ?? 0, $b);
                    }
                }
                if ($clashEnd === null) {
                    break;
                }
                $t = $clashEnd;
            }

            $limit = $this->dueLimit($first);
            if ($t === $start) {
                // Không bị đè: chỉ kéo về lịch từng bị tự động dời mà đang trễ hạn
                if ($limit === null || date('Y-m-d', $start) <= $limit || ! isset($autoShifted[(int) $first->id])) {
                    continue;
                }
            }

            // Dời ra sau mà trễ hạn BT: lấy khe trống trước giờ cũ, gần giờ cũ nhất, còn kịp hạn
            $late = $limit !== null && date('Y-m-d', $t) > $limit;
            if ($late) {
                $earlier = $this->latestGapBefore($blocks, $start, $len, $nowTs);
                if ($earlier !== null && date('Y-m-d', $earlier) <= $limit) {
                    $t = $earlier;
                    $late = false;
                }
            }

            $delta = $t - $start;
            if ($delta === 0) {
                // Đang trễ hạn mà không có khe trống sớm hơn kịp hạn: giữ nguyên, chỉ báo
                $moved[] = [
                    'room' => (string) ($roomCodes[$roomId] ?? $roomId),
                    'title' => trim(strtok(strip_tags(str_replace('<br/>', "
", (string) $first->title)), "
")),
                    'from' => $first->start,
                    'to' => $first->start,
                    'rows' => $rows->count(),
                    'due' => $limit,
                    'late' => true,
                ];
                continue;
            }

            foreach ($rows as $r) {
                $updates[(int) $r->id] = $delta;
            }
            $moved[] = [
                'room' => (string) ($roomCodes[$roomId] ?? $roomId),
                'title' => trim(strtok(strip_tags(str_replace('<br/>', "\n", (string) $first->title)), "\n")),
                'from' => $first->start,
                'to' => date('Y-m-d H:i:s', $t),
                'rows' => $rows->count(),
                'due' => $limit,
                'late' => $late,
            ];
        }

        if ($updates === []) {
            return $moved;
        }

        $versions = DB::table('stage_plan_history')->whereIn('stage_plan_id', array_keys($updates))
            ->groupBy('stage_plan_id')->selectRaw('stage_plan_id, MAX(version) AS v')->pluck('v', 'stage_plan_id');
        $fmt = fn($value, int $d) => $value ? date('Y-m-d H:i:s', strtotime($value) + $d) : null;
        $user = session('user.fullName') ?? 'System';
        foreach ($maint->whereIn('id', array_keys($updates)) as $r) {
            $d = $updates[(int) $r->id];
            DB::table('stage_plan')->where('id', $r->id)->update([
                'start' => $fmt($r->start, $d),
                'end' => $fmt($r->end, $d),
                'start_clearning' => $fmt($r->start_clearning, $d),
                'end_clearning' => $fmt($r->end_clearning, $d),
                'schedualed_by' => $user,
                'schedualed_at' => now(),
            ]);
            StagePlanHistory::record(DB::table('stage_plan')->where('id', $r->id)->first(), $typeOfChange, (int) ($versions[$r->id] ?? 0) + 1);
        }

        return $moved;
    }

    /**
     * Ngày cuối cùng lịch được bắt đầu mà chưa trễ hạn BT (Y-m-d), như viền đỏ trên Gantt và sắp lịch bảo trì:
     * ngày tới hạn lấy từ tiêu đề ("Ngày tới hạn: dd/mm/yyyy"), không có thì expected_date của kế hoạch;
     * HC không gia hạn, Monthly +7 ngày, loại khác +21 ngày.
     */
    private function dueLimit(object $row): ?string
    {
        $due = null;
        if (preg_match('~Ngày tới hạn:\s*(\d{1,2})/(\d{1,2})/(\d{4})~u', (string) $row->title, $m)) {
            $due = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        } elseif (! empty($row->expected_date)) {
            $due = substr((string) $row->expected_date, 0, 10);
        }
        if ($due === null) {
            return null;
        }

        $grace = str_contains((string) $row->code, '_HC') ? 0 : (($row->actual_batch ?? '') === 'Monthly' ? 7 : 21);

        return date('Y-m-d', strtotime("$due +$grace days"));
    }

    /** Giờ bắt đầu muộn nhất (bội 15 phút, từ $from, trước $before) để lịch dài $len giây nằm trọn trong một khe trống */
    private function latestGapBefore(array $blocks, int $before, int $len, int $from): ?int
    {
        usort($blocks, fn($x, $y) => $x[0] <=> $y[0]);
        $merged = [];
        foreach ($blocks as [$a, $b]) {
            $last = count($merged) - 1;
            if ($last >= 0 && $a <= $merged[$last][1]) {
                $merged[$last][1] = max($merged[$last][1], $b);
            } else {
                $merged[] = [$a, $b];
            }
        }

        // Các khe trống [gs, ge) từ $from tới $before, xét từ khe muộn nhất
        $gaps = [];
        $cursor = $from;
        foreach ($merged as [$a, $b]) {
            if ($a > $cursor) {
                $gaps[] = [$cursor, min($a, $before)];
            }
            $cursor = max($cursor, $b);
            if ($cursor >= $before) {
                break;
            }
        }
        if ($cursor < $before) {
            $gaps[] = [$cursor, $before];
        }

        foreach (array_reverse($gaps) as [$gs, $ge]) {
            $t = intdiv($ge - $len, 900) * 900;
            if ($t >= $gs) {
                return $t;
            }
        }

        return null;
    }
}
