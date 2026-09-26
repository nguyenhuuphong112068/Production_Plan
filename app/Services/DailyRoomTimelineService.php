<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Dòng thời gian trong ngày (06:00 → 06:00) của từng phòng cho Báo cáo ngày.
 *
 * - Bổ sung các khoảng đã có mốc thực tế nhưng chưa nằm trong dữ liệu báo cáo (sản lượng, vệ sinh, hoạt động):
 *   Chuẩn bị (BĐSX → BĐCM đầu tiên của lô) và các trạng thái đang diễn ra ở trang Thực Thi Sản Xuất
 *   (đang chuẩn bị / sản xuất / tạm dừng / vệ sinh — chỉ ghi vào stage_plan, yields, room_status khi kết thúc).
 * - Phần còn lại của ngày (tính tới hiện tại) là thời gian phòng "Không hoạt động". Lý do suy ra theo thứ tự:
 *   trạng thái phòng ở trang Thực Thi Sản Xuất (room_execution_log) → các lô trước / sau của phòng → ngày nghỉ → chưa rõ.
 *   Chỉ tính khi xem báo cáo, không lưu.
 */
class DailyRoomTimelineService
{
    const IDLE_REASONS = [
        'paused'     => 'Tạm dừng giữa 2 lần xác nhận',
        'wait_clean' => 'Chờ vệ sinh',
        'expired'    => 'Phòng quá hạn sạch – chờ vệ sinh lại',
        'clean_idle' => 'Phòng sạch – chờ sản xuất',
        'off_day'    => 'Ngày nghỉ',
        'unknown'    => 'Chưa rõ lý do',
    ];

    // Khoảng ngưng bị nhiều trạng thái phủ (phòng chạy nhiều lô song song): lô đang dở > phòng bẩn > quá hạn sạch > phòng sạch
    const PRIORITY = ['paused' => 1, 'wait_clean' => 2, 'expired' => 3, 'clean_idle' => 4];

    // Xét các lô có mốc thực tế trong khoảng này trước ngày báo cáo (hạn sạch dài nhất 7 ngày)
    const LOOKBACK_DAYS = 20;

    public function __construct(private ProductionExecutionService $execution)
    {
    }

    /**
     * @param  int[]       $roomIds
     * @param  Carbon      $dayStart  06:00 của ngày báo cáo
     * @param  Collection  $detail    actual_detail của DailyReportController::yield_actual_detial (các khoảng đã ghi nhận)
     * @return array<int, object>  theo room id: extra (khoảng ghi nhận bổ sung), idle (khoảng ngưng hoạt động),
     *                             recorded_seconds, idle_seconds, idle_by_reason, until (hết phần đã diễn ra của ngày)
     */
    public function build(array $roomIds, Carbon $dayStart, Collection $detail): array
    {
        $w0 = $dayStart->getTimestamp();
        $w1 = $w0 + 86400;
        $now = now()->getTimestamp();
        $until = min($w1, $now);

        $roomIds = array_values(array_unique(array_filter($roomIds)));
        if (!$roomIds) {
            return [];
        }

        $planRows = $this->plans($roomIds, $dayStart);
        $yields = $planRows->isEmpty() ? collect() : DB::table('yields')
            ->whereIn('stage_plan_id', $planRows->pluck('id')->all())
            ->whereNotNull('start')
            ->whereNotNull('end')
            ->orderBy('start')
            ->get(['stage_plan_id', 'start', 'end'])
            ->groupBy('stage_plan_id');
        // Bỏ lô không có sản xuất thật: không có sản lượng và KT không sau BĐSX (các lô đóng cùng 1 giây)
        $planRows = $planRows->filter(fn($p) => $yields->has($p->id) || $this->ts($p->actual_end) > $this->ts($p->actual_start))->values();
        $plans = $planRows->groupBy('resourceId');
        $logs = DB::table('room_execution_log')
            ->whereIn('room_id', $roomIds)
            ->whereNull('cancelled_at')
            ->where('started_at', '<', Carbon::createFromTimestamp($w1, $dayStart->getTimezone()))
            ->where(fn($q) => $q->whereNull('ended_at')->orWhere('ended_at', '>', $dayStart))
            ->orderBy('started_at')
            ->get()
            ->groupBy('room_id');
        $labels = $this->execution->planDetails(array_merge(
            $planRows->pluck('id')->all(),
            $logs->flatten()->pluck('stage_plan_id')->filter()->all()
        ))->map(fn($p) => $p->label);
        $off = DB::table('off_days')->where('off_date', $dayStart->toDateString())->first();
        $offDay = $off ? (string) $off->reason : null; // ngày nghỉ tính 06:00 → 06:00 như lịch
        $detail = $detail->groupBy('resourceId');

        $result = [];
        foreach ($roomIds as $roomId) {
            $ctx = (object) [
                'w0' => $w0, 'w1' => $w1, 'now' => $now, 'until' => $until,
                'plans' => $plans->get($roomId, collect()),
                'yields' => $yields,
                'logs' => $logs->get($roomId, collect()),
                'labels' => $labels,
            ];

            $extra = $this->extraItems($ctx);
            $known = collect($detail->get($roomId, []))
                ->map(fn($d) => [$this->ts($d->start), $this->ts($d->end)])
                ->concat($extra->map(fn($e) => [$e->from, $e->to]))
                ->all();
            $recorded = $this->merge($this->clamp($known, $w0, $w1));

            $idle = $this->idleSegments($ctx, $this->gaps($recorded, $w0, $until), $offDay);

            $result[$roomId] = (object) [
                'extra'            => $extra->map(fn($e) => $this->toCarbon($e, $dayStart))->values(),
                'idle'             => $idle->map(fn($e) => $this->toCarbon($e, $dayStart))->values(),
                'recorded_seconds' => array_sum(array_map(fn($i) => $i[1] - $i[0], $recorded)),
                'idle_seconds'     => $idle->sum(fn($e) => $e->to - $e->from),
                'idle_by_reason'   => $idle->groupBy('reason')->map(fn($g) => $g->sum(fn($e) => $e->to - $e->from))->sortDesc()->all(),
                'until'            => Carbon::createFromTimestamp($until, $dayStart->getTimezone()),
            ];
        }

        return $result;
    }

    /** Các lô của phòng có mốc thực tế gần ngày báo cáo (để lấy BĐSX, KT, vệ sinh, lô kế tiếp) */
    private function plans(array $roomIds, Carbon $dayStart): Collection
    {
        $from = $dayStart->copy()->subDays(self::LOOKBACK_DAYS);

        return DB::table('stage_plan')
            ->whereIn('resourceId', $roomIds)
            ->where('active', 1)
            ->whereNotNull('actual_start')
            ->where('actual_start', '<', $dayStart->copy()->addDay())
            ->where('actual_start', '>=', $from->copy()->subDays(self::LOOKBACK_DAYS))
            ->whereRaw('COALESCE(actual_end_clearning, actual_end, actual_start) >= ?', [$from])
            ->orderBy('actual_start')
            ->get(['id', 'resourceId', 'title', 'title_clearning', 'actual_start', 'actual_end', 'actual_start_clearning', 'actual_end_clearning']);
    }

    /**
     * Khoảng đã có mốc thực tế nhưng chưa có trong dữ liệu báo cáo:
     * Chuẩn bị (BĐSX → BĐCM đầu tiên) và trạng thái đang diễn ra ở trang Thực Thi Sản Xuất.
     */
    private function extraItems(object $ctx): Collection
    {
        $items = collect();
        $prepDone = [];

        foreach ($ctx->plans as $p) {
            $first = $ctx->yields->get($p->id)?->first();
            $bdsx = $this->ts($p->actual_start);
            if ($first && $this->ts($first->start) > $bdsx) {
                $items->push($this->item('preparing', $bdsx, $this->ts($first->start), $ctx->labels->get($p->id, $p->title), null, false));
                $prepDone[$p->id] = true;
            }
        }

        foreach ($ctx->logs as $log) {
            $from = $this->ts($log->started_at);
            $open = !$log->ended_at;
            $to = $open ? $ctx->now : $this->ts($log->ended_at);
            $label = $log->stage_plan_id ? $ctx->labels->get($log->stage_plan_id) : null;
            $state = (int) $log->state;

            if ($state === ProductionExecutionService::PREPARING && !isset($prepDone[$log->stage_plan_id])) {
                // Lô chưa tạm dừng / kết thúc lần nào nên chưa có BĐSX trong stage_plan
                $items->push($this->item('preparing', $from, $to, $label, $open ? 'Đang chuẩn bị' : null, $open));
            } elseif ($open && $state === ProductionExecutionService::PRODUCING) {
                $items->push($this->item('producing', $from, $to, $label, 'Đang chạy, chưa khai báo sản lượng', true));
            } elseif ($open && $state === ProductionExecutionService::PAUSED) {
                $items->push($this->item('paused', $from, $to, 'Tạm dừng SX', trim(($log->note ?: 'Không ghi lý do') . ($label ? ' - ' . $label : '')), true));
            } elseif ($open && $state === ProductionExecutionService::CLEANING) {
                $items->push($this->item('cleaning', $from, $to, ($log->cleaning_level ?: 'Vệ sinh') . ($label ? ' (' . $label . ')' : ''), 'Đang vệ sinh', true));
            }
        }

        return $items->filter(fn($i) => $i->to > $ctx->w0 && $i->from < $ctx->w1)
            ->map(function ($i) use ($ctx) {
                $i->from = max($i->from, $ctx->w0);
                $i->to = min($i->to, $ctx->w1);
                return $i;
            })
            ->filter(fn($i) => $i->to > $i->from)
            ->values();
    }

    /**
     * Gán lý do cho từng khoảng ngưng: trạng thái ở trang Thực Thi Sản Xuất nếu có, không thì suy từ các lô của phòng.
     * Các đoạn liền nhau cùng lý do + ghi chú được gộp lại.
     */
    private function idleSegments(object $ctx, array $gaps, ?string $offDay): Collection
    {
        if (!$gaps) {
            return collect();
        }

        $fromLogs = $this->logStates($ctx);
        $fromPlans = $this->planStates($ctx);
        $segments = collect();

        foreach ($gaps as [$g0, $g1]) {
            $cuts = [$g0, $g1];
            foreach (array_merge($fromLogs, $fromPlans) as $s) {
                foreach ([$s->from, $s->to] as $c) {
                    if ($c > $g0 && $c < $g1) {
                        $cuts[] = $c;
                    }
                }
            }
            sort($cuts);
            $cuts = array_values(array_unique($cuts));

            for ($i = 0; $i + 1 < count($cuts); $i++) {
                [$a, $b] = [$cuts[$i], $cuts[$i + 1]];
                $state = $this->pick($fromLogs, $a, $b) ?? $this->pick($fromPlans, $a, $b);

                if ($offDay !== null) {
                    $seg = $this->item('off_day', $a, $b, self::IDLE_REASONS['off_day'], $offDay ?: null, false);
                } elseif ($state) {
                    $seg = $this->item($state->reason, $a, $b, self::IDLE_REASONS[$state->reason], $state->note, false);
                } else {
                    $seg = $this->item('unknown', $a, $b, self::IDLE_REASONS['unknown'], null, false);
                }
                $seg->reason = $seg->kind;

                $last = $segments->last();
                if ($last && $last->to === $seg->from && $last->reason === $seg->reason && $last->note === $seg->note) {
                    $last->to = $seg->to;
                } else {
                    $segments->push($seg);
                }
            }
        }

        return $segments;
    }

    /** Trạng thái phòng sạch / cần vệ sinh ghi ở trang Thực Thi Sản Xuất (chính xác, ưu tiên hơn suy luận) */
    private function logStates(object $ctx): array
    {
        $states = [];
        foreach ($ctx->logs as $log) {
            $from = $this->ts($log->started_at);
            $to = $log->ended_at ? $this->ts($log->ended_at) : $ctx->now;
            $label = $log->stage_plan_id ? $ctx->labels->get($log->stage_plan_id) : null;

            switch ((int) $log->state) {
                case ProductionExecutionService::CLEAN:
                    $expired = $log->expired_at ? $this->ts($log->expired_at) : null;
                    $note = 'Sạch từ ' . date('H:i d/m', $from) . ($log->cleaning_level ? ' (' . $log->cleaning_level . ')' : '');
                    $states[] = $this->state('clean_idle', $from, $expired ? min($to, $expired) : $to, $note);
                    if ($expired && $expired < $to) {
                        $states[] = $this->state('expired', $expired, $to, 'Hết hạn sạch lúc ' . date('H:i d/m', $expired));
                    }
                    break;
                case ProductionExecutionService::NEED_CLEAN:
                    $states[] = $this->state('wait_clean', $from, $to, $label ? 'Sau lô ' . $label : ($log->note ?: null));
                    break;
                case ProductionExecutionService::PAUSED:
                    $states[] = $this->state('paused', $from, $to, $label ? 'Lô ' . $label : null);
                    break;
            }
        }

        return $states;
    }

    /** Suy trạng thái từ các lô của phòng: dừng giữa 2 lần xác nhận, chờ vệ sinh, phòng sạch chờ sản xuất, quá hạn sạch */
    private function planStates(object $ctx): array
    {
        // BĐSX của lô khác bắt đầu từ thời điểm $t (lô mở ngay lúc vừa vệ sinh xong vẫn tính)
        $starts = $ctx->plans->map(fn($p) => [$this->ts($p->actual_start), $p->id])->sortBy(0)->values();
        $nextStart = fn($t, $selfId) => $starts->first(fn($s) => $s[0] >= $t && $s[1] != $selfId)[0] ?? null;
        $states = [];

        foreach ($ctx->plans as $p) {
            $label = $ctx->labels->get($p->id, $p->title);
            $ys = $ctx->yields->get($p->id, collect())->values();

            for ($i = 0; $i + 1 < $ys->count(); $i++) {
                $from = $this->ts($ys[$i]->end);
                $to = $this->ts($ys[$i + 1]->start);
                if ($to > $from) {
                    $states[] = $this->state('paused', $from, $to, 'Lô ' . $label);
                }
            }

            $kt = max($this->ts($p->actual_end) ?? 0, $ys->isNotEmpty() ? $this->ts($ys->last()->end) : 0) ?: null;
            if (!$kt) {
                continue;
            }

            $cleanStart = $this->ts($p->actual_start_clearning);
            $cleanEnd = $this->ts($p->actual_end_clearning);

            // Sau KT: chờ vệ sinh tới lúc bắt đầu vệ sinh (hoặc tới lô kế tiếp nếu không ghi vệ sinh)
            $waitTo = min(array_filter([$cleanStart, $nextStart($kt, $p->id), $ctx->now]));
            if ($waitTo > $kt) {
                $states[] = $this->state('wait_clean', $kt, $waitTo, 'Sau lô ' . $label . ' · KT ' . date('H:i d/m', $kt));
            }

            // Sau vệ sinh: phòng sạch chờ lô kế tiếp; quá hạn sạch thì cần vệ sinh lại
            if ($cleanEnd) {
                $level = $this->execution->levelOf($p->title_clearning);
                $expired = $cleanEnd + ProductionExecutionService::CLEAN_HOLD_HOURS[$level] * 3600;
                $idleTo = min(array_filter([$nextStart($cleanEnd, $p->id), $ctx->now]));
                $note = 'Sạch từ ' . date('H:i d/m', $cleanEnd) . ' (' . $level . ')';
                if ($idleTo > $cleanEnd) {
                    $states[] = $this->state('clean_idle', $cleanEnd, min($idleTo, $expired), $note);
                }
                if ($idleTo > $expired) {
                    $states[] = $this->state('expired', $expired, $idleTo, 'Hết hạn sạch lúc ' . date('H:i d/m', $expired));
                }
            }
        }

        return $states;
    }

    /** Trạng thái ưu tiên cao nhất phủ trọn [a, b] */
    private function pick(array $states, int $a, int $b): ?object
    {
        $best = null;
        foreach ($states as $s) {
            if ($s->from <= $a && $s->to >= $b && (!$best || self::PRIORITY[$s->reason] < self::PRIORITY[$best->reason])) {
                $best = $s;
            }
        }

        return $best;
    }

    private function gaps(array $merged, int $w0, int $until): array
    {
        $gaps = [];
        $cur = $w0;
        foreach ($merged as [$s, $e]) {
            if ($s > $cur && $cur < $until) {
                $gaps[] = [$cur, min($s, $until)];
            }
            $cur = max($cur, $e);
        }
        if ($cur < $until) {
            $gaps[] = [$cur, $until];
        }

        return array_values(array_filter($gaps, fn($g) => $g[1] > $g[0]));
    }

    private function clamp(array $intervals, int $w0, int $w1): array
    {
        return array_values(array_filter(
            array_map(fn($i) => [max($i[0], $w0), min($i[1], $w1)], array_filter($intervals, fn($i) => $i[0] !== null && $i[1] !== null)),
            fn($i) => $i[1] > $i[0]
        ));
    }

    private function merge(array $intervals): array
    {
        usort($intervals, fn($a, $b) => $a[0] <=> $b[0]);
        $out = [];
        foreach ($intervals as $i) {
            $n = count($out);
            if ($n && $i[0] <= $out[$n - 1][1]) {
                $out[$n - 1][1] = max($out[$n - 1][1], $i[1]);
            } else {
                $out[] = $i;
            }
        }

        return $out;
    }

    private function item(string $kind, int $from, int $to, ?string $title, ?string $note, bool $live): object
    {
        return (object) ['kind' => $kind, 'from' => $from, 'to' => $to, 'title' => $title, 'note' => $note, 'live' => $live];
    }

    private function state(string $reason, int $from, int $to, ?string $note): object
    {
        return (object) ['reason' => $reason, 'from' => $from, 'to' => $to, 'note' => $note];
    }

    private function toCarbon(object $e, Carbon $dayStart): object
    {
        $e->start = Carbon::createFromTimestamp($e->from, $dayStart->getTimezone());
        $e->end = Carbon::createFromTimestamp($e->to, $dayStart->getTimezone());

        return $e;
    }

    private function ts($t): ?int
    {
        return $t ? Carbon::parse($t)->getTimestamp() : null;
    }
}
