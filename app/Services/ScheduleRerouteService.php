<?php

namespace App\Services;

use App\Support\LeadConfirmation;
use App\Support\PackagingDate;
use App\Support\StagePlanHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Tịnh tuyến lịch lý thuyết theo xác nhận hoàn thành.
 *
 * Khi một lô được xác nhận hoàn thành, giờ kết thúc thực tế thường lệch so với lý thuyết.
 * Service này dịch chuyển (2 chiều) các lô chịu ảnh hưởng, giữ nguyên thứ tự lô:
 *   - lô kế tiếp trên CÙNG PHÒNG (cùng hoặc khác sản phẩm),
 *   - công đoạn kế tiếp theo liên kết predecessor_code -> code,
 * rồi lan truyền tiếp cho tới khi khoảng trống trên lịch hấp thụ hết độ lệch.
 *
 * Quy tắc cho từng lô bị ảnh hưởng:
 *   earliest = max(kết thúc mới của các lô đứng trước trên phòng, kết thúc mới của công đoạn trước)
 *   shiftIn  = max(độ dịch của các lô đứng liền trước mà lô này đang bám sát)
 *   newStart = max(earliest, startGốc + min(0, shiftIn))
 * => trễ thì khoảng trống hấp thụ độ trễ; sớm thì chỉ kéo lùi đúng lượng lô trước sớm lên,
 *    khoảng trống cố ý vẫn giữ. Không kéo sớm hơn hiện tại và ngày có NL/BB.
 *
 * Mọi thay đổi được ghi vào stage_plan_reroute_log (kèm giờ cũ để hoàn tác).
 */
class ScheduleRerouteService
{
    /** Chỉ xét các lô bắt đầu trong khoảng này sau lô gốc. */
    public const WINDOW_DAYS = 30;

    /**
     * Lô được coi là "bám sát" lô trước nếu khoảng hở không quá ngưỡng này.
     * Lịch được đặt theo lưới 15 phút nên khoảng hở nhỏ thường chỉ là làm tròn, không phải khoảng trống cố ý.
     */
    public const TIGHT_TOLERANCE_SECONDS = 1800;

    /** Lệch dưới ngưỡng này thì bỏ qua. */
    public const MIN_SHIFT_SECONDS = 60;

    /** Công đoạn Cân / Cấp phát không tạo ràng buộc (giống predecessorReadyTimes). */
    public const SKIP_STAGES = [1, 2];

    /** Lịch bảo trì - hiệu chuẩn: cố định, không dịch. */
    public const MAINTENANCE_STAGE = 8;

    public const TYPE_OF_CHANGE = 'Tịnh tuyến theo hoàn thành';

    public const TYPE_OF_CHANGE_UNDO = 'Hoàn tác tịnh tuyến';

    /** Tính năng thử nghiệm: chỉ các user id này được thấy và bật công tắc tịnh tuyến. */
    public const ALLOWED_USER_IDS = [1];

    public static function canUse(): bool
    {
        return in_array((int) (session('user')['userId'] ?? 0), self::ALLOWED_USER_IDS, true);
    }

    /** @var array<int, array> */
    private array $nodes = [];

    /** @var array<int, array<int, int>> resourceId => danh sách id theo thứ tự trên phòng */
    private array $roomOrder = [];

    /** @var array<int, int> id => vị trí trong roomOrder */
    private array $roomIndex = [];

    /** @var array<string, array<int, int>> code => id các lô có predecessor_code = code */
    private array $successorsByCode = [];

    /** @var array<string, int> code => id */
    private array $idByCode = [];

    /** @var array<int, array{0: int, 1: int}> các khoảng ngày nghỉ [start, end) dạng timestamp */
    private array $offRanges = [];

    /**
     * Tịnh tuyến lịch sau khi lô $stagePlanId được xác nhận hoàn thành.
     *
     * @return array{run_code: ?string, delta_minutes: int, changes: array}
     */
    public function reroute(int $stagePlanId): array
    {
        $empty = ['run_code' => null, 'delta_minutes' => 0, 'changes' => []];

        $source = DB::table('stage_plan')->where('id', $stagePlanId)->first();

        if (! $source || (int) $source->finished !== 1 || ! $source->start || ! $source->end) {
            return $empty;
        }

        $sourceStage = (int) $source->stage_code;
        if (in_array($sourceStage, self::SKIP_STAGES, true) || $sourceStage === self::MAINTENANCE_STAGE) {
            return $empty;
        }

        $actualFinishRaw = $source->actual_end_clearning ?? $source->actual_end;
        if (! $actualFinishRaw) {
            return $empty;
        }

        $theoryFinish = Carbon::parse($source->end_clearning ?? $source->end)->getTimestamp();
        $actualFinish = Carbon::parse($actualFinishRaw)->getTimestamp();
        $delta = $actualFinish - $theoryFinish;

        if (abs($delta) < self::MIN_SHIFT_SECONDS) {
            return $empty;
        }

        $windowStart = $this->at(min(
            Carbon::parse($source->start)->getTimestamp(),
            $source->actual_start ? Carbon::parse($source->actual_start)->getTimestamp() : PHP_INT_MAX
        ))->subDay();
        $windowEnd = $this->at(max($theoryFinish, $actualFinish))->addDays(self::WINDOW_DAYS);

        $this->loadGraph($windowStart, $windowEnd);
        $this->loadOffRanges($windowStart, $windowEnd->copy()->addDays(self::WINDOW_DAYS));

        if (! isset($this->nodes[$source->id])) {
            return $empty;
        }

        // Lô gốc: thời điểm kết thúc lý thuyết -> thực tế
        $this->nodes[$source->id]['origFinish'] = $theoryFinish;
        $this->nodes[$source->id]['curFinish'] = $actualFinish;

        $changes = $this->propagate($source->id);

        if (empty($changes)) {
            return ['run_code' => null, 'delta_minutes' => intdiv($delta, 60), 'changes' => []];
        }

        $runCode = (string) Str::uuid();
        $this->apply($runCode, $source, intdiv($delta, 60), $changes);

        return ['run_code' => $runCode, 'delta_minutes' => intdiv($delta, 60), 'changes' => array_values($changes)];
    }

    /**
     * Hoàn tác một lần tịnh tuyến. Chỉ khôi phục lô chưa chạy và chưa bị đổi lịch lần nữa.
     *
     * @return array{restored: int, skipped: int}
     */
    public function undo(string $runCode): array
    {
        $logs = DB::table('stage_plan_reroute_log')
            ->where('run_code', $runCode)
            ->whereNull('undone_at')
            ->get();

        if ($logs->isEmpty()) {
            return ['restored' => 0, 'skipped' => 0];
        }

        $offDays = DB::table('off_days')->pluck('off_date')->map(fn($d) => Carbon::parse($d)->toDateString())->all();
        $user = session('user')['fullName'] ?? 'System';

        $restored = 0;
        $skipped = 0;

        DB::transaction(function () use ($logs, $offDays, $user, $runCode, &$restored, &$skipped) {
            $restoredIds = [];

            foreach ($logs as $log) {
                $row = DB::table('stage_plan')->where('id', $log->stage_plan_id)->first();

                $unchanged = $row
                    && (int) $row->finished === 0
                    && ! $row->actual_start
                    && $this->sameTime($row->start, $log->new_start)
                    && $this->sameTime($row->end, $log->new_end);

                if (! $unchanged) {
                    $skipped++;

                    continue;
                }

                $this->writeTimes($row, [
                    'start' => $log->old_start,
                    'end' => $log->old_end,
                    'start_clearning' => $log->old_start_clearning,
                    'end_clearning' => $log->old_end_clearning,
                ], $offDays, $user, self::TYPE_OF_CHANGE_UNDO);

                $restoredIds[] = $row->id;
                $restored++;
            }

            LeadConfirmation::reset($restoredIds);

            DB::table('stage_plan_reroute_log')
                ->where('run_code', $runCode)
                ->whereNull('undone_at')
                ->update(['undone_at' => now(), 'undone_by' => $user]);
        });

        return ['restored' => $restored, 'skipped' => $skipped];
    }

    // =====================================================================
    // Dựng đồ thị
    // =====================================================================

    private function loadGraph(Carbon $windowStart, Carbon $windowEnd): void
    {
        $this->nodes = [];
        $this->roomOrder = [];
        $this->roomIndex = [];
        $this->successorsByCode = [];
        $this->idByCode = [];

        $rows = DB::table('stage_plan as sp')
            ->leftJoin('plan_master as pm', 'pm.id', '=', 'sp.plan_master_id')
            ->where('sp.active', 1)
            ->whereNotIn('sp.stage_code', self::SKIP_STAGES)
            ->where(function ($q) use ($windowStart, $windowEnd) {
                $q->whereBetween('sp.start', [$windowStart, $windowEnd])
                    ->orWhereBetween('sp.actual_start', [$windowStart, $windowEnd]);
            })
            ->select(
                'sp.id',
                'sp.code',
                'sp.predecessor_code',
                'sp.stage_code',
                'sp.resourceId',
                'sp.title',
                'sp.finished',
                'sp.start',
                'sp.end',
                'sp.start_clearning',
                'sp.end_clearning',
                'sp.actual_start',
                'sp.actual_end',
                'sp.actual_start_clearning',
                'sp.actual_end_clearning',
                'pm.after_weigth_date',
                'pm.allow_weight_before_date',
                'pm.after_parkaging_date'
            )
            ->get();

        foreach ($rows as $r) {
            $stage = (int) $r->stage_code;

            $movable = (int) $r->finished === 0
                && ! $r->actual_start
                && $stage !== self::MAINTENANCE_STAGE
                && $r->start && $r->end;

            $ts = fn($v) => $v ? Carbon::parse($v)->getTimestamp() : null;

            if ($movable) {
                $start = $ts($r->start);
                $end = $ts($r->end);
                $cStart = $ts($r->start_clearning);
                $cEnd = $ts($r->end_clearning);
                $finish = $cEnd ?? $end;
            } else {
                // Lô cố định: ưu tiên giờ thực tế
                $start = $ts($r->actual_start) ?? $ts($r->start);
                $end = $ts($r->actual_end) ?? $ts($r->end);
                $cStart = $ts($r->actual_start_clearning) ?? $ts($r->start_clearning);
                $cEnd = $ts($r->actual_end_clearning) ?? $ts($r->end_clearning);
                $finish = $ts($r->actual_end_clearning) ?? $ts($r->actual_end) ?? $cEnd ?? $end;
            }

            if ($start === null || $finish === null) {
                continue;
            }

            // Ngày có NL/BB: chặn dưới khi kéo sớm (cùng quy ước với criticalChecks)
            $material = null;
            if ($stage <= 3) {
                foreach ([$r->after_weigth_date, $r->allow_weight_before_date] as $d) {
                    if ($d) {
                        $material = max($material ?? 0, Carbon::parse($d)->startOfDay()->getTimestamp());
                    }
                }
            }
            if ($stage === 7 && $r->after_parkaging_date) {
                $material = max($material ?? 0, Carbon::parse($r->after_parkaging_date)->startOfDay()->getTimestamp());
            }

            $this->nodes[$r->id] = [
                'id' => (int) $r->id,
                'code' => $r->code,
                'pred_code' => $r->predecessor_code,
                'stage' => $stage,
                'room' => $r->resourceId ? (int) $r->resourceId : null,
                'title' => $r->title,
                'movable' => $movable,
                'material' => $material,

                'origStart' => $start,
                'origEnd' => $end,
                'origCleanStart' => $cStart,
                'origCleanEnd' => $cEnd,
                'origFinish' => $finish,

                'curStart' => $start,
                'curEnd' => $end,
                'curCleanStart' => $cStart,
                'curCleanEnd' => $cEnd,
                'curFinish' => $finish,

                // thời lượng làm việc (đã trừ ngày nghỉ) - tính sau khi có offRanges
                'mainWork' => null,
                'cleanGap' => ($cStart !== null && $end !== null) ? max(0, $cStart - $end) : null,
                'cleanWork' => null,
            ];

            if ($r->code) {
                $this->idByCode[(string) $r->code] = (int) $r->id;
            }
            if ($r->predecessor_code) {
                $this->successorsByCode[(string) $r->predecessor_code][] = (int) $r->id;
            }
        }

        // Thứ tự trên phòng theo giờ bắt đầu gốc
        foreach ($this->nodes as $id => $n) {
            if ($n['room'] !== null) {
                $this->roomOrder[$n['room']][] = $id;
            }
        }
        foreach ($this->roomOrder as $room => $ids) {
            usort($ids, fn($a, $b) => [$this->nodes[$a]['origStart'], $a] <=> [$this->nodes[$b]['origStart'], $b]);
            $this->roomOrder[$room] = $ids;
            foreach ($ids as $i => $id) {
                $this->roomIndex[$id] = $i;
            }
        }
    }

    private function loadOffRanges(Carbon $from, Carbon $to): void
    {
        $this->offRanges = DB::table('off_days')
            ->whereBetween('off_date', [$from->copy()->subDay()->toDateString(), $to->toDateString()])
            ->orderBy('off_date')
            ->pluck('off_date')
            ->map(function ($d) {
                // Cùng quy ước với frontend: ngày nghỉ tính từ 06:00 tới 06:00 hôm sau
                $start = Carbon::parse(Carbon::parse($d)->toDateString() . ' 06:00:00')->getTimestamp();

                return [$start, $start + 86400];
            })
            ->all();

        foreach ($this->nodes as $id => $n) {
            if (! $n['movable']) {
                continue;
            }
            $this->nodes[$id]['mainWork'] = $this->workSeconds($n['origStart'], $n['origEnd']);
            if ($n['origCleanStart'] !== null && $n['origCleanEnd'] !== null) {
                $this->nodes[$id]['cleanWork'] = $this->workSeconds($n['origCleanStart'], $n['origCleanEnd']);
            }
        }
    }

    // =====================================================================
    // Lan truyền
    // =====================================================================

    /**
     * @return array<int, array> id => thông tin thay đổi
     */
    private function propagate(int $sourceId): array
    {
        $now = now()->getTimestamp();
        $queue = [];
        $cause = [];

        foreach ($this->successorsOf($sourceId) as $succ) {
            $queue[$succ] = true;
        }

        $guard = 0;
        $maxIterations = max(1000, count($this->nodes) * 20);

        while (! empty($queue) && $guard++ < $maxIterations) {
            // Luôn xử lý lô có giờ bắt đầu gốc sớm nhất trước (gần đúng thứ tự topo)
            $id = null;
            foreach (array_keys($queue) as $candidate) {
                if ($id === null || $this->nodes[$candidate]['origStart'] < $this->nodes[$id]['origStart']) {
                    $id = $candidate;
                }
            }
            unset($queue[$id]);

            $n = $this->nodes[$id];
            if (! $n['movable']) {
                continue;
            }

            [$newStart, $reasonId, $reasonType] = $this->computeStart($id, $now);

            $placed = $this->place($id, $newStart);
            $placed = $this->avoidFixedBlocks($id, $placed);

            if (abs($placed['start'] - $n['curStart']) < self::MIN_SHIFT_SECONDS
                && abs($placed['finish'] - $n['curFinish']) < self::MIN_SHIFT_SECONDS) {
                continue;
            }

            $this->nodes[$id]['curStart'] = $placed['start'];
            $this->nodes[$id]['curEnd'] = $placed['end'];
            $this->nodes[$id]['curCleanStart'] = $placed['cleanStart'];
            $this->nodes[$id]['curCleanEnd'] = $placed['cleanEnd'];
            $this->nodes[$id]['curFinish'] = $placed['finish'];
            $cause[$id] = [$reasonId, $reasonType];

            foreach ($this->successorsOf($id) as $succ) {
                $queue[$succ] = true;
            }
        }

        $changes = [];
        foreach ($this->nodes as $id => $n) {
            if (! $n['movable']) {
                continue;
            }
            if (abs($n['curStart'] - $n['origStart']) < self::MIN_SHIFT_SECONDS
                && abs($n['curFinish'] - $n['origFinish']) < self::MIN_SHIFT_SECONDS) {
                continue;
            }

            [$reasonId, $reasonType] = $cause[$id] ?? [null, null];

            $changes[$id] = [
                'id' => $id,
                'title' => $n['title'],
                'room' => $n['room'],
                'old' => [
                    'start' => $this->fmt($n['origStart']),
                    'end' => $this->fmt($n['origEnd']),
                    'start_clearning' => $this->fmt($n['origCleanStart']),
                    'end_clearning' => $this->fmt($n['origCleanEnd']),
                ],
                'new' => [
                    'start' => $this->fmt($n['curStart']),
                    'end' => $this->fmt($n['curEnd']),
                    'start_clearning' => $this->fmt($n['curCleanStart']),
                    'end_clearning' => $this->fmt($n['curCleanEnd']),
                ],
                'shift_minutes' => intdiv($n['curStart'] - $n['origStart'], 60),
                'reason' => $this->reasonText($reasonId, $reasonType),
            ];
        }

        return $changes;
    }

    /**
     * @return array{0: int, 1: ?int, 2: string}  [newStart, id lô ràng buộc, loại ràng buộc]
     */
    private function computeStart(int $id, int $now): array
    {
        $n = $this->nodes[$id];
        $origStart = $n['origStart'];

        $bindingShift = null;
        $bindingId = null;
        $earliest = PHP_INT_MIN;
        $earliestId = null;
        $earliestType = 'room';

        // Ràng buộc cùng phòng: sau MỌI lô đứng trước trên phòng
        if ($n['room'] !== null) {
            $order = $this->roomOrder[$n['room']];
            $idx = $this->roomIndex[$id];

            for ($i = 0; $i < $idx; $i++) {
                $p = $this->nodes[$order[$i]];
                $c = $this->constraintFrom($p, $origStart);
                if ($c > $earliest) {
                    $earliest = $c;
                    $earliestId = $p['id'];
                    $earliestType = 'room';
                }
            }

            if ($idx > 0) {
                $prev = $this->nodes[$order[$idx - 1]];
                $this->considerBinding($prev, $origStart, $bindingShift, $bindingId);
            }
        }

        // Ràng buộc công đoạn trước
        if ($n['pred_code'] && isset($this->idByCode[(string) $n['pred_code']])) {
            $pred = $this->nodes[$this->idByCode[(string) $n['pred_code']]];
            $c = $this->constraintFrom($pred, $origStart);
            if ($c > $earliest) {
                $earliest = $c;
                $earliestId = $pred['id'];
                $earliestType = 'stage';
            }
            $this->considerBinding($pred, $origStart, $bindingShift, $bindingId);
        }

        $candidate = $origStart + min(0, $bindingShift ?? 0);

        if ($earliest >= $candidate) {
            $newStart = $earliest;
            $reasonId = $earliestId;
            $reasonType = $earliestType;
        } else {
            $newStart = $candidate;
            $reasonId = $bindingId;
            $reasonType = ($bindingId !== null && $this->nodes[$bindingId]['room'] === $n['room']) ? 'room' : 'stage';
        }

        // Kéo sớm: không sớm hơn hiện tại, không sớm hơn ngày có NL/BB
        if ($newStart < $origStart) {
            $lower = min($origStart, max($now, $n['material'] ?? 0));
            if ($newStart < $lower) {
                $newStart = $lower;
            }
        }

        return [$newStart, $reasonId, $reasonType];
    }

    /**
     * Mốc sớm nhất lô trước $pred cho phép lô này bắt đầu.
     *
     * Lịch lý thuyết hiện có có thể đã chồng giờ sẵn (slack âm). Tịnh tuyến không có nhiệm vụ
     * sửa chồng lấn cũ, chỉ không được làm nó tệ hơn: giữ nguyên lượng chồng lấn gốc.
     * Lô trước không đổi thì mốc này luôn <= giờ bắt đầu gốc, nên không tự đẩy lô nào.
     */
    private function constraintFrom(array $pred, int $origStart): int
    {
        $slack = $origStart - $pred['origFinish'];

        return $pred['curFinish'] + min(0, $slack);
    }

    /**
     * Lô trước được coi là ràng buộc (lô này đang bám sát nó) thì lấy độ dịch của nó.
     */
    private function considerBinding(array $pred, int $origStart, ?int &$bindingShift, ?int &$bindingId): void
    {
        if ($pred['origFinish'] < $origStart - self::TIGHT_TOLERANCE_SECONDS) {
            return;
        }

        $shift = $pred['curFinish'] - $pred['origFinish'];

        if ($bindingShift === null || $shift > $bindingShift) {
            $bindingShift = $shift;
            $bindingId = $pred['id'];
        }
    }

    /**
     * @return array<int, int>
     */
    private function successorsOf(int $id): array
    {
        $n = $this->nodes[$id];
        $result = [];

        if ($n['room'] !== null) {
            $order = $this->roomOrder[$n['room']];
            // Lô kế tiếp trên phòng; nếu lô kế là lô cố định thì đi tiếp tới lô dịch được đầu tiên
            for ($i = $this->roomIndex[$id] + 1; $i < count($order); $i++) {
                $result[] = $order[$i];
                if ($this->nodes[$order[$i]]['movable']) {
                    break;
                }
            }
        }

        if ($n['code'] && isset($this->successorsByCode[(string) $n['code']])) {
            foreach ($this->successorsByCode[(string) $n['code']] as $succ) {
                $result[] = $succ;
            }
        }

        return array_unique($result);
    }

    // =====================================================================
    // Đặt lô vào lịch
    // =====================================================================

    /**
     * Đặt lô bắt đầu từ $start, giữ nguyên thời lượng làm việc, bỏ qua ngày nghỉ.
     *
     * @return array{start: int, end: int, cleanStart: ?int, cleanEnd: ?int, finish: int}
     */
    private function place(int $id, int $start): array
    {
        $n = $this->nodes[$id];

        // Không dịch thì giữ nguyên giờ gốc (kể cả khi lịch gốc cố ý đặt trên ngày nghỉ)
        if (abs($start - $n['origStart']) < self::MIN_SHIFT_SECONDS) {
            return [
                'start' => $n['origStart'],
                'end' => $n['origEnd'],
                'cleanStart' => $n['origCleanStart'],
                'cleanEnd' => $n['origCleanEnd'],
                'finish' => $n['origFinish'],
            ];
        }

        [$s, $e] = $this->placeForward($start, $n['mainWork'] ?? ($n['origEnd'] - $n['origStart']));

        $cs = null;
        $ce = null;
        if ($n['cleanWork'] !== null) {
            [$cs, $ce] = $this->placeForward($e + ($n['cleanGap'] ?? 0), $n['cleanWork']);
        }

        return ['start' => $s, 'end' => $e, 'cleanStart' => $cs, 'cleanEnd' => $ce, 'finish' => $ce ?? $e];
    }

    /**
     * Nếu lô bị dịch chồng lên lô cố định (bảo trì, lô đang chạy / đã xong) trên cùng phòng,
     * đẩy lô qua sau lô cố định đó.
     */
    private function avoidFixedBlocks(int $id, array $placed): array
    {
        $n = $this->nodes[$id];
        if ($n['room'] === null) {
            return $placed;
        }

        for ($guard = 0; $guard < 50; $guard++) {
            $conflictEnd = null;

            foreach ($this->roomOrder[$n['room']] as $otherId) {
                if ($otherId === $id) {
                    continue;
                }
                $o = $this->nodes[$otherId];
                if ($o['movable']) {
                    continue;
                }
                // Chồng lấn đã có sẵn trong lịch gốc thì bỏ qua, không phải do tịnh tuyến gây ra
                if ($o['origStart'] < $n['origFinish'] && $o['origFinish'] > $n['origStart']) {
                    continue;
                }
                if ($o['curStart'] < $placed['finish'] && $o['curFinish'] > $placed['start']) {
                    $conflictEnd = max($conflictEnd ?? 0, $o['curFinish']);
                }
            }

            if ($conflictEnd === null) {
                return $placed;
            }

            $placed = $this->place($id, $conflictEnd);
        }

        return $placed;
    }

    private function workSeconds(int $start, int $end): int
    {
        $total = max(0, $end - $start);

        foreach ($this->offRanges as [$os, $oe]) {
            $overlap = min($end, $oe) - max($start, $os);
            if ($overlap > 0) {
                $total -= $overlap;
            }
        }

        return max(0, $total);
    }

    /**
     * @return array{0: int, 1: int}  [start thực, end]
     */
    private function placeForward(int $start, int $work): array
    {
        $cursor = $start;

        // Nếu điểm bắt đầu rơi vào ngày nghỉ thì dời tới hết ngày nghỉ
        foreach ($this->offRanges as [$os, $oe]) {
            if ($cursor >= $os && $cursor < $oe) {
                $cursor = $oe;
            }
        }
        $realStart = $cursor;
        $remain = $work;

        foreach ($this->offRanges as [$os, $oe]) {
            if ($oe <= $cursor) {
                continue;
            }
            if ($os >= $cursor + $remain) {
                break;
            }
            $remain -= max(0, $os - $cursor);
            $cursor = $oe;
        }

        return [$realStart, $cursor + $remain];
    }

    // =====================================================================
    // Ghi DB
    // =====================================================================

    private function apply(string $runCode, object $source, int $deltaMinutes, array $changes): void
    {
        $offDays = DB::table('off_days')->pluck('off_date')->map(fn($d) => Carbon::parse($d)->toDateString())->all();
        $user = session('user')['fullName'] ?? 'System';
        $department = session('user.production_code') ?? $source->deparment_code;

        DB::transaction(function () use ($runCode, $source, $deltaMinutes, $changes, $offDays, $user, $department) {
            $logs = [];

            foreach ($changes as $id => $c) {
                $row = DB::table('stage_plan')->where('id', $id)->first();
                if (! $row) {
                    continue;
                }

                $this->writeTimes($row, $c['new'], $offDays, $user, self::TYPE_OF_CHANGE);

                $logs[] = [
                    'run_code' => $runCode,
                    'source_stage_plan_id' => $source->id,
                    'source_title' => $source->title,
                    'source_delta_minutes' => $deltaMinutes,
                    'stage_plan_id' => $id,
                    'old_start' => $c['old']['start'],
                    'old_end' => $c['old']['end'],
                    'old_start_clearning' => $c['old']['start_clearning'],
                    'old_end_clearning' => $c['old']['end_clearning'],
                    'new_start' => $c['new']['start'],
                    'new_end' => $c['new']['end'],
                    'new_start_clearning' => $c['new']['start_clearning'],
                    'new_end_clearning' => $c['new']['end_clearning'],
                    'shift_minutes' => $c['shift_minutes'],
                    'reason' => Str::limit($c['reason'], 500, '...'),
                    'deparment_code' => $department,
                    'created_by' => $user,
                    'created_at' => now(),
                ];
            }

            LeadConfirmation::reset(array_keys($changes));

            foreach (array_chunk($logs, 200) as $chunk) {
                DB::table('stage_plan_reroute_log')->insert($chunk);
            }
        });
    }

    /**
     * Ghi giờ mới cho một lô, kèm các hiệu ứng phụ giống SchedualController::update():
     * ngày nhận bao bì (ĐG), lịch sử phiên bản khi lịch đã submit, reset cờ submit.
     */
    private function writeTimes(object $row, array $times, array $offDays, string $user, string $typeOfChange): void
    {
        $update = [
            'start' => $times['start'],
            'end' => $times['end'],
            'schedualed_by' => $user,
            'schedualed_at' => now(),
            'accept_quarantine' => 0,
        ];

        if ($row->start_clearning !== null || $times['start_clearning'] !== null) {
            $update['start_clearning'] = $times['start_clearning'];
            $update['end_clearning'] = $times['end_clearning'];
        }

        $receiveDate = null;
        if ((int) $row->stage_code === 7 && $times['start']) {
            $receiveDate = PackagingDate::receiveDateFor($times['start'], $offDays);
            $update['receive_packaging_date'] = DB::raw("CASE WHEN received = 0 THEN '$receiveDate' ELSE receive_packaging_date END");
            $update['receive_second_packaging_date'] = DB::raw("CASE WHEN received_second_packaging = 0 THEN '$receiveDate' ELSE receive_second_packaging_date END");
        }

        DB::table('stage_plan')->where('id', $row->id)->update($update);

        if ((int) $row->submit === 1) {
            if ($receiveDate) {
                PackagingDate::sync($row->id, $receiveDate, 0, 'ScheduleRerouteService');
                PackagingDate::sync($row->id, $receiveDate, 1, 'ScheduleRerouteService');
            }

            $updated = DB::table('stage_plan')->where('id', $row->id)->first();
            StagePlanHistory::record($updated, $typeOfChange);
        }

        DB::table('stage_plan')
            ->where('id', $row->id)
            ->where('stage_code', '!=', self::MAINTENANCE_STAGE)
            ->update(['submit' => 0]);
    }

    // =====================================================================
    // Tiện ích
    // =====================================================================

    private function reasonText(?int $reasonId, ?string $reasonType): string
    {
        if ($reasonId === null || ! isset($this->nodes[$reasonId])) {
            return 'Dịch theo lô đứng trước';
        }

        $title = $this->nodes[$reasonId]['title'] ?? ('#' . $reasonId);
        $label = $reasonType === 'stage' ? 'công đoạn trước' : 'cùng phòng';

        return "Theo lô \"{$title}\" ({$label})";
    }

    private function fmt(?int $ts): ?string
    {
        return $ts === null ? null : $this->at($ts)->format('Y-m-d H:i:s');
    }

    /** Carbon 3 mặc định tạo từ timestamp theo UTC, phải truyền múi giờ ứng dụng. */
    private function at(int $ts): Carbon
    {
        return Carbon::createFromTimestamp($ts, date_default_timezone_get());
    }

    private function sameTime($a, $b): bool
    {
        if ($a === null || $b === null) {
            return $a === $b;
        }

        return abs(Carbon::parse($a)->getTimestamp() - Carbon::parse($b)->getTimestamp()) < 60;
    }
}
