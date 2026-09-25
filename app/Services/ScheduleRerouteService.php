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
 * Chạy ở 2 thời điểm trên trang xác nhận hoàn thành:
 *   - nút ✓ (xác nhận sản xuất): lô đã trễ thì dời vệ sinh của chính lô và dịch các lô sau (rerouteAfterProduction),
 *   - nút ✓✓ (xác nhận toàn bộ): dịch 2 chiều theo giờ kết thúc vệ sinh thực tế (reroute).
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
 * Ngày nghỉ: dịch trễ thì được chạy trong ngày nghỉ (tăng ca) để hấp thụ trễ; lô mà lịch gốc kéo dài
 * vắt ngang ngày nghỉ chỉ lấn vào phần nghỉ vừa đủ, còn kịp thì giữ giờ kết thúc cũ. Kéo sớm thì không tăng ca.
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

    public const TYPE_OF_CHANGE_UNDO = 'Hoàn tác tịnh tuyến';

    /** Nút ✓ "Xác nhận sản xuất": đã có giờ kết thúc sản xuất, vệ sinh chưa làm. */
    public const TRIGGER_PRODUCTION = 'production';

    /** Nút ✓✓ "Xác nhận toàn bộ": đã có giờ vệ sinh thực tế. */
    public const TRIGGER_FINISHED = 'finished';

    public const SOURCE_CLEANING_REASON = 'Dời vệ sinh của chính lô ra sau giờ kết thúc sản xuất thực tế';

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

    /** @var array<int, array<int, array{0: int, 1: int}>> resourceId => ngày nghỉ thực sự của phòng */
    private array $roomOffRanges = [];

    /**
     * Nút ✓✓ "Xác nhận toàn bộ": tịnh tuyến theo giờ kết thúc vệ sinh thực tế (dịch 2 chiều).
     * Nếu trước đó đã tịnh tuyến theo nút ✓ thì lịch lý thuyết của lô đã được dời,
     * nên lần này chỉ bù phần chênh còn lại.
     *
     * @return array{run_code: ?string, delta_minutes: int, changes: array, source_cleaning_moved: bool}
     */
    public function reroute(int $stagePlanId): array
    {
        $source = $this->loadSource($stagePlanId);

        $actualFinishRaw = $source ? ($source->actual_end_clearning ?? $source->actual_end) : null;
        if (! $actualFinishRaw) {
            return $this->emptyResult();
        }

        return $this->run($source, self::TRIGGER_FINISHED, Carbon::parse($actualFinishRaw)->getTimestamp());
    }

    /**
     * Nút ✓ "Xác nhận sản xuất" (vệ sinh chưa làm): nếu lô đã trễ so với lịch lý thuyết thì dời
     * vệ sinh của chính lô ra ngay sau giờ kết thúc sản xuất thực tế và dịch các lô sau theo.
     * Chỉ dịch về sau, không kéo sớm: bấm ✓ theo ca thì lô có thể vẫn đang chạy tiếp.
     * Bấm ✓ nhiều lần thì mỗi lần chỉ bù phần trễ thêm so với lần trước.
     *
     * @return array{run_code: ?string, delta_minutes: int, changes: array, source_cleaning_moved: bool}
     */
    public function rerouteAfterProduction(int $stagePlanId): array
    {
        $source = $this->loadSource($stagePlanId);

        if (! $source || ! $source->actual_end || $source->actual_start_clearning) {
            return $this->emptyResult();
        }

        return $this->run($source, self::TRIGGER_PRODUCTION, Carbon::parse($source->actual_end)->getTimestamp());
    }

    /**
     * @param  int  $actualTs  TRIGGER_FINISHED: giờ kết thúc thực tế của lô; TRIGGER_PRODUCTION: giờ kết thúc sản xuất thực tế
     */
    private function run(object $source, string $trigger, int $actualTs): array
    {
        $theoryFinish = Carbon::parse($source->end_clearning ?? $source->end)->getTimestamp();

        $windowStart = $this->at(min(
            Carbon::parse($source->start)->getTimestamp(),
            $source->actual_start ? Carbon::parse($source->actual_start)->getTimestamp() : PHP_INT_MAX
        ))->subDay();
        $windowEnd = $this->at(max($theoryFinish, $actualTs))->addDays(self::WINDOW_DAYS);

        $this->loadGraph($windowStart, $windowEnd);
        $this->loadOffRanges($windowStart, $windowEnd->copy()->addDays(self::WINDOW_DAYS));

        if (! isset($this->nodes[$source->id])) {
            return $this->emptyResult();
        }

        $sourceCleaning = null;
        $actualFinish = $actualTs;

        if ($trigger === self::TRIGGER_PRODUCTION) {
            $sourceCleaning = $this->projectSourceCleaning($source, $actualTs);
            $actualFinish = $sourceCleaning['finish'];
        }

        // Độ lệch giờ kết thúc của lô gốc => quyết định dịch các lô sau
        $delta = $actualFinish - $theoryFinish;

        // Độ lệch báo cho người dùng / ghi lý do: ✓ = sản xuất trễ bao nhiêu so với giờ kết thúc sản xuất lý thuyết
        $reportDelta = $trigger === self::TRIGGER_PRODUCTION
            ? $actualTs - Carbon::parse($source->end)->getTimestamp()
            : $delta;

        // ✓ chỉ dịch khi lô trễ; ✓✓ dịch cả 2 chiều
        $propagate = $trigger === self::TRIGGER_PRODUCTION ? $delta >= self::MIN_SHIFT_SECONDS : abs($delta) >= self::MIN_SHIFT_SECONDS;

        // Vệ sinh của lô gốc vẫn dời dù phần trễ đã được hấp thụ hết (vệ sinh vốn dừng qua ngày nghỉ)
        $sourceChange = $this->sourceCleaningChange($source, $sourceCleaning);

        if (! $propagate && ! $sourceChange) {
            return $this->emptyResult();
        }

        $changes = [];
        if ($propagate) {
            // Lô gốc: thời điểm kết thúc lý thuyết -> thực tế (✓: dự kiến, gồm cả vệ sinh đã dời)
            $this->nodes[$source->id]['origStart'] = Carbon::parse($source->start)->getTimestamp();
            $this->nodes[$source->id]['origFinish'] = $theoryFinish;
            $this->nodes[$source->id]['curFinish'] = $actualFinish;

            $changes = $this->propagate($source->id);
        }

        $result = ['run_code' => null, 'delta_minutes' => intdiv($reportDelta, 60), 'changes' => [], 'source_cleaning_moved' => false];

        if (empty($changes) && ! $sourceChange) {
            return $result;
        }

        $runCode = (string) Str::uuid();
        $this->apply($runCode, $source, intdiv($reportDelta, 60), $changes, $sourceChange, $this->runReason($source, $trigger, $reportDelta));

        return ['run_code' => $runCode, 'changes' => array_values($changes), 'source_cleaning_moved' => (bool) $sourceChange] + $result;
    }

    private function loadSource(int $stagePlanId): ?object
    {
        $source = DB::table('stage_plan')->where('id', $stagePlanId)->first();

        if (! $source || (int) $source->finished !== 1 || ! $source->start || ! $source->end) {
            return null;
        }

        $sourceStage = (int) $source->stage_code;
        if (in_array($sourceStage, self::SKIP_STAGES, true) || $sourceStage === self::MAINTENANCE_STAGE) {
            return null;
        }

        return $source;
    }

    private function emptyResult(): array
    {
        return ['run_code' => null, 'delta_minutes' => 0, 'changes' => [], 'source_cleaning_moved' => false];
    }

    /**
     * Vệ sinh chưa làm của lô gốc khi sản xuất kết thúc trễ: bắt đầu ngay khi kết thúc sản xuất thực tế,
     * giữ thời lượng làm việc theo kế hoạch. Giống lô dịch trễ trong place(): được chạy trong ngày nghỉ,
     * vệ sinh vốn dừng qua ngày nghỉ thì còn kịp thì giữ giờ kết thúc cũ. Chưa trễ thì giữ nguyên kế hoạch.
     *
     * @return array{start: ?int, end: ?int, finish: int}  start = null nếu vệ sinh không dời
     */
    private function projectSourceCleaning(object $source, int $actualEnd): array
    {
        if (! $source->start_clearning || ! $source->end_clearning) {
            return ['start' => null, 'end' => null, 'finish' => $actualEnd];
        }

        $cs = Carbon::parse($source->start_clearning)->getTimestamp();
        $ce = Carbon::parse($source->end_clearning)->getTimestamp();

        // Chưa trễ: lô có thể vẫn đang chạy tiếp ca sau, không kéo vệ sinh sớm lên
        if ($actualEnd <= $cs) {
            return ['start' => null, 'end' => null, 'finish' => $ce];
        }

        $work = $this->workSeconds($cs, $ce, $this->offRangesFor($this->nodes[$source->id]));
        $newCe = max($ce, $actualEnd + ($work > 0 ? $work : max(0, $ce - $cs)));

        return ['start' => $actualEnd, 'end' => $newCe, 'finish' => $newCe];
    }

    private function sourceCleaningChange(object $source, ?array $projected): ?array
    {
        if (! $projected || $projected['start'] === null) {
            return null;
        }

        $oldCs = Carbon::parse($source->start_clearning)->getTimestamp();
        $oldCe = Carbon::parse($source->end_clearning)->getTimestamp();

        if (abs($projected['start'] - $oldCs) < self::MIN_SHIFT_SECONDS && abs($projected['end'] - $oldCe) < self::MIN_SHIFT_SECONDS) {
            return null;
        }

        return [
            'old' => ['start_clearning' => $source->start_clearning, 'end_clearning' => $source->end_clearning],
            'new' => ['start_clearning' => $this->fmt($projected['start']), 'end_clearning' => $this->fmt($projected['end'])],
            'shift_minutes' => intdiv($projected['start'] - $oldCs, 60),
        ];
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

                // Dòng của chính lô gốc (nút ✓): chỉ khôi phục vệ sinh, khi vệ sinh chưa làm và chưa bị dời lần nữa
                if ((int) $log->stage_plan_id === (int) $log->source_stage_plan_id) {
                    $unchanged = $row
                        && ! $row->actual_start_clearning
                        && $this->sameTime($row->start_clearning, $log->new_start_clearning)
                        && $this->sameTime($row->end_clearning, $log->new_end_clearning);

                    if (! $unchanged) {
                        $skipped++;

                        continue;
                    }

                    $this->writeSourceCleaning($row, $log->old_start_clearning, $log->old_end_clearning, self::TYPE_OF_CHANGE_UNDO);
                    $restored++;

                    continue;
                }

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
                // Đã xác nhận sản xuất (✓) nhưng chưa xác nhận vệ sinh: phòng còn bận tới hết vệ sinh theo kế hoạch
                $finish = $ts($r->actual_end_clearning) ?? (max($end ?? 0, $cEnd ?? 0) ?: null);
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

                // Thứ tự trên phòng luôn theo giờ LÝ THUYẾT (thứ tự đã sắp lịch),
                // kể cả với lô đã chạy: lô bắt đầu thực tế trễ vẫn đứng trước các lô sau nó.
                'orderStart' => $ts($r->start) ?? $start,

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
            usort($ids, fn($a, $b) => [$this->nodes[$a]['orderStart'], $a] <=> [$this->nodes[$b]['orderStart'], $b]);
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

        // Ngày nghỉ công ty nhưng phòng vẫn có lô bắt đầu / kết thúc / nằm trong ngày đó
        // => với phòng này, ngày đó là ngày làm việc (tăng ca), không được bỏ qua.
        // Lô chỉ vắt ngang trọn ngày nghỉ (bắt đầu trước, kết thúc sau) thì KHÔNG tính: lô đó đang dừng
        // qua ngày nghỉ (lịch gốc kéo dài lô vì ngày nghỉ), tức phòng nghỉ ngày đó.
        $this->roomOffRanges = [];
        foreach ($this->roomOrder as $room => $ids) {
            $this->roomOffRanges[$room] = array_values(array_filter($this->offRanges, function ($range) use ($ids) {
                foreach ($ids as $id) {
                    $n = $this->nodes[$id];
                    foreach ([['origStart', 'origEnd'], ['origCleanStart', 'origCleanEnd'], ['curStart', 'curEnd'], ['curCleanStart', 'curCleanEnd']] as [$a, $b]) {
                        if ($n[$a] !== null && $n[$b] !== null && $this->worksInside($n[$a], $n[$b], $range)) {
                            return false;
                        }
                    }
                }

                return true;
            }));
        }

        foreach ($this->nodes as $id => $n) {
            if (! $n['movable']) {
                continue;
            }
            $ranges = $this->offRangesFor($n);

            // Thời lượng làm việc không bao giờ được về 0: nếu toàn bộ lô nằm trong ngày nghỉ
            // thì giữ nguyên thời lượng thực của lịch gốc.
            $wall = $n['origEnd'] - $n['origStart'];
            $work = $this->workSeconds($n['origStart'], $n['origEnd'], $ranges);
            $this->nodes[$id]['mainWork'] = $work > 0 ? $work : $wall;

            if ($n['origCleanStart'] !== null && $n['origCleanEnd'] !== null) {
                $cWall = $n['origCleanEnd'] - $n['origCleanStart'];
                $cWork = $this->workSeconds($n['origCleanStart'], $n['origCleanEnd'], $ranges);
                $this->nodes[$id]['cleanWork'] = $cWork > 0 ? $cWork : $cWall;
            }
        }
    }

    /**
     * Khoảng [start, end] có làm việc trong ngày nghỉ $range: chồng lên ngày nghỉ nhưng không vắt ngang trọn ngày nghỉ.
     *
     * @param  array{0: int, 1: int}  $range
     */
    private function worksInside(int $start, int $end, array $range): bool
    {
        [$os, $oe] = $range;

        return $start < $oe && $end > $os && ! ($start < $os && $end > $oe);
    }

    /**
     * @return array<int, array{0: int, 1: int}>
     */
    private function offRangesFor(array $node): array
    {
        return $node['room'] !== null ? ($this->roomOffRanges[$node['room']] ?? $this->offRanges) : $this->offRanges;
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
     * Đặt lô bắt đầu từ $start, giữ nguyên thời lượng làm việc.
     *
     * - Dịch trễ: được chạy cả trong ngày nghỉ (tăng ca) để hấp thụ trễ. Lô vốn dừng qua ngày nghỉ
     *   (lịch gốc kéo dài lô vì ngày nghỉ) chỉ lấn vào phần nghỉ vừa đủ: còn kịp thì giữ giờ kết thúc cũ.
     * - Kéo sớm: không tự sinh tăng ca, bỏ qua ngày nghỉ phòng không làm.
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

        $mainWork = $n['mainWork'] ?? ($n['origEnd'] - $n['origStart']);

        if ($start > $n['origStart']) {
            $e = max($n['origEnd'], $start + $mainWork);

            $cs = null;
            $ce = null;
            if ($n['cleanWork'] !== null) {
                $cs = $e + ($n['cleanGap'] ?? 0);
                $ce = max($n['origCleanEnd'], $cs + $n['cleanWork']);
            }

            return ['start' => $start, 'end' => $e, 'cleanStart' => $cs, 'cleanEnd' => $ce, 'finish' => $ce ?? $e];
        }

        $ranges = $this->offRangesFor($n);

        [$s, $e] = $this->placeForward($start, $mainWork, $ranges);

        $cs = null;
        $ce = null;
        if ($n['cleanWork'] !== null) {
            [$cs, $ce] = $this->placeForward($e + ($n['cleanGap'] ?? 0), $n['cleanWork'], $ranges);
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

    private function workSeconds(int $start, int $end, array $ranges): int
    {
        $total = max(0, $end - $start);

        foreach ($ranges as [$os, $oe]) {
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
    private function placeForward(int $start, int $work, array $ranges): array
    {
        $cursor = $start;

        // Nếu điểm bắt đầu rơi vào ngày nghỉ thì dời tới hết ngày nghỉ
        foreach ($ranges as [$os, $oe]) {
            if ($cursor >= $os && $cursor < $oe) {
                $cursor = $oe;
            }
        }
        $realStart = $cursor;
        $remain = $work;

        foreach ($ranges as [$os, $oe]) {
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

    /**
     * @param  ?array  $sourceChange  Vệ sinh của chính lô gốc được dời (nút ✓), null nếu không dời
     * @param  string  $reason  Lý do ghi vào stage_plan_history.type_of_change
     */
    private function apply(string $runCode, object $source, int $deltaMinutes, array $changes, ?array $sourceChange, string $reason): void
    {
        $offDays = DB::table('off_days')->pluck('off_date')->map(fn($d) => Carbon::parse($d)->toDateString())->all();
        $user = session('user')['fullName'] ?? 'System';
        $department = session('user.production_code') ?? $source->deparment_code;

        DB::transaction(function () use ($runCode, $source, $deltaMinutes, $changes, $sourceChange, $reason, $offDays, $user, $department) {
            $logs = [];

            if ($sourceChange) {
                $this->writeSourceCleaning($source, $sourceChange['new']['start_clearning'], $sourceChange['new']['end_clearning'], $reason);

                $logs[] = [
                    'run_code' => $runCode,
                    'source_stage_plan_id' => $source->id,
                    'source_title' => $source->title,
                    'source_delta_minutes' => $deltaMinutes,
                    'stage_plan_id' => $source->id,
                    'old_start' => $source->start,
                    'old_end' => $source->end,
                    'old_start_clearning' => $sourceChange['old']['start_clearning'],
                    'old_end_clearning' => $sourceChange['old']['end_clearning'],
                    'new_start' => $source->start,
                    'new_end' => $source->end,
                    'new_start_clearning' => $sourceChange['new']['start_clearning'],
                    'new_end_clearning' => $sourceChange['new']['end_clearning'],
                    'shift_minutes' => $sourceChange['shift_minutes'],
                    'reason' => self::SOURCE_CLEANING_REASON,
                    'deparment_code' => $department,
                    'created_by' => $user,
                    'created_at' => now(),
                ];
            }

            foreach ($changes as $id => $c) {
                $row = DB::table('stage_plan')->where('id', $id)->first();
                if (! $row) {
                    continue;
                }

                $this->writeTimes($row, $c['new'], $offDays, $user, $reason);

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
     * ngày nhận bao bì (ĐG), lịch sử phiên bản khi lịch đã submit.
     * Khác update(): giữ nguyên cờ submit, vì đây là dịch tự động theo xác nhận hoàn thành, không phải
     * người lập lịch sửa tay; thay đổi đã được ghi vào stage_plan_history kèm lý do.
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

        if ((int) $row->submit === 1 && $receiveDate) {
            PackagingDate::sync($row->id, $receiveDate, 0, 'ScheduleRerouteService');
            PackagingDate::sync($row->id, $receiveDate, 1, 'ScheduleRerouteService');
        }

        $this->recordHistory($row, $typeOfChange);
    }

    /**
     * Dời vệ sinh lý thuyết của lô gốc. Lô đang chạy / đã chạy nên chỉ đổi giờ vệ sinh,
     * không đụng giờ sản xuất lý thuyết và các cờ submit / xác nhận của lô.
     */
    private function writeSourceCleaning(object $row, ?string $startClearning, ?string $endClearning, string $typeOfChange): void
    {
        DB::table('stage_plan')->where('id', $row->id)->update([
            'start_clearning' => $startClearning,
            'end_clearning' => $endClearning,
        ]);

        $this->recordHistory($row, $typeOfChange);
    }

    /**
     * Ghi phiên bản vào stage_plan_history kèm lý do tịnh tuyến.
     *
     * Ngoài lô đã submit, còn ghi cả lô đã có phiên bản nhưng đang submit = 0 (người lập lịch đã sửa tay,
     * chưa submit lại), để không mất dấu lần tịnh tuyến. Lô chưa submit lần nào thì bỏ qua:
     * lúc submit sẽ ghi "Tạo Mới Lịch". Submit bỏ qua lô có phiên bản mới nhất trùng giờ, nên không bị ghi trùng.
     *
     * @param  object  $row  Dòng stage_plan TRƯỚC khi cập nhật (để lấy cờ submit cũ)
     */
    private function recordHistory(object $row, string $typeOfChange): void
    {
        if ((int) $row->submit !== 1 && ! DB::table('stage_plan_history')->where('stage_plan_id', $row->id)->exists()) {
            return;
        }

        StagePlanHistory::record(DB::table('stage_plan')->where('id', $row->id)->first(), $typeOfChange);
    }

    // =====================================================================
    // Tiện ích
    // =====================================================================

    /**
     * Lý do ghi vào stage_plan_history: lô nào (sản phẩm, số lô, công đoạn, phòng),
     * xác nhận lúc nào, giờ kết thúc thực tế và lệch bao nhiêu so với lịch lý thuyết.
     * Cùng 1 chuỗi cho mọi lô trong lần tịnh tuyến, để trang Lịch Sử Thay Đổi tính là 1 lần thay đổi.
     */
    private function runReason(object $source, string $trigger, int $deltaSeconds): string
    {
        $info = DB::table('stage_plan as sp')
            ->leftJoin('plan_master as pm', 'pm.id', '=', 'sp.plan_master_id')
            ->leftJoin('finished_product_category as fpc', 'fpc.id', '=', 'sp.product_caterogy_id')
            ->leftJoin('intermediate_category as ic', 'ic.intermediate_code', '=', 'fpc.intermediate_code')
            ->leftJoin('product_name as pn', 'pn.id', '=', 'ic.product_name_id')
            ->leftJoin('room as r', 'r.id', '=', 'sp.resourceId')
            ->where('sp.id', $source->id)
            ->select('pn.name as product_name', DB::raw('COALESCE(pm.actual_batch, pm.batch) as batch'), 'r.code as room_code', 'r.stage as stage_name')
            ->first();

        $batch = ($info->product_name ?? $source->title) . ($info && $info->batch ? ' - lô ' . $info->batch : '');
        $where = implode(', ', array_filter([$info->stage_name ?? null, ($info->room_code ?? null) ? 'phòng ' . $info->room_code : null]));
        $confirmedAt = Carbon::parse($source->finished_date ?? now())->format('H:i d/m/Y');
        $lag = ($deltaSeconds < 0 ? 'sớm ' : 'trễ ') . $this->durationText($deltaSeconds);

        if ($trigger === self::TRIGGER_PRODUCTION) {
            $head = 'Tịnh tuyến theo xác nhận sản xuất';
            $actual = 'KT sản xuất thực tế ' . Carbon::parse($source->actual_end)->format('H:i d/m/Y');
        } else {
            $head = 'Tịnh tuyến theo xác nhận hoàn thành';
            $actual = 'KT vệ sinh thực tế ' . Carbon::parse($source->actual_end_clearning ?? $source->actual_end)->format('H:i d/m/Y');
        }

        $text = "{$head}: {$batch}" . ($where !== '' ? " ({$where})" : '')
            . ", xác nhận lúc {$confirmedAt}, {$actual}, {$lag}";

        // stage_plan_history.type_of_change là varchar(255)
        return Str::limit($text, 250, '...');
    }

    private function durationText(int $seconds): string
    {
        $minutes = intdiv(abs($seconds), 60);
        $hours = intdiv($minutes, 60);
        $minutes %= 60;

        if ($hours === 0) {
            return "{$minutes} phút";
        }

        return $minutes > 0 ? "{$hours} giờ {$minutes} phút" : "{$hours} giờ";
    }

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
