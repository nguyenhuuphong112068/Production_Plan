<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Thực thi sản xuất theo phòng (trang "Thực Thi Sản Xuất").
 *
 * Máy trạng thái của phòng:
 *   Phòng Sạch → (Mở phòng, chọn lô) Đang SX ⇄ Tạm Dừng SX → (Kết thúc SX) Cần VS → Đang VS → Phòng Sạch
 *   Mở phòng chọn "Chuẩn bị": Phòng Sạch → Đang Chuẩn Bị (BĐSX) → (Thực thi sản xuất, BĐCM) Đang SX → ...
 *   Phòng Sạch quá hạn → Cần VS Lại → Đang VS → Phòng Sạch
 *
 * - Trạng thái hiện tại lưu ở room_execution_log (dòng ended_at NULL, chưa hủy).
 * - Sản lượng/thời gian thực tế ghi vào stage_plan + yields giống trang Xác nhận hoàn thành, nhưng CHỈ khi
 *   Tạm dừng/Kết thúc SX (= ✓) và Kết thúc vệ sinh (= ✓✓): lịch Gantt ẩn lô có actual_start mà finished = 0,
 *   nên không ghi actual_start lúc vừa bắt đầu.
 * - Vệ sinh không gắn lô, khoảng tạm dừng và hoạt động ngoài lịch ghi vào room_status (is_daily_report = 1)
 *   để hiện ở Báo cáo ngày. Hoạt động khác chỉ thêm được ngoài khoảng BĐSX → KT của lô (BATCH_RUNNING_STATES).
 * - Phòng chưa có log (hoặc log lỗi thời do có người xác nhận ở trang cũ) thì trạng thái được dẫn xuất
 *   từ lô có thời gian thực tế mới nhất của phòng.
 */
class ProductionExecutionService
{
    const CLEAN      = 1;
    const PRODUCING  = 2;
    const NEED_CLEAN = 3;
    const CLEANING   = 4;
    const EXPIRED    = 5; // Phòng sạch quá hạn: chỉ tính lúc hiển thị, không lưu
    const PAUSED     = 6;
    const PREPARING  = 7; // Mở phòng chọn "Chuẩn bị": đã có BĐSX, chưa bắt đầu tạo ra sản lượng (BĐCM)

    const STATE_LABELS = [
        self::CLEAN      => 'Phòng Sạch',
        self::PRODUCING  => 'Đang Sản Xuất',
        self::NEED_CLEAN => 'Cần Vệ Sinh',
        self::CLEANING   => 'Đang Vệ Sinh',
        self::EXPIRED    => 'Cần Vệ Sinh Lại',
        self::PAUSED     => 'Tạm Dừng SX',
        self::PREPARING  => 'Đang Chuẩn Bị',
    ];

    // [hậu tố class CSS, icon]
    const STATE_META = [
        self::CLEAN      => ['clean', 'fa-check-circle'],
        self::PRODUCING  => ['producing', 'fa-cog'],
        self::NEED_CLEAN => ['dirty', 'fa-exclamation-circle'],
        self::CLEANING   => ['cleaning', 'fa-broom'],
        self::EXPIRED    => ['expired', 'fa-exclamation-triangle'],
        self::PAUSED     => ['paused', 'fa-pause-circle'],
        self::PREPARING  => ['preparing', 'fa-clipboard-check'],
    ];

    // Thứ tự trạng thái trên bộ lọc và bộ đếm (trang Thực Thi SX, trang công khai)
    const DISPLAY_ORDER = [self::PREPARING, self::PRODUCING, self::PAUSED, self::NEED_CLEAN, self::CLEANING, self::CLEAN, self::EXPIRED];

    // Mở phòng: chuẩn bị trước (BĐCM ghi khi bấm Thực thi sản xuất) hoặc thực thi ngay (BĐCM = BĐSX)
    const START_MODES = ['prepare' => self::PREPARING, 'execute' => self::PRODUCING];

    // Lô đang chạy trong phòng (từ BĐSX đến KT): phòng dành cho lô, không được thêm hoạt động khác
    const BATCH_RUNNING_STATES = [self::PREPARING, self::PRODUCING, self::PAUSED];

    // Hủy thao tác: chỉ các thao tác chưa ghi gì vào stage_plan/yields, và chỉ trong 2 phút kể từ lúc thao tác
    const UNDO_STATES = [self::PREPARING, self::PRODUCING, self::CLEANING];
    const UNDO_SECONDS = 120;

    // Mã ca (assignments.Sheet) như trang Lịch Công Tác → Sản Xuất
    const SHIFT_NAMES = [1 => 'Ca 1', 2 => 'Ca 2', 3 => 'Ca 3', 4 => 'Hành chính', 5 => 'Khác', 6 => 'Ca 4'];

    const CLEANING_LEVELS = [
        'VS-I'   => 'Vệ sinh cấp I',
        'VS-II'  => 'Vệ sinh cấp II',
        'VS-LAI' => 'Vệ sinh lại',
    ];

    // Hạn phòng sạch (giờ) theo cấp vệ sinh. Tạm lấy theo quy định bên eBMR
    // (cấp I 3 ngày, cấp II 7 ngày, vệ sinh lại 24h) — cần QA xác nhận lại.
    const CLEAN_HOLD_HOURS = ['VS-I' => 72, 'VS-II' => 168, 'VS-LAI' => 24];

    // Nhóm công đoạn hiển thị; Cân NL Khác (stage 2) gộp vào Cân NL
    const STAGE_GROUPS = [
        1 => ['label' => 'Cân Nguyên Liệu', 'icon' => 'fa-balance-scale', 'grad' => 'g-blue'],
        3 => ['label' => 'Pha Chế', 'icon' => 'fa-flask', 'grad' => 'g-teal'],
        4 => ['label' => 'Trộn Hoàn Tất', 'icon' => 'fa-blender', 'grad' => 'g-purple'],
        5 => ['label' => 'Định Hình', 'icon' => 'fa-capsules', 'grad' => 'g-orange'],
        6 => ['label' => 'Bao Phim', 'icon' => 'fa-circle-notch', 'grad' => 'g-rose'],
        7 => ['label' => 'Đóng Gói', 'icon' => 'fa-box-open', 'grad' => 'g-slate'],
    ];

    /* =========================================================
       ĐỌC TRẠNG THÁI
       ========================================================= */

    public function board(string $deparmentCode): Collection
    {
        $rooms = $this->roomQuery()->where('deparment_code', $deparmentCode)->get();
        $this->attachStates($rooms);

        return $rooms;
    }

    public function room(int $roomId): ?object
    {
        $room = $this->roomQuery()->where('id', $roomId)->first();
        if ($room) {
            $this->attachStates(collect([$room]));
        }

        return $room;
    }

    private function roomQuery()
    {
        return DB::table('room')
            ->where('active', 1)
            ->whereBetween('stage_code', [1, 7])
            ->orderBy('stage_code')
            ->orderBy('order_by')
            ->orderBy('code')
            ->select('id', 'code', 'name', 'main_equiment_name', 'stage', 'stage_code', 'deparment_code', 'active');
    }

    /**
     * Gắn vào mỗi phòng: ->st (trạng thái hiện tại + lô), ->next_plan (lô kế tiếp theo lịch),
     * ->activities (hoạt động khác đang diễn ra), ->stage_group.
     */
    private function attachStates(Collection $rooms): void
    {
        $ids = $rooms->pluck('id')->all();
        if (!$ids) {
            return;
        }

        $now = now();

        // Nếu lỡ có nhiều dòng mở cho 1 phòng thì dòng mới nhất thắng (keyBy ghi đè theo thứ tự id)
        $openLogs = DB::table('room_execution_log')
            ->whereIn('room_id', $ids)
            ->whereNull('ended_at')
            ->whereNull('cancelled_at')
            ->orderBy('id')
            ->get()
            ->keyBy('room_id');

        $latest = $this->latestActualPlans($ids);

        $donePlanIds = DB::table('stage_plan')
            ->whereIn('id', $openLogs->pluck('stage_plan_id')->filter()->unique()->all())
            ->whereNotNull('actual_end_clearning')
            ->pluck('id')
            ->flip();

        foreach ($rooms as $room) {
            $log = $openLogs->get($room->id);
            $sp = $latest->get($room->id);
            $spTime = $sp ? ($sp->actual_end_clearning ?? $sp->actual_end ?? $sp->actual_start) : null;

            // Log lỗi thời: lô của nó đã được xác nhận vệ sinh (✓✓) ở trang Xác nhận hoàn thành,
            // hoặc phòng có lô khác được xác nhận ở trang đó với thời gian thực tế mới hơn log
            $stale = $log && (
                ($log->state != self::CLEAN && $log->stage_plan_id && isset($donePlanIds[$log->stage_plan_id]))
                || ($sp && $sp->id != $log->stage_plan_id && $spTime > $log->started_at)
            );

            $room->st = $log && !$stale ? $this->stateFromLog($log, $now) : $this->deriveState($sp, $now);
            $room->stage_group = in_array((int) $room->stage_code, [1, 2], true) ? 1 : (int) $room->stage_code;
        }

        $nextIds = $this->nextPlanIds($ids);
        $details = $this->planDetails(array_merge(
            $rooms->pluck('st.stage_plan_id')->filter()->all(),
            array_values($nextIds)
        ));

        $activities = DB::table('room_status')
            ->whereIn('room_id', $ids)
            ->where('is_daily_report', 1)
            ->where('active', 1)
            ->whereNotNull('start')
            ->whereNull('end')
            ->orderBy('start')
            ->get()
            ->groupBy('room_id');

        $staff = $this->onDutyStaff($rooms);

        // Lô đang chạy trong phòng (từ BĐSX đến KT), để vẽ thanh thời gian trên card: các lần đã khai báo sản lượng
        // (BĐCM → KT) và các lần mở phòng (Đang chuẩn bị / Đang SX) để lấy BĐSX và các khoảng chuẩn bị
        $livePlanIds = $rooms->filter(fn($r) => in_array($r->st->state, self::BATCH_RUNNING_STATES, true))
            ->pluck('st.stage_plan_id')->filter()->all();
        $segments = $livePlanIds
            ? DB::table('yields')
                ->whereIn('stage_plan_id', $livePlanIds)
                ->whereNotNull('start')
                ->whereNotNull('end')
                ->orderBy('start')
                ->get(['stage_plan_id', 'start', 'end', 'yield'])
                ->groupBy('stage_plan_id')
            : collect();
        $openings = $livePlanIds
            ? DB::table('room_execution_log')
                ->whereIn('stage_plan_id', $livePlanIds)
                ->whereIn('state', [self::PREPARING, self::PRODUCING])
                ->whereNull('cancelled_at')
                ->orderBy('started_at')
                ->get(['stage_plan_id', 'state', 'started_at', 'ended_at'])
                ->groupBy('stage_plan_id')
            : collect();

        foreach ($rooms as $room) {
            $plan = $room->st->plan = $room->st->stage_plan_id ? $details->get($room->st->stage_plan_id) : null;
            if ($plan && in_array($room->st->state, self::BATCH_RUNNING_STATES, true)) {
                $logs = $openings->get($plan->id, collect());
                $plan->segments = $segments->get($plan->id, collect());
                // BĐSX = lần mở phòng đầu tiên, kể cả mở để chuẩn bị (cùng quy tắc recordSegment ghi vào actual_start)
                $plan->batch_start = $plan->actual_start ?? $logs->min('started_at') ?? $room->st->since;
                // Khoảng chuẩn bị (BĐSX → BĐCM); ended_at NULL = đang chuẩn bị
                $plan->prep = $logs->where('state', self::PREPARING)->values();
            }
            $room->next_plan = isset($nextIds[$room->id]) ? $details->get($nextIds[$room->id]) : null;
            $room->activities = $activities->get($room->id, collect());
            $room->staff = $staff->get($room->id, collect());
        }
    }

    /**
     * Nhân sự đang được phân công tại từng phòng lúc này, lấy từ Lịch Công Tác → Sản Xuất (assignments + assignment_personnel).
     * Mỗi phòng: danh sách ca đang diễn ra, mỗi ca kèm danh sách người (giờ riêng từng người nếu có, nếu không theo giờ của ca).
     */
    private function onDutyStaff(Collection $rooms): Collection
    {
        $now = now();
        $depts = $rooms->pluck('deparment_code')->unique()->values()->all();

        return DB::table('assignments as a')
            ->join('assignment_personnel as ap', 'ap.assignment_id', '=', 'a.id')
            ->join('employees as e', 'e.id', '=', 'ap.personnel_id')
            ->whereIn('a.deparment_code', $depts)
            // Giới hạn theo giờ bắt đầu ca để dùng index (deparment_code, start); 1 ca không dài quá 2 ngày
            ->whereBetween('a.start', [$now->copy()->subDays(2), $now->copy()->addDay()])
            ->whereIn('a.room_id', $rooms->pluck('id')->all())
            ->where('a.active', 1)
            ->whereRaw('COALESCE(ap.start, a.start) <= ?', [$now])
            ->whereRaw('COALESCE(ap.end, a.end) > ?', [$now])
            ->orderBy('a.start')
            ->orderBy('a.id')
            ->orderBy('ap.display_order')
            ->get([
                'a.id', 'a.room_id', 'a.Sheet', 'a.start', 'a.end', 'a.Job_description',
                'ap.start as person_start', 'ap.end as person_end', 'ap.operation_type', 'ap.notification',
                'e.code', 'e.name',
            ])
            ->groupBy('room_id')
            ->map(fn($rows) => $rows->groupBy('id')->map(function ($people) {
                $a = $people->first();
                // Mô tả soạn bằng trình soạn thảo HTML: thẻ nào cũng thay bằng khoảng trắng để các dòng không dính nhau
                $job = trim(preg_replace('/\s+/u', ' ', html_entity_decode(preg_replace('/<[^>]*>/', ' ', (string) $a->Job_description))));
                return (object) [
                    'shift'  => self::SHIFT_NAMES[$a->Sheet] ?? ('Ca ' . $a->Sheet),
                    'start'  => $a->start,
                    'end'    => $a->end,
                    // Bỏ mô tả không có chữ (nhiều phân công chỉ ghi "1")
                    'job'    => preg_match('/\p{L}/u', $job) ? $job : '',
                    'people' => $people->map(fn($p) => (object) [
                        'code'  => $p->code,
                        'name'  => $p->name,
                        'start' => $p->person_start ?? $a->start,
                        'end'   => $p->person_end ?? $a->end,
                        'note'  => $p->notification,
                        'type'  => $p->operation_type,
                    ])->values(),
                ];
            })->values());
    }

    /**
     * Lô có thời gian thực tế mới nhất của từng phòng (theo actual_end_clearning / actual_end / actual_start).
     */
    private function latestActualPlans(array $roomIds): Collection
    {
        $ranked = DB::table('stage_plan as sp')
            ->whereIn('sp.resourceId', $roomIds)
            ->where('sp.active', 1)
            ->whereNotNull('sp.actual_start')
            ->whereBetween('sp.stage_code', [1, 7])
            ->select(
                'sp.id',
                'sp.resourceId',
                'sp.actual_start',
                'sp.actual_end',
                'sp.actual_end_clearning',
                'sp.title_clearning',
                DB::raw('ROW_NUMBER() OVER (PARTITION BY sp.resourceId ORDER BY COALESCE(sp.actual_end_clearning, sp.actual_end, sp.actual_start) DESC, sp.id DESC) AS rn')
            );

        return DB::query()->fromSub($ranked, 't')->where('t.rn', 1)->get()->keyBy('resourceId');
    }

    /**
     * Lô kế tiếp theo lịch lý thuyết của từng phòng (chưa bắt đầu, chưa nằm ở phòng nào đang thực thi).
     * Bỏ các lô tồn lịch quá 2 ngày (lô cũ chưa từng chạy) để không che lô sắp chạy thật.
     */
    private function nextPlanIds(array $roomIds): array
    {
        $busy = $this->busyPlanIds();

        $ranked = DB::table('stage_plan as sp')
            ->whereIn('sp.resourceId', $roomIds)
            ->where('sp.active', 1)
            ->where('sp.finished', 0)
            ->whereNull('sp.actual_start')
            ->where('sp.start', '>=', now()->subDays(2))
            ->whereBetween('sp.stage_code', [1, 7])
            ->when($busy, fn($q) => $q->whereNotIn('sp.id', $busy))
            ->select('sp.id', 'sp.resourceId', DB::raw('ROW_NUMBER() OVER (PARTITION BY sp.resourceId ORDER BY sp.start, sp.id) AS rn'));

        return DB::query()->fromSub($ranked, 't')->where('t.rn', 1)->pluck('id', 'resourceId')->all();
    }

    /**
     * Lô đang gắn với 1 phòng đang thực thi (Đang chuẩn bị, Đang SX, Tạm dừng, chờ/đang vệ sinh sau lô).
     */
    private function busyPlanIds(): array
    {
        return DB::table('room_execution_log')
            ->whereNull('ended_at')
            ->whereNull('cancelled_at')
            ->whereNotNull('stage_plan_id')
            ->whereIn('state', [self::PREPARING, self::PRODUCING, self::PAUSED, self::NEED_CLEAN, self::CLEANING])
            ->pluck('stage_plan_id')
            ->all();
    }

    public function planDetails(array $ids): Collection
    {
        $ids = array_values(array_unique(array_filter($ids)));
        if (!$ids) {
            return collect();
        }

        $yields = DB::table('yields')
            ->whereIn('stage_plan_id', $ids)
            ->groupBy('stage_plan_id')
            ->select(
                'stage_plan_id',
                DB::raw('SUM(`yield`) AS total_confirmed'),
                DB::raw('MAX(`end`) AS max_yield_end'),
                // Tổng thời gian chạy của các lần đã xác nhận (BĐCM → KT), để tính thời gian sản xuất thực của lô
                DB::raw('SUM(TIMESTAMPDIFF(SECOND, `start`, `end`)) AS run_seconds')
            );

        $rows = DB::table('stage_plan as sp')
            ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
            ->leftJoin('finished_product_category as fpc', 'sp.product_caterogy_id', '=', 'fpc.id')
            ->leftJoin('intermediate_category as ic', 'fpc.intermediate_code', '=', 'ic.intermediate_code')
            ->leftJoin('product_name as pn', 'ic.product_name_id', '=', 'pn.id')
            ->leftJoin('market as mk', 'fpc.market_id', '=', 'mk.id')
            ->leftJoin('room as r', 'sp.resourceId', '=', 'r.id')
            ->leftJoinSub($yields, 'y', 'y.stage_plan_id', '=', 'sp.id')
            ->whereIn('sp.id', $ids)
            ->select(
                'sp.id',
                'sp.stage_code',
                'sp.title',
                'sp.start',
                'sp.end',
                'sp.title_clearning',
                'sp.resourceId',
                'sp.actual_start',
                'sp.actual_end',
                'sp.Theoretical_yields',
                'sp.number_of_boxes',
                'sp.note',
                'sp.plan_master_id',
                'pn.name as product_name',
                DB::raw('COALESCE(pm.actual_batch, pm.batch) AS batch'),
                'pm.actual_batch',
                'pm.is_val',
                'fpc.intermediate_code',
                'fpc.finished_product_code',
                'mk.code as market',
                'r.code as room_code',
                DB::raw('COALESCE(y.total_confirmed, 0) AS total_confirmed'),
                DB::raw('COALESCE(y.run_seconds, 0) AS run_seconds'),
                'y.max_yield_end'
            )
            ->get();

        foreach ($rows as $row) {
            $row->unit = $row->stage_code <= 4 ? 'Kg' : 'ĐVL';
            $row->label = ($row->product_name ?? $row->title) . ' - ' . $row->batch;
        }

        return $rows->keyBy('id');
    }

    private function stateFromLog(object $log, Carbon $now): object
    {
        $st = $this->makeState(
            (int) $log->state,
            $log->id,
            $log->stage_plan_id,
            $log->started_at,
            $log->cleaning_level,
            $log->expired_at,
            $log->note,
            false,
            $now
        );
        // Lúc thao tác tạo ra trạng thái này (giờ hệ thống), để giới hạn thời gian Hủy thao tác
        $st->acted_at = $log->created_at ? Carbon::parse($log->created_at) : null;

        return $st;
    }

    private function deriveState(?object $sp, Carbon $now): object
    {
        if (!$sp) {
            return $this->makeState(self::NEED_CLEAN, null, null, null, null, null,
                'Chưa có dữ liệu thực tế của phòng, mặc định cần vệ sinh', true, $now);
        }

        if ($sp->actual_end_clearning) {
            $level = $this->levelOf($sp->title_clearning);

            return $this->makeState(self::CLEAN, null, $sp->id, $sp->actual_end_clearning, $level,
                $this->expiry(Carbon::parse($sp->actual_end_clearning), $level), null, true, $now);
        }

        return $this->makeState(self::PAUSED, null, $sp->id, $sp->actual_end ?? $sp->actual_start, null, null,
            'Lô đã xác nhận sản xuất (✓) ở trang Xác nhận hoàn thành nhưng chưa xác nhận vệ sinh', true, $now);
    }

    private function makeState(int $state, $logId, $spId, $since, $level, $expiredAt, $note, bool $derived, Carbon $now): object
    {
        $since = $since ? Carbon::parse($since) : null;
        $expiredAt = $expiredAt ? Carbon::parse($expiredAt) : null;
        $display = ($state === self::CLEAN && $expiredAt && $now->gte($expiredAt)) ? self::EXPIRED : $state;

        return (object) [
            'state'          => $state,
            'display'        => $display,
            'label'          => self::STATE_LABELS[$display],
            'log_id'         => $logId,
            'stage_plan_id'  => $spId,
            'since'          => $since,
            'cleaning_level' => $level,
            'expired_at'     => $expiredAt,
            'note'           => $note,
            'derived'        => $derived,
            'acted_at'       => null, // lúc thao tác, gán ở stateFromLog (trạng thái dẫn xuất không có)
            // Trình duyệt gửi lại token khi thao tác; khác token hiện tại nghĩa là phòng vừa bị người khác đổi trạng thái
            'token'          => implode('|', [$display, $logId ?? 'd', $spId ?? '-', $since ? $since->format('YmdHis') : '-']),
            'plan'           => null,
        ];
    }

    /**
     * Hạn Hủy thao tác của trạng thái hiện tại (null = không hủy được): UNDO_SECONDS kể từ lúc thao tác.
     */
    public static function undoUntil(object $st): ?Carbon
    {
        if ($st->derived || !$st->acted_at || !in_array($st->state, self::UNDO_STATES, true)) {
            return null;
        }

        return $st->acted_at->copy()->addSeconds(self::UNDO_SECONDS);
    }

    public function levelOf(?string $titleClearning): string
    {
        return in_array($titleClearning, ['VS-I', 'VS-II'], true) ? $titleClearning : 'VS-I';
    }

    private function expiry(Carbon $since, string $level): Carbon
    {
        return $since->copy()->addHours(self::CLEAN_HOLD_HOURS[$level] ?? self::CLEAN_HOLD_HOURS['VS-I']);
    }

    /* =========================================================
       DANH SÁCH LÔ ĐỂ MỞ PHÒNG
       ========================================================= */

    /**
     * Lô được phép bắt đầu ở phòng: cùng công đoạn, chưa xác nhận vệ sinh (✓✓), chưa gắn với phòng khác đang thực thi.
     * scope 'room' = lô đã sắp vào phòng này; 'stage' = mọi phòng cùng công đoạn (chạy lô ở phòng khác lịch).
     */
    private function candidateQuery(object $room, string $scope)
    {
        $weighing = in_array((int) $room->stage_code, [1, 2], true);
        $stageCodes = $weighing ? [1, 2] : [(int) $room->stage_code];
        $busy = $this->busyPlanIds();

        $roomIds = $scope === 'stage'
            ? DB::table('room')
                ->where('deparment_code', $room->deparment_code)
                ->where('active', 1)
                ->whereIn('stage_code', $stageCodes)
                ->pluck('id')
                ->all()
            : [$room->id];

        return DB::table('stage_plan as sp')
            ->where('sp.active', 1)
            ->where('sp.deparment_code', $room->deparment_code)
            ->whereIn('sp.stage_code', $stageCodes)
            ->whereNull('sp.actual_start_clearning')
            // chưa bắt đầu, hoặc đã xác nhận 1 phần (✓) — lô đã chạy dở thì chỉ tiếp tục ở chính phòng đó
            ->where(fn($q) => $q->where(fn($q2) => $q2->where('sp.finished', 0)->whereNull('sp.actual_start'))
                ->orWhere(fn($q2) => $q2->whereNotNull('sp.actual_start')->where('sp.resourceId', $room->id)))
            ->where(function ($q) use ($roomIds, $weighing) {
                $q->whereIn('sp.resourceId', $roomIds);
                if ($weighing) {
                    $q->orWhereNull('sp.resourceId'); // Cân NL: lô chưa gán phòng vẫn chọn được
                }
            })
            // Chưa sắp lịch thì không được thực hiện, trừ Cân NL / Cân NL Khác (giống trang Xác nhận hoàn thành)
            ->when(!$weighing, fn($q) => $q->whereNotNull('sp.start'))
            ->when($busy, fn($q) => $q->whereNotIn('sp.id', $busy));
    }

    public function candidatePlans(object $room, string $scope): Collection
    {
        $ids = $this->candidateQuery($room, $scope)
            ->orderByRaw('sp.resourceId = ? DESC', [$room->id])
            ->orderByRaw('sp.start IS NULL')
            // lô có lịch gần thời điểm hiện tại nhất lên đầu
            ->orderByRaw('ABS(TIMESTAMPDIFF(MINUTE, sp.start, NOW()))')
            ->orderBy('sp.id')
            ->limit(150)
            ->pluck('sp.id')
            ->all();

        $details = $this->planDetails($ids);

        return collect($ids)->map(fn($id) => $details->get($id))->filter()->values();
    }

    /* =========================================================
       CHUYỂN TRẠNG THÁI
       ========================================================= */

    /**
     * Phòng Sạch → Đang Chuẩn Bị (mode 'prepare') hoặc Đang SX (mode 'execute'). Cả hai đều là BĐSX của lô;
     * 'execute' thì BĐCM của lần xác nhận sản lượng đầu tiên = BĐSX.
     */
    public function start(int $roomId, ?string $token, int $stagePlanId, ?string $mode, ?string $time, string $user): string
    {
        if (!isset(self::START_MODES[$mode])) {
            throw new ProductionExecutionException('❌ Chọn Chuẩn bị hoặc Thực thi sản xuất');
        }

        return $this->transition($roomId, $token, function ($room, $st) use ($stagePlanId, $mode, $time, $user) {
            if ($st->display === self::EXPIRED) {
                throw new ProductionExecutionException('❌ Phòng đã quá hạn sạch, cần vệ sinh lại trước khi sản xuất');
            }
            if ($st->state !== self::CLEAN) {
                throw new ProductionExecutionException('❌ Chỉ mở phòng khi phòng đang ở trạng thái Phòng Sạch');
            }
            if (!$this->candidateQuery($room, 'stage')->where('sp.id', $stagePlanId)->exists()) {
                throw new ProductionExecutionException('❌ Lô không hợp lệ, đã hoàn thành hoặc đang được thực hiện ở phòng khác');
            }

            $plan = $this->planDetails([$stagePlanId])->get($stagePlanId);
            $at = $this->parseTime($time, 'Thời gian bắt đầu sản xuất (BĐSX)', $st->since);

            if ($st->expired_at && $at->gte($st->expired_at)) {
                throw new ProductionExecutionException('❌ Phòng đã hết hạn sạch lúc ' . $st->expired_at->format('H:i d/m/Y') . ', cần vệ sinh lại');
            }
            if ($plan->max_yield_end && $at->lt(Carbon::parse($plan->max_yield_end))) {
                throw new ProductionExecutionException('❌ BĐSX không được nhỏ hơn thời gian kết thúc lần xác nhận sản lượng trước của lô ('
                    . Carbon::parse($plan->max_yield_end)->format('H:i d/m/Y') . ')');
            }

            $this->closeCurrent($room, $st, $at, $user);
            $this->openLog($room, self::START_MODES[$mode], $at, $user, ['stage_plan_id' => $stagePlanId]);

            return ($mode === 'prepare' ? '✅ Đã mở phòng, bắt đầu chuẩn bị ' : '✅ Đã bắt đầu sản xuất ') . $plan->label;
        });
    }

    /** Đang Chuẩn Bị → Đang SX (BĐCM: bắt đầu tạo ra sản lượng) */
    public function execute(int $roomId, ?string $token, ?string $time, string $user): string
    {
        return $this->transition($roomId, $token, function ($room, $st) use ($time, $user) {
            if ($st->state !== self::PREPARING) {
                throw new ProductionExecutionException('❌ Phòng không ở trạng thái Đang Chuẩn Bị');
            }

            $at = $this->parseTime($time, 'Thời gian bắt đầu tạo ra sản lượng (BĐCM)', $st->since);

            $this->closeCurrent($room, $st, $at, $user);
            $this->openLog($room, self::PRODUCING, $at, $user, ['stage_plan_id' => $st->stage_plan_id]);

            $plan = $this->planDetails([$st->stage_plan_id])->get($st->stage_plan_id);

            return '✅ Đã bắt đầu thực thi sản xuất' . ($plan ? ' ' . $plan->label : '');
        });
    }

    /** Đang SX → Tạm dừng (KT + sản lượng, = ✓) */
    public function pause(int $roomId, ?string $token, array $in, string $user): string
    {
        return $this->transition($roomId, $token, function ($room, $st) use ($in, $user) {
            if ($st->state !== self::PRODUCING) {
                throw new ProductionExecutionException('❌ Phòng không ở trạng thái Đang Sản Xuất');
            }

            $end = $this->recordSegment($room, $st, $in, $user);
            $this->openLog($room, self::PAUSED, $end, $user, [
                'stage_plan_id' => $st->stage_plan_id,
                'note'          => $this->text($in['reason'] ?? null),
            ]);

            return '✅ Đã tạm dừng sản xuất và ghi nhận sản lượng';
        });
    }

    /** Tạm dừng → Đang SX (bắt đầu lại) */
    public function resume(int $roomId, ?string $token, ?string $time, string $user): string
    {
        return $this->transition($roomId, $token, function ($room, $st) use ($time, $user) {
            if ($st->state !== self::PAUSED) {
                throw new ProductionExecutionException('❌ Phòng không ở trạng thái Tạm Dừng SX');
            }

            $plan = $this->planDetails([$st->stage_plan_id])->get($st->stage_plan_id);
            $min = $st->since;
            if ($plan && $plan->max_yield_end && (!$min || Carbon::parse($plan->max_yield_end)->gt($min))) {
                $min = Carbon::parse($plan->max_yield_end);
            }
            $at = $this->parseTime($time, 'Thời gian bắt đầu lại', $min);

            $this->closePause($room, $st, $at, $user);
            $this->openLog($room, self::PRODUCING, $at, $user, ['stage_plan_id' => $st->stage_plan_id]);

            return '✅ Đã bắt đầu lại sản xuất';
        });
    }

    /** Đang SX / Tạm dừng → Cần VS (kết thúc lô) */
    public function finish(int $roomId, ?string $token, array $in, string $user): string
    {
        return $this->transition($roomId, $token, function ($room, $st) use ($in, $user) {
            if ($st->state === self::PRODUCING) {
                $end = $this->recordSegment($room, $st, $in, $user);
            } elseif ($st->state === self::PAUSED) {
                // Sản lượng đã ghi lúc tạm dừng, chỉ khép lô
                $end = $this->parseTime($in['time'] ?? null, 'Thời gian kết thúc', $st->since);
                $this->closePause($room, $st, $end, $user);
            } elseif ($st->state === self::PREPARING) {
                // Lô chỉ kết thúc được sau khi đã thực thi (luôn có ít nhất 1 lần sản lượng BĐCM → KT)
                throw new ProductionExecutionException('❌ Lô đang chuẩn bị, chưa thực thi sản xuất: bấm Thực thi sản xuất trước, '
                    . 'hoặc Hủy thao tác nếu không chạy lô này');
            } else {
                throw new ProductionExecutionException('❌ Phòng không có lô đang sản xuất');
            }

            $plan = $this->planDetails([$st->stage_plan_id])->get($st->stage_plan_id);
            $this->openLog($room, self::NEED_CLEAN, $end, $user, [
                'stage_plan_id'  => $st->stage_plan_id,
                'cleaning_level' => $this->levelOf($plan->title_clearning ?? null),
            ]);

            return '✅ Đã kết thúc sản xuất, phòng chuyển sang Cần Vệ Sinh';
        });
    }

    /** Cần VS / Cần VS lại → Đang VS */
    public function startCleaning(int $roomId, ?string $token, ?string $time, ?string $level, string $user): string
    {
        return $this->transition($roomId, $token, function ($room, $st) use ($time, $level, $user) {
            if (!in_array($st->display, [self::NEED_CLEAN, self::EXPIRED], true)) {
                throw new ProductionExecutionException('❌ Phòng không ở trạng thái Cần Vệ Sinh');
            }
            if (!isset(self::CLEANING_LEVELS[$level])) {
                throw new ProductionExecutionException('❌ Chọn cấp vệ sinh');
            }

            $at = $this->parseTime($time, 'Thời gian bắt đầu vệ sinh', $st->since);

            $this->closeCurrent($room, $st, $at, $user);
            $this->openLog($room, self::CLEANING, $at, $user, [
                // Vệ sinh sau lô thì gắn lô để ghi actual_*_clearning; vệ sinh lại phòng quá hạn thì không gắn lô
                'stage_plan_id'  => $st->state === self::NEED_CLEAN ? $st->stage_plan_id : null,
                'cleaning_level' => $level,
            ]);

            return '✅ Đã bắt đầu vệ sinh';
        });
    }

    /**
     * Đang VS → Phòng Sạch (= ✓✓ nếu vệ sinh sau lô).
     * Vệ sinh sau lô + công tắc tịnh tuyến của phân xưởng đang bật thì tịnh tuyến lịch lý thuyết sau khi đã lưu,
     * lỗi tịnh tuyến không làm mất xác nhận vệ sinh (giống trang Xác nhận hoàn thành).
     *
     * @return array{message: string, reroute: ?array}
     */
    public function endCleaning(int $roomId, ?string $token, ?string $time, ?string $note, string $user): array
    {
        $cleanedPlanId = null;
        $deparmentCode = null;

        $message = $this->transition($roomId, $token, function ($room, $st) use ($time, $note, $user, &$cleanedPlanId, &$deparmentCode) {
            if ($st->state !== self::CLEANING) {
                throw new ProductionExecutionException('❌ Phòng không ở trạng thái Đang Vệ Sinh');
            }

            $at = $this->parseTime($time, 'Thời gian kết thúc vệ sinh', $st->since, true);
            $level = $st->cleaning_level ?: 'VS-I';
            $note = $this->text($note);

            if ($st->stage_plan_id) {
                $conflict = $this->overlapConflict($room, $st->stage_plan_id, $st->since, $at);
                if ($conflict) {
                    throw new ProductionExecutionException('❌ Thời gian vệ sinh bị trùng giờ với lô "' . $conflict->title
                        . '" (' . $this->conflictRange($conflict) . ') trên cùng phòng sản xuất, vui lòng kiểm tra lại!');
                }
            }

            $logId = $this->closeCurrent($room, $st, $at, $user);

            if ($st->stage_plan_id) {
                DB::table('stage_plan')->where('id', $st->stage_plan_id)->update([
                    'actual_start_clearning' => $st->since,
                    'actual_end_clearning'   => $at,
                    'finished_by'            => $user,
                    'finished_date'          => now(),
                    'finished'               => 1,
                ]);
                if ($note) {
                    DB::table('room_execution_log')->where('id', $logId)->update(['note' => $note]);
                }
            } else {
                // Vệ sinh không gắn lô: ghi vào Báo cáo ngày như 1 hoạt động của phòng
                $rsId = $this->insertActivity($room, $level === 'VS-LAI' ? 'Vệ sinh lại' : $level, $st->since, $at, $note, $user);
                DB::table('room_execution_log')->where('id', $logId)->update(['room_status_id' => $rsId, 'note' => $note]);
            }

            $this->openLog($room, self::CLEAN, $at, $user, [
                'stage_plan_id'  => $st->stage_plan_id,
                'cleaning_level' => $level,
                'expired_at'     => $this->expiry($at, $level),
            ]);

            $cleanedPlanId = $st->stage_plan_id;
            $deparmentCode = $room->deparment_code;

            return '✅ Đã kết thúc vệ sinh, phòng sạch đến ' . $this->expiry($at, $level)->format('H:i d/m/Y');
        });

        $reroute = null;
        if ($cleanedPlanId && RealtimeRerouteSwitch::enabled($deparmentCode)) {
            try {
                $result = app(ScheduleRerouteService::class)->reroute((int) $cleanedPlanId);
                $reroute = [
                    'delta'          => $result['delta_minutes'],
                    'count'          => count($result['changes']),
                    'cleaning_moved' => $result['source_cleaning_moved'],
                ];
            } catch (\Throwable $e) {
                Log::error('[Reroute] Tịnh tuyến thất bại cho stage_plan ' . $cleanedPlanId, [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $reroute = ['error' => true];
            }
        }

        return ['message' => $message, 'reroute' => $reroute];
    }

    /** Phòng Sạch → Cần VS (ví dụ sau bảo trì, sự cố), không gắn lô */
    public function markDirty(int $roomId, ?string $token, ?string $time, ?string $reason, string $user): string
    {
        return $this->transition($roomId, $token, function ($room, $st) use ($time, $reason, $user) {
            if (!in_array($st->display, [self::CLEAN, self::EXPIRED], true)) {
                throw new ProductionExecutionException('❌ Chỉ chuyển Cần Vệ Sinh khi phòng đang sạch');
            }
            $reason = $this->text($reason);
            if (!$reason) {
                throw new ProductionExecutionException('❌ Nhập lý do chuyển phòng sang Cần Vệ Sinh');
            }

            $at = $this->parseTime($time, 'Thời gian', $st->since);
            $this->closeCurrent($room, $st, $at, $user);
            $this->openLog($room, self::NEED_CLEAN, $at, $user, ['note' => $reason]);

            return '✅ Phòng đã chuyển sang Cần Vệ Sinh';
        });
    }

    /**
     * Hủy thao tác vừa rồi. Chỉ hủy được các thao tác chưa ghi gì vào stage_plan/yields:
     * Mở phòng (chuẩn bị / bắt đầu SX), Thực thi sản xuất, Bắt đầu lại, Bắt đầu vệ sinh — trong 2 phút kể từ lúc thao tác.
     */
    public function undo(int $roomId, ?string $token, string $user): string
    {
        return $this->transition($roomId, $token, function ($room, $st) use ($user) {
            if ($st->derived || !in_array($st->state, self::UNDO_STATES, true)) {
                throw new ProductionExecutionException('❌ Chỉ hủy được thao tác Mở phòng / Thực thi sản xuất / Bắt đầu lại / Bắt đầu vệ sinh vừa thực hiện');
            }
            $until = self::undoUntil($st);
            if (!$until || now()->gte($until)) {
                // 409 để trình duyệt nhận card mới (không còn nút Hủy thao tác)
                throw new ProductionExecutionException('❌ Đã quá ' . (self::UNDO_SECONDS / 60) . ' phút kể từ lúc thao tác'
                    . ($st->acted_at ? ' (' . $st->acted_at->format('H:i:s') . ')' : '') . ', không hủy được nữa', 409);
            }

            $prev = DB::table('room_execution_log')
                ->where('room_id', $room->id)
                ->whereNull('cancelled_at')
                ->where('id', '<', $st->log_id)
                ->orderByDesc('id')
                ->first();

            DB::table('room_execution_log')->where('id', $st->log_id)->update([
                'cancelled_at' => now(),
                'cancelled_by' => $user,
                'updated_at'   => now(),
            ]);

            if ($prev) {
                // Bắt đầu lại đã ghi khoảng tạm dừng vào Báo cáo ngày → hủy luôn dòng đó
                if ($prev->room_status_id) {
                    DB::table('room_status')->where('id', $prev->room_status_id)->update(['active' => 0]);
                }
                DB::table('room_execution_log')->where('id', $prev->id)->update([
                    'ended_at'       => null,
                    'ended_by'       => null,
                    'room_status_id' => null,
                    'updated_at'     => now(),
                ]);
            }

            return '✅ Đã hủy thao tác vừa rồi';
        });
    }

    /* =========================================================
       HOẠT ĐỘNG KHÁC (room_status → Báo cáo ngày)
       ========================================================= */

    /**
     * Khai báo hoạt động khác. Chạy như 1 lần chuyển trạng thái (khóa phòng + token) để không lọt vào
     * khoảng BĐSX → KT của lô khi có người vừa mở phòng cùng lúc.
     */
    public function addActivity(int $roomId, ?string $token, array $in, string $user): string
    {
        $name = $this->text($in['in_production'] ?? null);
        if (!$name) {
            throw new ProductionExecutionException('❌ Hoạt động không được để trống');
        }

        return $this->transition($roomId, $token, function ($room, $st) use ($name, $in, $user) {
            if (in_array($st->state, self::BATCH_RUNNING_STATES, true)) {
                throw new ProductionExecutionException('❌ Phòng đang có lô (' . $st->label
                    . '): từ BĐSX đến khi Kết thúc SX không được thêm hoạt động khác');
            }

            $start = $this->parseTime($in['start'] ?? null, 'Thời gian bắt đầu', null);
            $end = !empty($in['end']) ? $this->parseTime($in['end'], 'Thời gian kết thúc', $start, true) : null;

            $this->insertActivity($room, $name, $start, $end, $this->text($in['notification'] ?? null), $user);

            return $end ? '✅ Đã khai báo hoạt động' : '✅ Đã khai báo hoạt động đang diễn ra';
        });
    }

    public function endActivity(object $activity, ?string $time): string
    {
        $at = $this->parseTime($time, 'Thời gian kết thúc', Carbon::parse($activity->start), true);
        DB::table('room_status')->where('id', $activity->id)->whereNull('end')->update(['end' => $at]);

        return '✅ Đã kết thúc hoạt động "' . $activity->in_production . '"';
    }

    /* =========================================================
       LỊCH SỬ
       ========================================================= */

    public function history(int $roomId): array
    {
        $fmt = fn($t) => $t ? Carbon::parse($t)->format('H:i d/m/Y') : null;

        $logs = DB::table('room_execution_log as l')
            ->leftJoin('stage_plan as sp', 'l.stage_plan_id', '=', 'sp.id')
            ->where('l.room_id', $roomId)
            ->orderByDesc('l.started_at')
            ->orderByDesc('l.id')
            ->limit(60)
            ->get(['l.*', 'sp.title as plan_title'])
            ->map(fn($l) => [
                'kind'      => 'state',
                'label'     => self::STATE_LABELS[$l->state] ?? $l->state,
                'state'     => (int) $l->state,
                'detail'    => trim(($l->plan_title ?? '') . ($l->cleaning_level ? ' · ' . $l->cleaning_level : '') . ($l->note ? ' · ' . $l->note : ''), ' ·'),
                'start'     => $fmt($l->started_at),
                'end'       => $fmt($l->ended_at),
                'by'        => $l->created_by,
                'end_by'    => $l->ended_by,
                'cancelled' => $l->cancelled_at ? 'Đã hủy bởi ' . $l->cancelled_by . ' lúc ' . $fmt($l->cancelled_at) : null,
                'sort'      => $l->started_at,
            ]);

        $activities = DB::table('room_status')
            ->where('room_id', $roomId)
            ->where('is_daily_report', 1)
            ->where('active', 1)
            ->whereNotNull('start')
            ->orderByDesc('start')
            ->limit(60)
            ->get()
            ->map(fn($a) => [
                'kind'      => 'activity',
                'label'     => $a->in_production,
                'state'     => null,
                'detail'    => $a->notification !== 'NA' ? $a->notification : '',
                'start'     => $fmt($a->start),
                'end'       => $fmt($a->end),
                'by'        => $a->created_by,
                'end_by'    => null,
                'cancelled' => null,
                'sort'      => $a->start,
            ]);

        return $logs->concat($activities)->sortByDesc('sort')->take(80)->values()->all();
    }

    /* =========================================================
       NỘI BỘ
       ========================================================= */

    /**
     * Chạy 1 lần chuyển trạng thái trong transaction, khóa dòng phòng để 2 người bấm cùng lúc phải xếp hàng,
     * và từ chối nếu trạng thái phòng đã khác với lúc người dùng nhìn thấy (token).
     */
    private function transition(int $roomId, ?string $token, callable $fn): string
    {
        return DB::transaction(function () use ($roomId, $token, $fn) {
            $room = DB::table('room')->where('id', $roomId)->lockForUpdate()->first();
            if (!$room || !$room->active || $room->stage_code < 1 || $room->stage_code > 7) {
                throw new ProductionExecutionException('❌ Không tìm thấy phòng sản xuất', 404);
            }

            $this->attachStates(collect([$room]));

            if ($token !== $room->st->token) {
                throw new ProductionExecutionException('⚠️ Trạng thái phòng vừa thay đổi (có thể do người khác thao tác). '
                    . 'Đã tải lại trạng thái mới, vui lòng kiểm tra rồi thao tác lại.', 409);
            }

            return $fn($room, $room->st);
        });
    }

    /**
     * Ghi 1 lần xác nhận sản lượng (BĐCM → KT) giống nút ✓ của trang Xác nhận hoàn thành, rồi đóng khoảng Đang SX.
     */
    private function recordSegment(object $room, object $st, array $in, string $user): Carbon
    {
        $sp = DB::table('stage_plan')->where('id', $st->stage_plan_id)->lockForUpdate()->first();
        if (!$sp) {
            throw new ProductionExecutionException('❌ Không tìm thấy lô đang sản xuất');
        }

        $end = $this->parseTime($in['end'] ?? null, 'Thời gian kết thúc (KT)', $st->since, true);

        $startYield = !empty($in['start_yield']) ? $this->parseTime($in['start_yield'], 'BĐCM', null) : $st->since->copy();
        if ($startYield->lt($st->since)) {
            throw new ProductionExecutionException('❌ BĐCM không được nhỏ hơn thời điểm bắt đầu sản xuất / bắt đầu lại ('
                . $st->since->format('H:i d/m/Y') . ')');
        }
        if ($startYield->gte($end)) {
            throw new ProductionExecutionException('❌ BĐCM phải nhỏ hơn thời gian kết thúc (KT)');
        }

        $raw = str_replace(',', '.', trim((string) ($in['yields'] ?? '')));
        if ($raw === '' || !is_numeric($raw) || (float) $raw < 0) {
            throw new ProductionExecutionException('❌ Nhập sản lượng của lần này (số ≥ 0)');
        }
        $yield = round((float) $raw, 5);

        $previous = (float) DB::table('yields')->where('stage_plan_id', $sp->id)->sum('yield');
        $total = $previous + $yield;
        if ($sp->Theoretical_yields > 0 && $total > $sp->Theoretical_yields * 1.05) {
            throw new ProductionExecutionException('❌ Sản Lượng Không Vượt Quá 105% Sản Lượng Lý Thuyết (lý thuyết '
                . round($sp->Theoretical_yields, 2) . ', đã xác nhận ' . round($previous, 2) . ')');
        }

        $overlapYield = DB::table('yields')
            ->where('stage_plan_id', $sp->id)
            ->where('start', '<', $end)
            ->where('end', '>', $startYield)
            ->exists();
        if ($overlapYield) {
            throw new ProductionExecutionException('❌ Khoảng BĐCM - KT bị chồng lấp với các lần xác nhận sản lượng trước của lô, vui lòng kiểm tra lại');
        }

        // BĐSX của lô = lần mở phòng đầu tiên, kể cả khi mở để chuẩn bị (các lần bắt đầu lại / thực thi chỉ là BĐCM)
        $firstStart = DB::table('room_execution_log')
            ->where('stage_plan_id', $sp->id)
            ->whereIn('state', [self::PREPARING, self::PRODUCING])
            ->whereNull('cancelled_at')
            ->min('started_at');
        $batchStart = Carbon::parse($sp->actual_start ?? $firstStart ?? $st->since);

        $conflict = $this->overlapConflict($room, $sp->id, $batchStart, $end);
        if ($conflict) {
            throw new ProductionExecutionException('❌ Thời gian sản xuất bị trùng giờ với lô "' . $conflict->title
                . '" (' . $this->conflictRange($conflict) . ') trên cùng phòng sản xuất, vui lòng kiểm tra lại!');
        }

        $update = [
            'resourceId'      => $room->id,
            'actual_start'    => $batchStart,
            'actual_end'      => $end,
            'yields'          => $total,
            'number_of_boxes' => max(1, (int) ($in['number_of_boxes'] ?? $sp->number_of_boxes ?? 1)),
            'finished_by'     => $user,
            'finished_date'   => now(),
            'finished'        => 1,
        ];

        $note = $this->text($in['note'] ?? null);
        if ($note) {
            $update['note'] = $note;
        }

        // THT: quy đổi sản lượng (kg) ra đơn vị lô theo tổng đã xác nhận
        if ((int) $sp->stage_code === 4 && $sp->Theoretical_yields > 0) {
            $batchQty = DB::table('finished_product_category')->where('id', $sp->product_caterogy_id)->value('batch_qty');
            $update['yields_batch_qty'] = round(($total / $sp->Theoretical_yields) * $batchQty, 2);
        }

        if ((int) $sp->stage_code <= 2) {
            $update['quarantine_room_code'] = 'W14';
        }

        DB::table('stage_plan')->where('id', $sp->id)->update($update);

        $yieldId = DB::table('yields')->insertGetId([
            'stage_plan_id' => $sp->id,
            'start'         => $startYield,
            'end'           => $end,
            'yield'         => $yield,
            'created_by'    => $user,
            'created_date'  => now(),
        ]);

        // Cân NL: số lô thực tế chỉ sửa ở lần xác nhận đầu tiên (giống trang Xác nhận hoàn thành)
        $actualBatch = $this->text($in['actual_batch'] ?? null);
        if ($actualBatch && (int) $sp->stage_code === 1 && !$sp->actual_start) {
            DB::table('plan_master')
                ->where('main_parkaging_id', $sp->plan_master_id)
                ->update(['actual_batch' => $actualBatch, 'weighed' => 1]);
        }

        $logId = $this->closeCurrent($room, $st, $end, $user);
        DB::table('room_execution_log')->where('id', $logId)->update(['yield_id' => $yieldId]);

        return $end;
    }

    /**
     * Đóng khoảng tạm dừng; khoảng tạm dừng thật (không phải dẫn xuất) ghi vào Báo cáo ngày kèm lý do.
     */
    private function closePause(object $room, object $st, Carbon $at, string $user): void
    {
        $logId = $this->closeCurrent($room, $st, $at, $user);

        if ($st->derived || !$logId || !$st->since || $at->lte($st->since)) {
            return;
        }

        $plan = $this->planDetails([$st->stage_plan_id])->get($st->stage_plan_id);
        $rsId = $this->insertActivity(
            $room,
            'Tạm dừng SX',
            $st->since,
            $at,
            ($st->note ?: 'Không ghi lý do') . ($plan ? ' - ' . $plan->label : ''),
            $user
        );

        DB::table('room_execution_log')->where('id', $logId)->update(['room_status_id' => $rsId]);
    }

    /**
     * Đóng trạng thái hiện tại tại thời điểm $at. Trạng thái đang dẫn xuất thì lưu thành 1 dòng đã đóng
     * để lịch sử liền mạch và "Hủy thao tác" có dòng để mở lại. Trả về id dòng log vừa đóng.
     */
    private function closeCurrent(object $room, object $st, Carbon $at, string $user): ?int
    {
        // Đóng cả log lỗi thời (nếu có) để phòng chỉ còn 1 trạng thái mở
        DB::table('room_execution_log')
            ->where('room_id', $room->id)
            ->whereNull('ended_at')
            ->whereNull('cancelled_at')
            ->update([
                'ended_at'   => DB::raw("GREATEST(started_at, '" . $at->format('Y-m-d H:i:s') . "')"),
                'ended_by'   => $user,
                'updated_at' => now(),
            ]);

        if ($st->log_id || !$st->since) {
            return $st->log_id;
        }

        return $this->openLog($room, $st->state, $st->since, 'Hệ thống', [
            'stage_plan_id'  => $st->stage_plan_id,
            'cleaning_level' => $st->cleaning_level,
            'expired_at'     => $st->expired_at,
            'note'           => 'Khởi tạo từ dữ liệu Xác nhận hoàn thành',
            'ended_at'       => $at,
            'ended_by'       => $user,
        ]);
    }

    private function openLog(object $room, int $state, $startedAt, string $user, array $extra = []): int
    {
        return DB::table('room_execution_log')->insertGetId(array_merge([
            'room_id'        => $room->id,
            'deparment_code' => $room->deparment_code,
            'state'          => $state,
            'started_at'     => $startedAt,
            'created_by'     => $user,
            'created_at'     => now(),
            'updated_at'     => now(),
        ], $extra));
    }

    private function insertActivity(object $room, string $name, $start, $end, ?string $note, string $user): int
    {
        return DB::table('room_status')->insertGetId([
            'room_id'         => $room->id,
            'status'          => 1,
            'in_production'   => mb_substr($name, 0, 255),
            'start'           => $start,
            'end'             => $end,
            'notification'    => $note ? mb_substr($note, 0, 255) : 'NA',
            'is_daily_report' => 1,
            'from_execution'  => 1, // Báo cáo ngày không được sửa/xóa
            'deparment_code'  => $room->deparment_code,
            'created_by'      => $user,
            'created_at'      => now(),
        ]);
    }

    /**
     * Lô khác trùng giờ thực tế trên cùng phòng + công đoạn (cùng quy tắc với trang Xác nhận hoàn thành:
     * bỏ qua Cân NL, Cân NL Khác, Pha Chế, THT).
     */
    private function overlapConflict(object $room, int $excludeId, Carbon $start, Carbon $end): ?object
    {
        if (in_array((int) $room->stage_code, [1, 2, 3, 4], true)) {
            return null;
        }

        return DB::table('stage_plan as sp')
            ->select('sp.id', 'sp.title', 'sp.actual_start', 'sp.actual_end', 'sp.actual_end_clearning')
            ->where('sp.resourceId', $room->id)
            ->where('sp.stage_code', $room->stage_code)
            ->where('sp.active', 1)
            ->whereNotNull('sp.actual_start')
            ->whereNotNull('sp.actual_end')
            ->where('sp.id', '!=', $excludeId)
            ->where('sp.actual_start', '<', $end)
            ->whereRaw('COALESCE(sp.actual_end_clearning, sp.actual_end) > ?', [$start])
            ->first();
    }

    private function conflictRange(object $conflict): string
    {
        return Carbon::parse($conflict->actual_start)->format('H:i d/m/Y')
            . ' - ' . Carbon::parse($conflict->actual_end_clearning ?? $conflict->actual_end)->format('H:i d/m/Y');
    }

    /**
     * Thời gian người dùng nhập (mặc định là bây giờ), làm tròn xuống phút.
     * Không được lớn hơn hiện tại; không nhỏ hơn $min (hoặc phải lớn hơn hẳn nếu $strict).
     */
    private function parseTime(?string $value, string $label, ?Carbon $min, bool $strict = false): Carbon
    {
        try {
            // Không truyền giờ = giờ hệ thống, giữ cả giây: thao tác liên tiếp trong cùng 1 phút (bắt đầu rồi tạm dừng ngay)
            // vẫn có KT > BĐSX. Giờ nhập tay (công cụ nội bộ / kiểm thử) làm tròn phút như trang Xác nhận hoàn thành.
            $at = $value ? Carbon::parse($value)->startOfMinute() : now();
        } catch (\Throwable $e) {
            throw new ProductionExecutionException("❌ $label không hợp lệ");
        }

        if ($at->gt(now())) {
            throw new ProductionExecutionException("❌ $label lớn hơn thời gian hiện tại");
        }

        if ($min && ($strict ? $at->lte($min) : $at->lt($min))) {
            throw new ProductionExecutionException("❌ $label phải " . ($strict ? 'lớn hơn ' : 'từ ')
                . $min->format('H:i d/m/Y') . ($strict ? '' : ' trở đi'));
        }

        return $at;
    }

    private function text($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, 255);
    }
}
