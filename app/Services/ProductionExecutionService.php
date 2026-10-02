<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use Illuminate\Support\Facades\Log;

/**
 * Thực thi sản xuất theo phòng (trang "Thực Thi Sản Xuất").
 *
 * Máy trạng thái của phòng:
 *   Phòng Sạch → (Mở phòng, chọn lô) Đang SX ⇄ Tạm Dừng SX → (Kết thúc SX) Cần VS → Đang VS → Chờ Kiểm Tra
 *   Chờ Kiểm Tra → (người khác kiểm tra, xác thực lại tài khoản) Đạt → Phòng Sạch / Không đạt → Đang VS
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
    const AWAIT_CHECK = 8; // Đã kết thúc vệ sinh, chờ người khác kiểm tra (Đạt → Phòng Sạch, Không đạt → tiếp tục vệ sinh)

    const STATE_LABELS = [
        self::CLEAN      => 'Đã Vệ Sinh',
        self::PRODUCING  => 'Đang Sản Xuất',
        self::NEED_CLEAN => 'Cần Vệ Sinh',
        self::CLEANING   => 'Đang Vệ Sinh',
        self::EXPIRED    => 'Cần Vệ Sinh Lại',
        self::PAUSED     => 'Tạm Dừng SX',
        self::PREPARING  => 'Đang Chuẩn Bị',
        self::AWAIT_CHECK => 'Chờ Kiểm Tra',
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
        self::AWAIT_CHECK => ['checking', 'fa-user-check'],
    ];

    // Thứ tự trạng thái trên bộ lọc và bộ đếm (trang Thực Thi SX, trang công khai)
    const DISPLAY_ORDER = [self::PREPARING, self::PRODUCING, self::PAUSED, self::NEED_CLEAN, self::CLEANING, self::AWAIT_CHECK, self::CLEAN, self::EXPIRED];

    // Mở phòng: chuẩn bị trước (BĐCM ghi khi bấm Thực thi sản xuất) hoặc thực thi ngay (BĐCM = BĐSX)
    const START_MODES = ['prepare' => self::PREPARING, 'execute' => self::PRODUCING];

    // Công đoạn được mở phòng cho nhiều lô cùng lúc (Cân NL, Cân NL Khác)
    const GROUP_STAGES = [1, 2];

    // Lô đang chạy trong phòng (từ BĐSX đến KT): phòng dành cho lô, không được thêm hoạt động khác
    const BATCH_RUNNING_STATES = [self::PREPARING, self::PRODUCING, self::PAUSED];

    // Hủy thao tác: chỉ các thao tác chưa ghi gì vào stage_plan/yields, và chỉ trong 2 phút kể từ lúc thao tác
    // Kết thúc vệ sinh (→ Chờ kiểm tra) chưa ghi stage_plan nên hủy được; kết quả kiểm tra thì không (xem undo())
    const UNDO_STATES = [self::PREPARING, self::PRODUCING, self::CLEANING, self::AWAIT_CHECK];
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

            // Phòng sạch: người vệ sinh, người kiểm tra, lô sản xuất trước lần vệ sinh (vệ sinh lại không gắn lô → lô gần nhất của phòng)
            $room->st->cleaned = null;
            $room->st->prev_plan_id = null;
            if ($room->st->state === self::CLEAN) {
                $room->st->cleaned = $room->st->derived
                    ? (object) ['finished_on' => $room->st->since, 'done_by' => $sp->finished_by ?? null, 'checked_by' => null]
                    : $this->cleanedBy($room->id, $log);
                $room->st->prev_plan_id = $room->st->stage_plan_id ?: ($sp->id ?? null);
            }
        }

        // Nhóm lô chạy chung (Cân NL): chỉ khi trạng thái hiện tại lấy từ log và lô chính của log thuộc nhóm
        $groupRows = DB::table('room_execution_batch')
            ->whereIn('room_id', $ids)
            ->whereNull('cleaned_at')
            ->whereNull('cancelled_at')
            ->orderBy('id')
            ->get()
            ->groupBy('room_id')
            ->filter(function ($rows, $roomId) use ($rooms) {
                $st = $rooms->firstWhere('id', $roomId)->st ?? null;
                return $st && !$st->derived && $st->state !== self::CLEAN && $rows->contains('stage_plan_id', $st->stage_plan_id);
            });

        $nextIds = $this->nextPlanIds($ids);
        $details = $this->planDetails(array_merge(
            $rooms->pluck('st.stage_plan_id')->filter()->all(),
            $rooms->pluck('st.prev_plan_id')->filter()->all(),
            $groupRows->flatten()->pluck('stage_plan_id')->all(),
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
            // Đợt vệ sinh đang dở (Không đạt → tiếp tục vệ sinh): lúc bắt đầu vệ sinh đầu tiên, người vệ sinh
            $room->st->cycle = in_array($room->st->state, [self::CLEANING, self::AWAIT_CHECK], true) && $room->st->log_id
                ? $this->cleaningCycle($room->id, $room->st->log_id)
                : null;
            $room->st->prev_plan = $room->st->prev_plan_id ? $details->get($room->st->prev_plan_id) : null;
            // Mỗi lô trong nhóm: thông tin lô + row_id, started_at (BĐSX), ended_at (đã kết thúc), running
            $room->st->group = $groupRows->has($room->id)
                ? $groupRows->get($room->id)->map(function ($r) use ($details) {
                    $b = clone $details->get($r->stage_plan_id);
                    $b->row_id = $r->id;
                    $b->started_at = $r->started_at;
                    $b->ended_at = $r->ended_at;
                    $b->running = $r->ended_at === null;
                    return $b;
                })->values()
                : null;
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
            ->orderBy('ap.personnel_id')
            // Vị trí của người trong cả phân công (kể cả người chưa/hết giờ) để nhãn A, B, C... khớp Lịch Công Tác
            ->selectRaw('(SELECT COUNT(*) FROM assignment_personnel ap2 WHERE ap2.assignment_id = ap.assignment_id
                AND (ap2.display_order < ap.display_order OR (ap2.display_order = ap.display_order AND ap2.personnel_id < ap.personnel_id))) AS position')
            ->addSelect([
                'a.id', 'a.room_id', 'a.Sheet', 'a.start', 'a.end', 'a.Job_description',
                'ap.start as person_start', 'ap.end as person_end', 'ap.operation_type', 'ap.notification',
                'e.code', 'e.name',
            ])
            ->get()
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
                        'label' => chr(65 + (int) $p->position),
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
                'sp.finished_by',
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
            ->whereIn('state', [self::PREPARING, self::PRODUCING, self::PAUSED, self::NEED_CLEAN, self::CLEANING, self::AWAIT_CHECK])
            ->pluck('stage_plan_id')
            // các lô còn lại của nhóm lô đang chạy chung
            ->merge(DB::table('room_execution_batch')->whereNull('cleaned_at')->whereNull('cancelled_at')->pluck('stage_plan_id'))
            ->unique()
            ->values()
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
            'cycle'          => null, // đợt vệ sinh đang dở (Đang VS / Chờ kiểm tra), gán ở attachStates
            'group'          => null, // nhóm lô chạy chung (Cân NL), gán ở attachStates
            // Trình duyệt gửi lại token khi thao tác; khác token hiện tại nghĩa là phòng vừa bị người khác đổi trạng thái
            'token'          => implode('|', [$display, $logId ?? 'd', $spId ?? '-', $since ? $since->format('YmdHis') : '-']),
            'plan'           => null,
        ];
    }

    /**
     * Hạn Hủy thao tác của trạng thái hiện tại (null = không hủy được): UNDO_SECONDS kể từ lúc thao tác.
     */
    private static function groupEndedSince(object $st): bool
    {
        return $st->group && $st->since
            && $st->group->contains(fn($b) => $b->ended_at && Carbon::parse($b->ended_at)->gte($st->since));
    }

    public static function undoUntil(object $st): ?Carbon
    {
        if ($st->derived || !$st->acted_at || !in_array($st->state, self::UNDO_STATES, true)) {
            return null;
        }
        // Nhóm lô: đã kết thúc lô nào sau thao tác này (đã ghi sản lượng) thì không hủy được
        if (self::groupEndedSince($st)) {
            return null;
        }
        // Đang VS mở lại do kiểm tra Không đạt (không phải dòng Đang VS đầu tiên của đợt): kết quả kiểm tra không hủy được
        if ($st->state === self::CLEANING && !empty($st->cycle->first_log_id) && $st->cycle->first_log_id != $st->log_id) {
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
            // Mở phòng chỉ dành cho lô chưa từng thực thi: chưa hoàn thành và chưa từng xác nhận sản lượng (actual_start)
            ->where('sp.finished', 0)
            ->whereNull('sp.actual_start')
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
            // Modal "Mở phòng" chỉ hiện lô đã sắp lịch, kể cả Cân NL (candidateQuery vẫn cho phép chọn lô chưa sắp lịch để start())
            ->whereNotNull('sp.start')
            ->orderByRaw('sp.resourceId = ? DESC', [$room->id])
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
    /**
     * @param int|int[] $stagePlanIds  Cân NL được mở nhiều lô cùng mã BTP (nhóm lô): lô đầu tiên là lô chính của log,
     *                                 cả nhóm lưu ở room_execution_batch
     */
    public function start(int $roomId, ?string $token, int|array $stagePlanIds, ?string $mode, ?string $time, string $user): string
    {
        if (!isset(self::START_MODES[$mode])) {
            throw new ProductionExecutionException('❌ Chọn Chuẩn bị hoặc Thực thi sản xuất');
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $stagePlanIds))));
        if (!$ids) {
            throw new ProductionExecutionException('❌ Chọn lô cần sản xuất');
        }

        return $this->transition($roomId, $token, function ($room, $st) use ($ids, $mode, $time, $user) {
            if ($st->display === self::EXPIRED) {
                throw new ProductionExecutionException('❌ Phòng đã quá hạn sạch, cần vệ sinh lại trước khi sản xuất');
            }
            if ($st->state !== self::CLEAN) {
                throw new ProductionExecutionException('❌ Chỉ mở phòng khi phòng đang ở trạng thái Phòng Sạch');
            }
            if (count($ids) > 1 && !in_array((int) $room->stage_code, self::GROUP_STAGES, true)) {
                throw new ProductionExecutionException('❌ Chỉ công đoạn Cân nguyên liệu được mở phòng cho nhiều lô');
            }
            if (count($ids) !== $this->candidateQuery($room, 'stage')->whereIn('sp.id', $ids)->count()) {
                throw new ProductionExecutionException('❌ Lô không hợp lệ, đã hoàn thành hoặc đang được thực hiện ở phòng khác');
            }

            $plans = $this->planDetails($ids);
            if ($plans->pluck('intermediate_code')->unique()->count() > 1) {
                throw new ProductionExecutionException('❌ Chỉ mở chung các lô cùng mã bán thành phẩm (BTP)');
            }
            $at = $this->parseTime($time, 'Thời gian bắt đầu sản xuất (BĐSX)', $st->since);

            if ($st->expired_at && $at->gte($st->expired_at)) {
                throw new ProductionExecutionException('❌ Phòng đã hết hạn sạch lúc ' . $st->expired_at->format('H:i d/m/Y') . ', cần vệ sinh lại');
            }
            foreach ($plans as $plan) {
                if ($plan->max_yield_end && $at->lt(Carbon::parse($plan->max_yield_end))) {
                    throw new ProductionExecutionException('❌ BĐSX không được nhỏ hơn thời gian kết thúc lần xác nhận sản lượng trước của lô '
                        . $plan->label . ' (' . Carbon::parse($plan->max_yield_end)->format('H:i d/m/Y') . ')');
                }
            }

            $this->closeCurrent($room, $st, $at, $user);
            $logId = $this->openLog($room, self::START_MODES[$mode], $at, $user, ['stage_plan_id' => $ids[0]]);

            if (count($ids) > 1) {
                // Nhóm cũ còn sót (vd. vệ sinh được xác nhận ở trang cũ) thì đóng lại
                DB::table('room_execution_batch')->where('room_id', $room->id)->whereNull('cleaned_at')->whereNull('cancelled_at')
                    ->update(['cleaned_at' => now(), 'updated_at' => now()]);
                DB::table('room_execution_batch')->insert(array_map(fn($id) => [
                    'room_id'       => $room->id,
                    'stage_plan_id' => $id,
                    'open_log_id'   => $logId,
                    'started_at'    => $at,
                    'created_by'    => $user,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ], $ids));
            }

            $label = count($ids) > 1
                ? count($ids) . ' lô ' . ($plans->first()->product_name ?? '') . ' (' . $plans->pluck('batch')->implode(', ') . ')'
                : $plans->first()->label;

            return ($mode === 'prepare' ? '✅ Đã mở phòng, bắt đầu chuẩn bị ' : '✅ Đã bắt đầu sản xuất ') . $label;
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

            if ($st->group) {
                // Nhóm lô: ghi sản lượng lần này của từng lô đang chạy
                $end = $this->parseTime(null, 'Thời gian kết thúc (KT)', $st->since, true);
                $yieldId = null;
                foreach ($st->group->where('running', true) as $b) {
                    $yieldId = $this->groupYield($room, $st, $b, $end, $in, $user);
                }
                $logId = $this->closeCurrent($room, $st, $end, $user);
                DB::table('room_execution_log')->where('id', $logId)->update(['yield_id' => $yieldId]);
            } else {
                $end = $this->recordSegment($room, $st, $in, $user);
            }
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
            if ($st->group && in_array($st->state, [self::PRODUCING, self::PAUSED], true)) {
                return $this->finishGroup($room, $st, $in, $user);
            }
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

    /**
     * Kết thúc các lô được chọn ($in['finish_ids']) trong nhóm lô. Đang SX thì ghi sản lượng lần này của các lô đó;
     * Tạm dừng thì sản lượng đã ghi lúc tạm dừng. Còn lô đang chạy thì phòng giữ nguyên trạng thái, hết lô thì Cần VS.
     */
    private function finishGroup(object $room, object $st, array $in, string $user): string
    {
        $running = $st->group->where('running', true);
        $ids = array_map('intval', (array) ($in['finish_ids'] ?? []));
        $finishing = $running->whereIn('id', $ids);
        if ($finishing->isEmpty()) {
            throw new ProductionExecutionException('❌ Chọn lô cần kết thúc');
        }

        $producing = $st->state === self::PRODUCING;
        $end = $this->parseTime(null, 'Thời gian kết thúc (KT)', $st->since, $producing);
        $yieldId = null;
        if ($producing) {
            foreach ($finishing as $b) {
                $yieldId = $this->groupYield($room, $st, $b, $end, $in, $user);
            }
        }

        DB::table('room_execution_batch')->whereIn('id', $finishing->pluck('row_id')->all())
            ->update(['ended_at' => $end, 'ended_by' => $user, 'updated_at' => now()]);

        $labels = $finishing->pluck('batch')->implode(', ');
        if ($finishing->count() < $running->count()) {
            return '✅ Đã kết thúc lô ' . $labels . ', còn ' . ($running->count() - $finishing->count()) . ' lô đang '
                . ($producing ? 'sản xuất' : 'tạm dừng');
        }

        if ($producing) {
            $logId = $this->closeCurrent($room, $st, $end, $user);
            DB::table('room_execution_log')->where('id', $logId)->update(['yield_id' => $yieldId]);
        } else {
            $this->closePause($room, $st, $end, $user);
        }

        $plan = $this->planDetails([$st->stage_plan_id])->get($st->stage_plan_id);
        $this->openLog($room, self::NEED_CLEAN, $end, $user, [
            'stage_plan_id'  => $st->stage_plan_id,
            'cleaning_level' => $this->levelOf($plan->title_clearning ?? null),
        ]);

        return '✅ Đã kết thúc lô ' . $labels . ', hết lô trong phòng → Cần Vệ Sinh';
    }

    /** Ghi sản lượng lần này của 1 lô trong nhóm: $in['batches'][stage_plan_id] = {yields, number_of_boxes, box_mode, actual_batch} */
    private function groupYield(object $room, object $st, object $b, Carbon $end, array $in, string $user): int
    {
        $row = (array) ($in['batches'][$b->id] ?? []);
        try {
            return $this->recordYield($room, (int) $b->id, $st->since, $end,
                $row + ['note' => $in['note'] ?? null], $user, Carbon::parse($b->started_at));
        } catch (ProductionExecutionException $e) {
            throw new ProductionExecutionException('Lô ' . $b->batch . ': ' . $e->getMessage(), $e->getCode() ?: 422);
        }
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
     * Đang VS → Chờ Kiểm Tra. Chưa ghi stage_plan / Báo cáo ngày: vệ sinh chỉ được công nhận khi kiểm tra Đạt
     * (checkCleaning); Không đạt thì tiếp tục vệ sinh và lần kết thúc sau mới là giờ kết thúc vệ sinh thật.
     */
    public function endCleaning(int $roomId, ?string $token, ?string $time, ?string $note, string $user): string
    {
        return $this->transition($roomId, $token, function ($room, $st) use ($time, $note, $user) {
            if ($st->state !== self::CLEANING) {
                throw new ProductionExecutionException('❌ Phòng không ở trạng thái Đang Vệ Sinh');
            }

            $at = $this->parseTime($time, 'Thời gian kết thúc vệ sinh', $st->since, true);
            $note = $this->text($note);

            if ($st->stage_plan_id) {
                $cycle = $this->cleaningCycle($room->id, $st->log_id);
                $conflict = $this->overlapConflict($room, $st->stage_plan_id, $cycle->start ?? $st->since, $at);
                if ($conflict) {
                    throw new ProductionExecutionException('❌ Thời gian vệ sinh bị trùng giờ với lô "' . $conflict->title
                        . '" (' . $this->conflictRange($conflict) . ') trên cùng phòng sản xuất, vui lòng kiểm tra lại!');
                }
            }

            $logId = $this->closeCurrent($room, $st, $at, $user);
            if ($note) {
                DB::table('room_execution_log')->where('id', $logId)->update(['note' => $note]);
            }

            $this->openLog($room, self::AWAIT_CHECK, $at, $user, [
                'stage_plan_id'  => $st->stage_plan_id,
                'cleaning_level' => $st->cleaning_level ?: 'VS-I',
            ]);

            return '✅ Đã kết thúc vệ sinh, phòng chờ kiểm tra (người kiểm tra phải khác người vệ sinh)';
        });
    }

    /**
     * Kiểm tra vệ sinh (Chờ Kiểm Tra). Người kiểm tra đã xác thực lại tài khoản (verifyChecker) và phải khác người vệ sinh.
     * - Đạt: ghi thời gian vệ sinh (= ✓✓ nếu vệ sinh sau lô, không gắn lô thì ghi Báo cáo ngày) từ lúc bắt đầu vệ sinh
     *   tới lúc kết thúc vệ sinh; phòng sạch, hạn tính từ lúc kết thúc vệ sinh. Tịnh tuyến lịch như ✓✓ nếu công tắc bật.
     * - Không đạt (bắt buộc lý do): tiếp tục vệ sinh cùng cấp.
     *
     * @return array{message: string, reroute: ?array}
     */
    public function checkCleaning(int $roomId, ?string $token, bool $pass, ?string $note, object $checker): array
    {
        $cleanedPlanId = null;
        $deparmentCode = null;
        $note = $this->text($note);
        if (!$pass && !$note) {
            throw new ProductionExecutionException('❌ Nhập lý do không đạt');
        }

        $message = $this->transition($roomId, $token, function ($room, $st) use ($pass, $note, $checker, &$cleanedPlanId, &$deparmentCode) {
            if ($st->state !== self::AWAIT_CHECK || !$st->log_id) {
                throw new ProductionExecutionException('❌ Phòng không ở trạng thái Chờ Kiểm Tra');
            }

            $cycle = $this->cleaningCycle($room->id, $st->log_id);
            if (in_array(mb_strtolower($checker->fullName), array_map('mb_strtolower', $cycle->cleaners), true)) {
                throw new ProductionExecutionException('❌ Người kiểm tra phải khác người thực hiện vệ sinh ('
                    . implode(', ', $cycle->cleaners) . ')', 422);
            }

            $name = $checker->fullName;
            $now = now();
            $level = $st->cleaning_level ?: 'VS-I';

            $this->closeCurrent($room, $st, $now, $name);
            DB::table('room_execution_log')->where('id', $st->log_id)->update([
                'checked_by'   => $name,
                'checked_at'   => $now,
                'check_result' => $pass ? 1 : 0,
                'note'         => $note,
            ]);

            if (!$pass) {
                $this->openLog($room, self::CLEANING, $now, $name, [
                    'stage_plan_id'  => $st->stage_plan_id,
                    'cleaning_level' => $level,
                    'note'           => 'Kiểm tra không đạt: ' . $note,
                ]);

                return '⚠️ Kiểm tra không đạt, phòng tiếp tục vệ sinh';
            }

            $end = $st->since; // giờ kết thúc vệ sinh
            $start = $cycle->start ?? $end;

            if ($st->stage_plan_id) {
                // Nhóm lô: 1 lần vệ sinh chung ghi cho mọi lô trong nhóm (như trang Xác nhận hoàn thành đang làm)
                $planIds = $st->group ? $st->group->pluck('id')->push($st->stage_plan_id)->unique()->all() : [$st->stage_plan_id];
                DB::table('stage_plan')->whereIn('id', $planIds)->update([
                    'actual_start_clearning' => $start,
                    'actual_end_clearning'   => $end,
                    'finished_by'            => $name,
                    'finished_date'          => $now,
                    'finished'               => 1,
                ]);
                if ($st->group) {
                    DB::table('room_execution_batch')->whereIn('id', $st->group->pluck('row_id')->all())
                        ->update(['cleaned_at' => $now, 'updated_at' => $now]);
                }
            } elseif ($cycle->first_log_id) {
                // Vệ sinh không gắn lô: ghi vào Báo cáo ngày như 1 hoạt động của phòng, gắn với dòng Đang VS đầu tiên
                $rsId = $this->insertActivity($room, $level === 'VS-LAI' ? 'Vệ sinh lại' : $level, $start, $end,
                    'Kiểm tra đạt: ' . $name . ($note ? ' - ' . $note : ''), $cycle->cleaners[0] ?? $name);
                DB::table('room_execution_log')->where('id', $cycle->first_log_id)->update(['room_status_id' => $rsId]);
            }

            $expiredAt = $this->expiry($end, $level);
            $this->openLog($room, self::CLEAN, $now, $name, [
                'stage_plan_id'  => $st->stage_plan_id,
                'cleaning_level' => $level,
                'expired_at'     => $expiredAt,
                'note'           => 'Kết thúc vệ sinh ' . $end->format('H:i d/m/Y') . ' · Kiểm tra đạt: ' . $name,
            ]);

            $cleanedPlanId = $st->stage_plan_id;
            $deparmentCode = $room->deparment_code;

            return '✅ Kiểm tra đạt, phòng sạch đến ' . $expiredAt->format('H:i d/m/Y');
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

    /**
     * Đợt vệ sinh đang dở của phòng: chuỗi dòng Đang VS / Chờ kiểm tra liền nhau kết thúc ở dòng $logId
     * (Không đạt → tiếp tục vệ sinh vẫn cùng 1 đợt). start = lúc bắt đầu vệ sinh đầu tiên; cleaners = người bắt đầu
     * vệ sinh + người bấm Kết thúc vệ sinh (không tính người kiểm tra đã mở lại dòng Đang VS khi Không đạt).
     */
    public function cleaningCycle(int $roomId, ?int $logId): object
    {
        $cycle = (object) ['start' => null, 'first_log_id' => null, 'cleaners' => []];
        if (!$logId) {
            return $cycle;
        }

        $logs = DB::table('room_execution_log')
            ->where('room_id', $roomId)
            ->whereNull('cancelled_at')
            ->where('id', '<=', $logId)
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'state', 'started_at', 'created_by']);

        $chain = collect();
        foreach ($logs as $l) {
            if (!in_array((int) $l->state, [self::CLEANING, self::AWAIT_CHECK], true)) {
                break;
            }
            $chain->prepend($l);
        }

        $first = $chain->first(fn($l) => (int) $l->state === self::CLEANING);
        if ($first) {
            $cycle->start = Carbon::parse($first->started_at);
            $cycle->first_log_id = $first->id;
        }
        $cycle->cleaners = $chain
            ->filter(fn($l) => (int) $l->state === self::AWAIT_CHECK || ($first && $l->id === $first->id))
            ->pluck('created_by')
            ->filter(fn($n) => $n && $n !== 'Hệ thống')
            ->unique()
            ->values()
            ->all();

        return $cycle;
    }

    /**
     * Xác thực lại tài khoản người kiểm tra (chữ ký điện tử): cùng quy tắc khóa tài khoản với màn hình đăng nhập,
     * nhập sai cũng tính vào số lần sai.
     */
    public function verifyChecker(?string $userName, ?string $password): object
    {
        $userName = trim((string) $userName);
        if ($userName === '' || (string) $password === '') {
            throw new ProductionExecutionException('❌ Nhập tài khoản và mật khẩu của người kiểm tra', 422);
        }

        $user = DB::table('user_management')->where('userName', $userName)->first();
        if (!$user) {
            throw new ProductionExecutionException('❌ Tài khoản hoặc mật khẩu không đúng', 422);
        }

        $maxAttempts = (int) config('security.max_login_attempts', 5);
        $lockoutMin = (int) config('security.lockout_minutes', 15);

        if ($user->isLocked && $user->locked_at && now()->gte(Carbon::parse($user->locked_at)->addMinutes($lockoutMin))) {
            DB::table('user_management')->where('id', $user->id)->update(['isLocked' => 0, 'failed_attempts' => 0, 'locked_at' => null]);
            $user->isLocked = 0;
            $user->failed_attempts = 0;
        }
        if ($user->isLocked) {
            throw new ProductionExecutionException('❌ Tài khoản đang bị khóa do nhập sai nhiều lần, thử lại sau', 423);
        }

        if (!Hash::check((string) $password, $user->passWord)) {
            $attempts = (int) $user->failed_attempts + 1;
            $locked = $attempts >= $maxAttempts;
            DB::table('user_management')->where('id', $user->id)->update(
                ['failed_attempts' => $attempts] + ($locked ? ['isLocked' => 1, 'locked_at' => now()] : [])
            );
            AuditTrialController::log('Clean Check Auth Failed', 'user_management', $user->id, 'NA',
                "Sai mật khẩu khi kiểm tra vệ sinh lần {$attempts}/{$maxAttempts}" . ($locked ? ' - tài khoản bị khóa' : ''), $user->userName);

            throw new ProductionExecutionException($locked
                ? "❌ Sai mật khẩu $maxAttempts lần, tài khoản đã bị khóa $lockoutMin phút"
                : '❌ Tài khoản hoặc mật khẩu không đúng (còn ' . ($maxAttempts - $attempts) . ' lần thử)', 422);
        }

        if ($user->failed_attempts) {
            DB::table('user_management')->where('id', $user->id)->update(['failed_attempts' => 0]);
        }

        return $user;
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
     * Mở phòng (chuẩn bị / bắt đầu SX), Thực thi sản xuất, Bắt đầu lại, Bắt đầu vệ sinh, Kết thúc vệ sinh (chưa kiểm tra) — trong 2 phút kể từ lúc thao tác.
     */
    public function undo(int $roomId, ?string $token, string $user): string
    {
        return $this->transition($roomId, $token, function ($room, $st) use ($user) {
            if ($st->derived || !in_array($st->state, self::UNDO_STATES, true)) {
                throw new ProductionExecutionException('❌ Chỉ hủy được thao tác Mở phòng / Thực thi sản xuất / Bắt đầu lại / Bắt đầu / Kết thúc vệ sinh vừa thực hiện');
            }
            if ($st->state === self::CLEANING && !empty($st->cycle->first_log_id) && $st->cycle->first_log_id != $st->log_id) {
                throw new ProductionExecutionException('❌ Không hủy được kết quả kiểm tra vệ sinh', 409);
            }
            if (self::groupEndedSince($st)) {
                throw new ProductionExecutionException('❌ Đã kết thúc lô trong nhóm sau thao tác này (đã ghi sản lượng), không hủy được', 409);
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

            // Đang VS do kiểm tra Không đạt: kết quả kiểm tra (đã xác thực tài khoản) không hủy được
            if ($prev && (int) $prev->state === self::AWAIT_CHECK && $prev->check_result !== null) {
                throw new ProductionExecutionException('❌ Không hủy được kết quả kiểm tra vệ sinh');
            }

            DB::table('room_execution_log')->where('id', $st->log_id)->update([
                'cancelled_at' => now(),
                'cancelled_by' => $user,
                'updated_at'   => now(),
            ]);
            // Hủy Mở phòng nhiều lô: hủy cả nhóm
            DB::table('room_execution_batch')->where('open_log_id', $st->log_id)->whereNull('cancelled_at')
                ->update(['cancelled_at' => now(), 'updated_at' => now()]);

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

    /**
     * Nhật ký phòng. Dùng đúng 4 nguồn như cột Chi tiết của Báo cáo ngày để 2 trang không lệch nhau:
     * trạng thái phòng (room_execution_log), sản xuất (yields), vệ sinh (stage_plan.actual_*_clearning),
     * hoạt động khác (room_status). Lô thực thi ở trang này có cả dòng log lẫn yields/stage_plan nên
     * ưu tiên dòng log (có người thực hiện, người kết thúc, cờ đã hủy) và bỏ bản sao ở 2 nguồn kia.
     */
    public function history(int $roomId, ?string $from = null, ?string $to = null): array
    {
        $fmt = fn($t) => $t ? Carbon::parse($t)->format('H:i d/m/Y') : null;
        $key = fn($t) => $t ? Carbon::parse($t)->format('Y-m-d H:i:s') : '';
        $qty = fn($v) => number_format((float) $v, 2, ',', '.');
        // Thời lượng ngắn gọn như cột Chi tiết của Báo cáo ngày ("2h30p", "45p")
        $dur = function ($start, $end) {
            if (!$start || !$end) {
                return null;
            }
            $m = intdiv(max(0, (int) Carbon::parse($start)->diffInSeconds(Carbon::parse($end), false)), 60);
            [$h, $mm] = [intdiv($m, 60), $m % 60];
            return $h ? ($mm ? "{$h}h{$mm}p" : "{$h}h") : "{$mm}p";
        };

        // Tên lô: tên bán thành phẩm (qua intermediate_category) - số lô thực tế, như Báo cáo ngày
        $joinPlan = fn($q) => $q
            ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
            ->leftJoin('finished_product_category as fpc', 'sp.product_caterogy_id', '=', 'fpc.id')
            ->leftJoin('intermediate_category as ic', 'fpc.intermediate_code', '=', 'ic.intermediate_code')
            ->leftJoin('product_name as pn', 'ic.product_name_id', '=', 'pn.id');
        $planCols = [
            'sp.title as plan_title',
            'sp.stage_code',
            'pn.name as product_name',
            DB::raw('COALESCE(pm.actual_batch, pm.batch) AS batch'),
            'fpc.intermediate_code',
        ];
        $product = fn($r) => $r->product_name ?: $r->plan_title;
        $label = fn($r) => trim(($r->product_name ?: $r->plan_title) . ($r->batch ? ' - ' . $r->batch : ''), ' -');
        $unit = fn($r) => (int) $r->stage_code <= 4 ? 'Kg' : 'ĐVL';

        /* --- 1. Trạng thái phòng --- */
        $logQuery = DB::table('room_execution_log as l')
            ->leftJoin('stage_plan as sp', 'l.stage_plan_id', '=', 'sp.id')
            // Sản lượng của chính lần xác nhận này: yields.start = BĐCM = lúc mở dòng log Đang SX
            ->leftJoin('yields as y', fn($j) => $j->on('y.stage_plan_id', '=', 'l.stage_plan_id')->on('y.start', '=', 'l.started_at'))
            ->where('l.room_id', $roomId)
            ->when($from, fn($q) => $q->where(fn($q2) => $q2->whereNull('l.ended_at')->orWhere('l.ended_at', '>=', $from)))
            ->when($to, fn($q) => $q->where('l.started_at', '<=', $to))
            ->orderByDesc('l.started_at')
            ->orderByDesc('l.id');
        $joinPlan($logQuery);
        $logRows = $logQuery->get(array_merge(['l.*', 'y.yield'], $planCols));

        // Khóa để bỏ bản sao ở yields / stage_plan
        $loggedYield = [];
        $loggedClean = [];
        foreach ($logRows as $l) {
            if ($l->stage_plan_id) {
                $loggedYield[$l->stage_plan_id . '|' . $key($l->started_at)] = true;
            }
            if ((int) $l->state === self::CLEANING) {
                $loggedClean[$key($l->started_at)] = true;
            }
        }

        /* --- 2. Vệ sinh (stage_plan): lấy trước để dòng log vệ sinh của nhóm lô liệt kê đủ số lô --- */
        $cleanQuery = DB::table('stage_plan as sp')
            ->where('sp.resourceId', $roomId)
            ->whereNotNull('sp.actual_start_clearning')
            ->whereNotNull('sp.actual_end_clearning')
            ->when($from, fn($q) => $q->where('sp.actual_end_clearning', '>=', $from))
            ->when($to, fn($q) => $q->where('sp.actual_start_clearning', '<=', $to))
            ->orderByDesc('sp.actual_start_clearning');
        $joinPlan($cleanQuery);
        $cleanRows = $cleanQuery->get(array_merge(
            ['sp.actual_start_clearning as start', 'sp.actual_end_clearning as end', 'sp.title_clearning', 'sp.finished_by'],
            $planCols
        ));
        // Nhóm lô vệ sinh chung: 1 lần vệ sinh ghi cho mọi lô trong nhóm → gộp số lô như Báo cáo ngày
        $groupLabel = fn($same) => $same->count() > 1
            ? trim($product($same->first()) . ' - ' . $same->pluck('batch')->implode(', '), ' -')
            : $label($same->first());
        $cleanByStart = $cleanRows->groupBy(fn($c) => $key($c->start));

        $logs = $logRows->map(function ($l) use ($fmt, $dur, $qty, $key, $label, $unit, $cleanByStart, $groupLabel) {
            $group = (int) $l->state === self::CLEANING ? $cleanByStart->get($key($l->started_at)) : null;
            $plan = $group ? $groupLabel($group) : $label($l);
            $level = $l->cleaning_level ? (self::CLEANING_LEVELS[$l->cleaning_level] ?? $l->cleaning_level) : '';

            return [
                'kind'      => 'state',
                'label'     => self::STATE_LABELS[$l->state] ?? $l->state,
                'state'     => (int) $l->state,
                'detail'    => $level && $plan ? $level . ' · ' . $plan : ($level ?: $plan),
                'note'      => $l->note ?: null,
                'code'      => $l->intermediate_code ?: null,
                'yield'     => $l->yield !== null ? $qty($l->yield) . ' ' . $unit($l) : null,
                'start'     => $fmt($l->started_at),
                'end'       => $fmt($l->ended_at),
                'dur'       => $dur($l->started_at, $l->ended_at),
                'by'        => $l->created_by,
                'end_by'    => $l->ended_by,
                'cancelled' => $l->cancelled_at ? 'Đã hủy bởi ' . $l->cancelled_by . ' lúc ' . $fmt($l->cancelled_at) : null,
                'sort'      => $l->started_at,
            ];
        });

        /* --- 3. Sản xuất (yields), gộp nhóm lô cân chung như Báo cáo ngày --- */
        $prodQuery = DB::table('yields as y')
            ->join('stage_plan as sp', 'sp.id', '=', 'y.stage_plan_id')
            ->where('sp.resourceId', $roomId)
            ->whereNotNull('y.start')
            ->whereNotNull('y.end')
            ->when($from, fn($q) => $q->where('y.end', '>=', $from))
            ->when($to, fn($q) => $q->where('y.start', '<=', $to))
            ->orderByDesc('y.start');
        $joinPlan($prodQuery);
        $production = $prodQuery->get(array_merge(['y.stage_plan_id', 'y.start', 'y.end', 'y.yield', 'y.created_by', 'sp.note'], $planCols))
            ->reject(fn($y) => isset($loggedYield[$y->stage_plan_id . '|' . $key($y->start)]))
            ->groupBy(fn($y) => $key($y->start) . '|' . $key($y->end) . '|' . $product($y) . '|' . $unit($y))
            ->map(function ($same) use ($fmt, $dur, $qty, $label, $product, $unit) {
                $first = $same->first();
                $total = $same->sum(fn($y) => (float) $y->yield);
                $note = $same->count() > 1
                    ? $same->count() . ' lô: ' . $same->map(fn($y) => $y->batch . ' ' . $qty($y->yield))->implode(' · ')
                    : ($first->note && $first->note !== 'NA' ? $first->note : null);

                return [
                    'kind'      => 'state',
                    'label'     => self::STATE_LABELS[self::PRODUCING],
                    'state'     => self::PRODUCING,
                    'detail'    => $same->count() > 1
                        ? trim($product($first) . ' - ' . $same->pluck('batch')->implode(', '), ' -')
                        : $label($first),
                    'note'      => $note,
                    'code'      => $first->intermediate_code ?: null,
                    'yield'     => $qty($total) . ' ' . $unit($first),
                    'start'     => $fmt($first->start),
                    'end'       => $fmt($first->end),
                    'dur'       => $dur($first->start, $first->end),
                    'by'        => $first->created_by,
                    'end_by'    => null,
                    'cancelled' => null,
                    'sort'      => $first->start,
                ];
            })->values();

        /* --- 4. Dòng vệ sinh chưa có trong room_execution_log --- */
        $cleanings = $cleanRows
            ->reject(fn($c) => isset($loggedClean[$key($c->start)]))
            ->groupBy(fn($c) => $key($c->start) . '|' . $key($c->end) . '|' . $product($c))
            ->map(function ($same) use ($fmt, $dur, $groupLabel) {
                $first = $same->first();
                $level = self::CLEANING_LEVELS[$this->levelOf($first->title_clearning)];
                $plan = $groupLabel($same);

                return [
                    'kind'      => 'state',
                    'label'     => self::STATE_LABELS[self::CLEANING],
                    'state'     => self::CLEANING,
                    'detail'    => $plan ? $level . ' · ' . $plan : $level,
                    'note'      => null,
                    'code'      => $first->intermediate_code ?: null,
                    'yield'     => null,
                    'start'     => $fmt($first->start),
                    'end'       => $fmt($first->end),
                    'dur'       => $dur($first->start, $first->end),
                    'by'        => $first->finished_by,
                    'end_by'    => null,
                    'cancelled' => null,
                    'sort'      => $first->start,
                ];
            })->values();

        /* --- 5. Hoạt động khác --- */
        $activities = DB::table('room_status')
            ->where('room_id', $roomId)
            ->where('is_daily_report', 1)
            ->where('active', 1)
            ->whereNotNull('start')
            ->when($from, fn($q) => $q->where(fn($q2) => $q2->whereNull('end')->orWhere('end', '>=', $from)))
            ->when($to, fn($q) => $q->where('start', '<=', $to))
            ->orderByDesc('start')
            ->get()
            ->map(fn($a) => [
                'kind'      => 'activity',
                'label'     => $a->in_production,
                'state'     => null,
                'detail'    => $a->in_production,
                'note'      => $a->notification !== 'NA' ? $a->notification : null,
                'code'      => null,
                'yield'     => null,
                'start'     => $fmt($a->start),
                'end'       => $fmt($a->end),
                'dur'       => $dur($a->start, $a->end),
                'by'        => $a->created_by,
                'end_by'    => null,
                'cancelled' => null,
                'sort'      => $a->start,
            ]);

        return $logs->concat($production)->concat($cleanings)->concat($activities)
            ->sortByDesc('sort')->values()->all();
    }

    /* =========================================================
       NHÃN PHÒNG (theo mẫu nhãn phòng eBMR: Cần vệ sinh / Đã vệ sinh)
       ========================================================= */

    // Nhãn Cần vệ sinh: cấp I phải vệ sinh trong 24 giờ, cấp II trong 3 ngày kể từ khi hoàn tất sản xuất
    const CLEAN_WITHIN_HOURS = ['VS-I' => 24, 'VS-II' => 72, 'VS-LAI' => 24];

    /**
     * Nhãn tình trạng phòng hiện tại.
     * - Cần VS / Đang VS / Cần VS lại → nhãn vàng "Cần vệ sinh".
     * - Phòng sạch → nhãn xanh "Đã vệ sinh", lô tiếp theo = lô kế tiếp theo lịch.
     * - Đang chuẩn bị / Đang SX / Tạm dừng → nhãn xanh của lần vệ sinh trước BĐSX, đã gắn vào hồ sơ lô đang chạy.
     */
    public function roomLabel(int $roomId): ?array
    {
        $room = $this->room($roomId);
        if (!$room) {
            return null;
        }

        $st = $room->st;
        $fmt = fn($t) => $t ? Carbon::parse($t)->format('H:i d/m/Y') : null;
        $label = [
            'room_name'   => $room->name,
            'room_code'   => $room->code,
            'state'       => $st->display,
            'state_label' => $st->label,
            'note'        => null,
            'generated_at' => now()->format('H:i d/m/Y'),
        ];

        if (in_array($st->display, [self::NEED_CLEAN, self::CLEANING, self::EXPIRED], true)) {
            $finishedOn = null;
            $doneBy = null;
            $level = $st->cleaning_level;
            $before = null;

            if ($st->display === self::EXPIRED) {
                $level = 'VS-LAI';
                $before = $st->expired_at;
                $label['note'] = 'Phòng sạch hết hiệu lực lúc ' . $fmt($st->expired_at) . ', cần vệ sinh lại trước khi sản xuất';
            } else {
                // Đang VS: thông tin "cần vệ sinh" lấy từ trạng thái ngay trước lúc bắt đầu vệ sinh
                // (Không đạt → tiếp tục vệ sinh: lấy trạng thái trước cả đợt vệ sinh)
                $cycleFirst = $st->display === self::CLEANING ? ($this->cleaningCycle($room->id, $st->log_id)->first_log_id ?? $st->log_id) : null;
                $dirty = $st->display === self::CLEANING && $st->log_id
                    ? DB::table('room_execution_log')->where('room_id', $room->id)->whereNull('cancelled_at')
                        ->where('id', '<', $cycleFirst)->orderByDesc('id')->first()
                    : ($st->log_id ? DB::table('room_execution_log')->where('id', $st->log_id)->first() : null);

                if ($dirty && (int) $dirty->state === self::CLEAN) {
                    // Vệ sinh lại phòng quá hạn
                    $before = $dirty->expired_at ? Carbon::parse($dirty->expired_at) : null;
                } elseif ($dirty) {
                    $finishedOn = Carbon::parse($dirty->started_at);
                    $doneBy = $this->personOf($dirty->created_by, $dirty->stage_plan_id);
                    $level = $level ?: $dirty->cleaning_level;
                } elseif ($st->since) {
                    $finishedOn = $st->since;
                    $doneBy = $this->personOf(null, $st->stage_plan_id);
                }
                if ($finishedOn) {
                    $before = $finishedOn->copy()->addHours(self::CLEAN_WITHIN_HOURS[$level ?: 'VS-I'] ?? 24);
                }
                if ($st->display === self::CLEANING) {
                    $by = $st->log_id ? DB::table('room_execution_log')->where('id', $st->log_id)->value('created_by') : null;
                    $label['note'] = 'Đang vệ sinh từ ' . $fmt($st->since) . ($by ? ' (' . $by . ')' : '')
                        . ($st->note ? ' · ' . $st->note : '');
                } elseif ($st->derived && !$st->since) {
                    $label['note'] = $st->note;
                }
            }

            return $label + [
                'kind'        => 'to_clean',
                'level'       => $level,
                'finished_on' => $fmt($finishedOn),
                'before'      => $fmt($before),
                'overdue'     => $before && now()->gte($before),
                'done_by'     => $doneBy,
            ];
        }

        // Chờ kiểm tra: nhãn xanh đã điền người vệ sinh, còn trống người kiểm tra
        if ($st->display === self::AWAIT_CHECK) {
            $level = $st->cleaning_level ?: 'VS-I';
            $next = $room->next_plan ?? null;
            $label['note'] = 'Chờ kiểm tra vệ sinh — phòng chưa được sử dụng cho tới khi kiểm tra Đạt';

            return $label + [
                'kind'         => 'cleaned',
                'level'        => $level,
                'finished_on'  => $fmt($st->since),
                'valid_until'  => $st->since ? $fmt($this->expiry($st->since, $level)) : null,
                'done_by'      => implode(', ', $this->cleaningCycle($room->id, $st->log_id)->cleaners) ?: null,
                'checked_by'   => null,
                'next_product' => $next ? ($next->product_name ?? $next->title) : null,
                'next_batch'   => $next->batch ?? null,
                'next_planned' => $next ? $fmt($next->start) : null,
                'received_by'  => null,
                'attached'     => false,
                'pending'      => true,
            ];
        }

        // Nhãn xanh: lần vệ sinh còn hiệu lực lúc này (Phòng sạch) hoặc lúc mở phòng cho lô đang chạy
        $running = in_array($st->state, self::BATCH_RUNNING_STATES, true) && $st->plan;
        $at = $running ? Carbon::parse($st->plan->batch_start ?? $st->since) : now();
        $cleaning = $this->lastCleaning($room->id, $at);
        $nextPlan = $running ? $st->plan : ($room->next_plan ?? null);

        if ($running && $cleaning && $cleaning->valid_until && $at->gte($cleaning->valid_until)) {
            $label['note'] = 'Lưu ý: lúc BĐSX phòng đã quá hạn sạch';
        }

        return $label + [
            'kind'         => 'cleaned',
            'level'        => $cleaning->level ?? null,
            'finished_on'  => $fmt($cleaning->finished_on ?? null),
            'valid_until'  => $fmt($cleaning->valid_until ?? null),
            'done_by'      => $cleaning->done_by ?? null,
            'checked_by'   => $cleaning->checked_by ?? null, // chỉ có khi phòng sạch qua bước Kiểm tra
            'next_product' => $nextPlan ? ($nextPlan->product_name ?? $nextPlan->title) : null,
            'next_batch'   => $nextPlan->batch ?? null,
            'next_planned' => !$running && $nextPlan ? $fmt($nextPlan->start) : null,
            'attached'     => $running,
        ];
    }

    /**
     * Lần vệ sinh gần nhất của phòng kết thúc trước $before: từ room_execution_log (Phòng sạch) hoặc
     * stage_plan.actual_end_clearning (✓✓ ở trang Xác nhận hoàn thành), lấy cái mới hơn.
     */
    private function lastCleaning(int $roomId, Carbon $before): ?object
    {
        $log = DB::table('room_execution_log')
            ->where('room_id', $roomId)
            ->where('state', self::CLEAN)
            ->whereNull('cancelled_at')
            ->where('started_at', '<=', $before)
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->first();

        $sp = DB::table('stage_plan')
            ->where('resourceId', $roomId)
            ->where('active', 1)
            ->whereNotNull('actual_end_clearning')
            ->where('actual_end_clearning', '<=', $before)
            ->orderByDesc('actual_end_clearning')
            ->first(['id', 'actual_end_clearning', 'title_clearning', 'finished_by']);

        if ($log && (!$sp || $log->started_at >= $sp->actual_end_clearning)) {
            $level = $log->cleaning_level ?: 'VS-I';
            $by = $this->cleanedBy($roomId, $log);

            return (object) [
                'finished_on' => $by->finished_on,
                'level'       => $level,
                'valid_until' => $log->expired_at ? Carbon::parse($log->expired_at) : $this->expiry($by->finished_on, $level),
                'done_by'     => $by->done_by,
                'checked_by'  => $by->checked_by,
            ];
        }

        if ($sp) {
            $level = $this->levelOf($sp->title_clearning);
            $finished = Carbon::parse($sp->actual_end_clearning);

            return (object) [
                'finished_on' => $finished,
                'level'       => $level,
                'valid_until' => $this->expiry($finished, $level),
                'done_by'     => $sp->finished_by,
            ];
        }

        return null;
    }

    /**
     * Người vệ sinh / người kiểm tra / giờ kết thúc vệ sinh của 1 dòng Phòng Sạch: qua bước Kiểm tra thì lấy từ dòng
     * Chờ kiểm tra (Đạt) ngay trước; dòng cũ (trước khi có bước kiểm tra) thì người tạo dòng, không có người kiểm tra.
     */
    private function cleanedBy(int $roomId, object $cleanLog): object
    {
        $by = (object) [
            'finished_on' => Carbon::parse($cleanLog->started_at),
            'done_by'     => $this->personOf($cleanLog->created_by, $cleanLog->stage_plan_id),
            'checked_by'  => null,
        ];

        $check = DB::table('room_execution_log')
            ->where('room_id', $roomId)
            ->whereNull('cancelled_at')
            ->where('id', '<', $cleanLog->id)
            ->orderByDesc('id')
            ->first();
        if ($check && (int) $check->state === self::AWAIT_CHECK && (int) $check->check_result === 1) {
            $by->finished_on = Carbon::parse($check->started_at);
            $by->done_by = implode(', ', $this->cleaningCycle($roomId, $check->id)->cleaners) ?: $by->done_by;
            $by->checked_by = $check->checked_by;
        }

        return $by;
    }

    /** Người thao tác; dòng log do hệ thống khởi tạo từ trang cũ thì lấy người xác nhận của lô */
    private function personOf(?string $createdBy, $stagePlanId): ?string
    {
        if ($createdBy && $createdBy !== 'Hệ thống') {
            return $createdBy;
        }

        return $stagePlanId ? DB::table('stage_plan')->where('id', $stagePlanId)->value('finished_by') : null;
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
        $end = $this->parseTime($in['end'] ?? null, 'Thời gian kết thúc (KT)', $st->since, true);
        $yieldId = $this->recordYield($room, (int) $st->stage_plan_id, $st->since, $end, $in, $user);

        $logId = $this->closeCurrent($room, $st, $end, $user);
        DB::table('room_execution_log')->where('id', $logId)->update(['yield_id' => $yieldId]);

        return $end;
    }

    /**
     * Ghi sản lượng lần này (BĐCM $since → KT $end) của 1 lô vào stage_plan + yields. Không đổi trạng thái phòng.
     * $groupStart: BĐSX của lô chạy chung nhóm (lô không phải lô chính không có dòng log riêng).
     */
    private function recordYield(object $room, int $stagePlanId, Carbon $since, Carbon $end, array $in, string $user, ?Carbon $groupStart = null): int
    {
        $sp = DB::table('stage_plan')->where('id', $stagePlanId)->lockForUpdate()->first();
        if (!$sp) {
            throw new ProductionExecutionException('❌ Không tìm thấy lô đang sản xuất');
        }

        $startYield = !empty($in['start_yield']) ? $this->parseTime($in['start_yield'], 'BĐCM', null) : $since->copy();
        if ($startYield->lt($since)) {
            throw new ProductionExecutionException('❌ BĐCM không được nhỏ hơn thời điểm bắt đầu sản xuất / bắt đầu lại ('
                . $since->format('H:i d/m/Y') . ')');
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
        $batchStart = Carbon::parse($sp->actual_start ?? $groupStart ?? $firstStart ?? $since);

        $conflict = $this->overlapConflict($room, $sp->id, $batchStart, $end);
        if ($conflict) {
            throw new ProductionExecutionException('❌ Thời gian sản xuất bị trùng giờ với lô "' . $conflict->title
                . '" (' . $this->conflictRange($conflict) . ') trên cùng phòng sản xuất, vui lòng kiểm tra lại!');
        }

        // Số thùng nhập là số thùng dùng cho LẦN NÀY. Lô đã có sản lượng > 0 thì phải chọn thùng mới hay dùng tiếp
        // thùng đang dùng dở (thùng đó đã được đếm ở lần trước → không cộng lại)
        $boxes = max(1, (int) ($in['number_of_boxes'] ?? 1));
        if ($previous > 0) {
            $mode = $in['box_mode'] ?? null;
            if (!in_array($mode, ['new', 'continue'], true)) {
                throw new ProductionExecutionException('❌ Chọn "Thùng mới" hoặc "Dùng tiếp thùng đang sử dụng"');
            }
            $totalBoxes = max(1, (int) $sp->number_of_boxes) + $boxes - ($mode === 'continue' ? 1 : 0);
        } else {
            $totalBoxes = $boxes;
        }

        $update = [
            'resourceId'      => $room->id,
            'actual_start'    => $batchStart,
            'actual_end'      => $end,
            'yields'          => $total,
            'number_of_boxes' => $totalBoxes,
            'finished_by'     => $user,
            'finished_date'   => now(),
            'finished'        => 1,
        ];

        $note = $this->text($in['note'] ?? null);
        if ($note) {
            $update['note'] = $note;
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

        return $yieldId;
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
