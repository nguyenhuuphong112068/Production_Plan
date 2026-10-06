<?php

namespace App\Services;

use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Trang "Thực Thi Sản Xuất" (bản tinh gọn 10/2026): phòng chỉ có 2 trạng thái, ghi thẳng vào stage_plan.
 *
 *   Sẵn Sàng --(Nhận phòng: chọn lô / lịch bảo trì, ghi actual_start)--> Phòng Bận
 *   Phòng Bận --(Trả phòng: ghi actual_end_clearning; lịch bảo trì ghi actual_end)--> Sẵn Sàng
 *   Lịch bảo trì có vệ sinh (title_clearning, lịch BT/TI): Trả phòng ghi actual_end → Chờ VS Sau BT
 *     --(Nhận phòng vệ sinh sau BT: actual_start_clearning)--> Đang VS Sau BT --(Trả phòng: actual_end_clearning)--> Sẵn Sàng
 *   Lịch hiệu chuẩn (HC) không có vệ sinh: Trả phòng ghi actual_end là xong.
 *
 * - Phòng Bận = có lô của phòng đã có actual_start mà chưa có mốc trả phòng, và actual_start sau lần trả phòng gần
 *   nhất của phòng (lô cũ chưa từng xác nhận ✓✓ không giữ phòng mãi), chỉ tính lô nhận phòng từ TRACK_FROM.
 * - Lịch stage_code 8 không có vệ sinh (title_clearning NULL, lịch HC): mốc trả phòng là actual_end.
 * - Lịch stage_code 8 chọn theo phòng ban: EN nhận lịch BT/TI, QA nhận lịch HC, phòng ban khác chỉ nhận lô sản xuất.
 * - Không dùng room_execution_log / room_execution_batch (bảng của bản cũ, Báo cáo ngày vẫn đọc dữ liệu cũ ở đó).
 * - Sản lượng, KT sản xuất, vệ sinh vẫn xác nhận ở trang Xác nhận hoàn thành.
 */
class RoomOccupancyService
{
    const READY      = 1;
    const BUSY       = 2;
    const CLEAN_WAIT = 3; // bảo trì xong, chờ sản xuất nhận phòng vệ sinh
    const CLEANING   = 4; // đang vệ sinh sau bảo trì

    const STATE_LABELS = [
        self::READY      => 'Sẵn Sàng',
        self::BUSY       => 'Phòng Bận',
        self::CLEAN_WAIT => 'Chờ VS Sau BT',
        self::CLEANING   => 'Đang VS Sau BT',
    ];

    // [hậu tố class CSS (dùng lại màu của bản cũ trong _styles), icon]
    const STATE_META = [
        self::READY      => ['clean', 'fa-check-circle'],
        self::BUSY       => ['producing', 'fa-cog'],
        self::CLEAN_WAIT => ['dirty', 'fa-exclamation-triangle'],
        self::CLEANING   => ['cleaning', 'fa-broom'],
    ];

    const DISPLAY_ORDER = [self::BUSY, self::CLEANING, self::CLEAN_WAIT, self::READY];

    const MAINTENANCE_STAGE = 8;

    // Loại lịch stage_code 8 (tiền tố quota_maintenance.block) mỗi phòng ban được nhận phòng; phòng ban không có ở đây
    // chỉ nhận lô sản xuất. Dòng không có block coi là BT như Lịch Công Tác bảo trì.
    const MAINTENANCE_TYPES_BY_DEPARTMENT = [
        'EN' => ['BT', 'TI'],
        'QA' => ['HC'],
    ];

    const MAINTENANCE_TYPE_LABELS = ['BT' => 'Bảo trì', 'TI' => 'Tiện ích', 'HC' => 'Hiệu chuẩn'];

    // Công đoạn được nhận phòng cho nhiều lô cùng lúc (Cân NL, Cân NL Khác): các lô phải cùng mã BTP
    const GROUP_STAGES = [1, 2];

    // Mã ca (assignments.Sheet) như trang Lịch Công Tác → Sản Xuất
    const SHIFT_NAMES = [1 => 'Ca 1', 2 => 'Ca 2', 3 => 'Ca 3', 4 => 'Hành chính', 5 => 'Khác', 6 => 'Ca 4'];

    // Nhóm công đoạn hiển thị; Cân NL Khác (stage 2) gộp vào Cân NL
    const STAGE_GROUPS = [
        1 => ['label' => 'Cân Nguyên Liệu', 'icon' => 'fa-balance-scale', 'grad' => 'g-blue'],
        3 => ['label' => 'Pha Chế', 'icon' => 'fa-flask', 'grad' => 'g-teal'],
        4 => ['label' => 'Trộn Hoàn Tất', 'icon' => 'fa-blender', 'grad' => 'g-purple'],
        5 => ['label' => 'Định Hình', 'icon' => 'fa-capsules', 'grad' => 'g-orange'],
        6 => ['label' => 'Bao Phim', 'icon' => 'fa-circle-notch', 'grad' => 'g-rose'],
        7 => ['label' => 'Đóng Gói', 'icon' => 'fa-box-open', 'grad' => 'g-slate'],
    ];

    // Bắt đầu áp dụng Nhận/Trả phòng: lô có actual_start trước mốc này (xác nhận ✓ ở trang cũ mà chưa ✓✓) không giữ phòng,
    // tránh bấm Trả phòng ghi actual_end_clearning trễ nhiều ngày cho lô cũ
    const TRACK_FROM = '2026-10-05 00:00:00';

    // Mốc trả phòng của 1 dòng stage_plan: lịch bảo trì không có vệ sinh (HC) xong ở actual_end, còn lại ở actual_end_clearning
    private const RELEASE_SQL = 'CASE WHEN stage_code = ' . self::MAINTENANCE_STAGE . ' AND title_clearning IS NULL THEN actual_end ELSE actual_end_clearning END';

    // Loại lịch bảo trì của 1 dòng stage_plan sp (cần left join quota_maintenance qm)
    private const MAINTENANCE_TYPE_SQL = "COALESCE(NULLIF(SUBSTRING_INDEX(qm.block, '-', 1), ''), 'BT')";

    /* =========================================================
       ĐỌC TRẠNG THÁI
       ========================================================= */

    /** $roomIds: chỉ lấy các phòng này (trang Ghi Nhận Sản Xuất: phòng người đăng nhập đang được phân công) */
    public function board(string $deparmentCode, ?array $roomIds = null): Collection
    {
        if ($roomIds === []) {
            return collect();
        }

        $rooms = $this->roomQuery()
            ->where('deparment_code', $deparmentCode)
            ->when($roomIds !== null, fn($q) => $q->whereIn('id', $roomIds))
            ->get();
        $this->attachStates($rooms);

        return $rooms;
    }

    /**
     * Phân công đang hiệu lực lúc này của 1 nhân viên (MSNV = employees.code = user_management.userName) tại các phòng
     * của 1 phân xưởng, theo Lịch Công Tác → Sản Xuất. Cùng điều kiện giờ với onDutyStaff() để card hiện đúng người.
     */
    public function assignmentsOf(string $employeeCode, string $deparmentCode): Collection
    {
        $now = now();

        return DB::table('assignments as a')
            ->join('assignment_personnel as ap', 'ap.assignment_id', '=', 'a.id')
            ->join('employees as e', 'e.id', '=', 'ap.personnel_id')
            ->join('room as r', 'r.id', '=', 'a.room_id')
            ->where('e.code', $employeeCode)
            ->where('a.deparment_code', $deparmentCode)
            ->whereBetween('a.start', [$now->copy()->subDays(2), $now->copy()->addDay()])
            ->where('a.active', 1)
            ->where('r.active', 1)
            ->whereRaw('COALESCE(ap.start, a.start) <= ?', [$now])
            ->whereRaw('COALESCE(ap.end, a.end) > ?', [$now])
            ->orderBy('r.stage_code')
            ->orderBy('r.order_by')
            ->get(['a.room_id', 'a.Sheet', 'r.code as room_code', DB::raw('COALESCE(ap.start, a.start) AS start'), DB::raw('COALESCE(ap.end, a.end) AS end')])
            ->map(function ($a) {
                $a->shift = self::SHIFT_NAMES[$a->Sheet] ?? ('Ca ' . $a->Sheet);
                return $a;
            });
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
     * Gắn vào mỗi phòng: ->st (state, label, since = lúc nhận phòng, plans = các lô đang giữ phòng), ->staff, ->stage_group.
     */
    private function attachStates(Collection $rooms): void
    {
        $ids = $rooms->pluck('id')->all();
        if (!$ids) {
            return;
        }

        $released = $this->releasedAt($ids);
        $open = $this->openPlans($ids, $released);
        $details = $this->planDetails($open->flatten()->pluck('id')->all());
        $staff = $this->onDutyStaff($rooms);

        foreach ($rooms as $room) {
            $plans = $open->get($room->id, collect())->map(fn($p) => $details->get($p->id))->filter()->values();
            $state = $this->stateOf($plans);

            $room->st = (object) [
                'state' => $state,
                'label' => self::STATE_LABELS[$state],
                'since' => $this->sinceOf($state, $plans),
                'plans' => $plans,
            ];
            $room->stage_group = in_array((int) $room->stage_code, self::GROUP_STAGES, true) ? 1 : (int) $room->stage_code;
            $room->staff = $staff->get($room->id, collect());
        }
    }

    /**
     * Lịch sử Nhận / Trả phòng của 1 phòng (từ TRACK_FROM, mới nhất trước). Các dòng nhận và trả cùng lúc (phòng cân nhiều
     * lô, bảo trì nhiều thiết bị) gộp thành 1 lượt. Người thao tác lấy từ audittriallog (action do receive/release/
     * receiveCleaning ghi); mốc ghi ở trang khác (Xác nhận hoàn thành) không có log thì để trống.
     */
    public function history(int $roomId, int $limit = 150): Collection
    {
        $rows = DB::table('stage_plan')
            ->where('resourceId', $roomId)
            ->where('active', 1)
            ->where('actual_start', '>=', self::TRACK_FROM)
            ->orderByDesc('actual_start')
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'actual_start', 'actual_end', 'actual_start_clearning', 'actual_end_clearning', 'title_clearning']);
        if ($rows->isEmpty()) {
            return collect();
        }

        $ids = $rows->pluck('id')->all();
        $details = $this->planDetails($ids);

        // Người thao tác: lần ghi log gần nhất của mỗi (dòng, thao tác)
        $logs = DB::table('audittriallog')
            ->where('table_Audit', 'stage_plan')
            ->whereIn('record_Id_AuditTrial', $ids)
            ->whereIn('action', ['Nhận phòng', 'Trả phòng', 'Nhận phòng vệ sinh sau BT', 'Trả phòng vệ sinh sau BT'])
            ->orderBy('id')
            ->get(['record_Id_AuditTrial', 'action', 'userName'])
            ->mapWithKeys(fn($l) => [$l->record_Id_AuditTrial . '|' . $l->action => $l->userName]);
        $names = DB::table('user_management')->whereIn('userName', $logs->values()->unique()->all())->pluck('fullName', 'userName');
        $who = fn($id, $action) => ($u = $logs->get($id . '|' . $action)) ? ($names[$u] ?? $u) : null;

        return $rows->map(function ($r) use ($details, $who) {
            $d = $details->get($r->id);
            $maint = $d && $d->maintenance;
            $cleanAfter = $maint && $r->title_clearning; // bảo trì có vệ sinh sau BT
            $released = $maint && !$cleanAfter ? $r->actual_end : $r->actual_end_clearning;

            return (object) [
                'id'             => $r->id,
                'label'          => $d->label ?? ('#' . $r->id),
                'maintenance'    => $maint,
                'received_at'    => $r->actual_start,
                'received_by'    => $who($r->id, 'Nhận phòng'),
                // Bảo trì có vệ sinh: KT bảo trì + nhận phòng vệ sinh nằm giữa nhận và trả
                'maint_end_at'   => $cleanAfter ? $r->actual_end : null,
                'maint_end_by'   => $cleanAfter ? $who($r->id, 'Trả phòng') : null,
                'clean_start_at' => $cleanAfter ? $r->actual_start_clearning : null,
                'clean_start_by' => $cleanAfter ? $who($r->id, 'Nhận phòng vệ sinh sau BT') : null,
                'released_at'    => $released,
                'released_by'    => $released ? $who($r->id, $cleanAfter ? 'Trả phòng vệ sinh sau BT' : 'Trả phòng') : null,
            ];
        })
            // Gộp các dòng cùng lượt nhận + trả
            ->groupBy(fn($h) => $h->received_at . '|' . $h->released_at)
            ->map(function ($g) {
                $h = clone $g->first();
                $h->labels = $g->pluck('label')->values();
                return $h;
            })
            ->values();
    }

    /**
     * Trạng thái phòng theo các dòng đang giữ phòng (planDetails): còn dòng chưa kết thúc (lô sản xuất, hay lịch bảo trì
     * chưa có actual_end) là Phòng Bận; chỉ còn lịch bảo trì đã xong chờ vệ sinh thì xét actual_start_clearning.
     */
    private function stateOf(Collection $plans): int
    {
        if ($plans->isEmpty()) {
            return self::READY;
        }
        if ($plans->contains(fn($p) => !$p->maintenance || !$p->actual_end)) {
            return self::BUSY;
        }

        return $plans->contains(fn($p) => !$p->actual_start_clearning) ? self::CLEAN_WAIT : self::CLEANING;
    }

    /** Mốc bắt đầu của trạng thái: nhận phòng / kết thúc bảo trì / nhận phòng vệ sinh */
    private function sinceOf(int $state, Collection $plans): ?Carbon
    {
        $t = match ($state) {
            self::BUSY       => $plans->min('actual_start'),
            self::CLEAN_WAIT => $plans->max('actual_end'),
            self::CLEANING   => $plans->min('actual_start_clearning'),
            default          => null,
        };

        return $t ? Carbon::parse($t) : null;
    }

    /**
     * Phòng đang bận của 1 phân xưởng (cho thanh "đang sản xuất" trên lịch Gantt): room id => các lô đang giữ phòng
     * (planDetails, có actual_start). Không tính nhân sự như board().
     */
    public function runningRooms(string $deparmentCode): Collection
    {
        $ids = $this->roomQuery()->where('deparment_code', $deparmentCode)->pluck('id')->all();
        if (!$ids) {
            return collect();
        }

        $open = $this->openPlans($ids, $this->releasedAt($ids));
        $details = $this->planDetails($open->flatten()->pluck('id')->all());

        return $open->map(fn($rows) => $rows->map(fn($p) => $details->get($p->id))->filter()->values())
            ->filter(fn($plans) => $plans->isNotEmpty());
    }

    /**
     * Phòng mà 1 người đang giữ (trang Ghi Nhận Sản Xuất vẫn hiện dù hết phân công): người đã bấm Nhận phòng các dòng còn
     * đang giữ phòng (Phòng Bận), hoặc Nhận phòng vệ sinh sau BT (Đang VS Sau BT). stage_plan không có cột người nhận,
     * nên đọc từ audittriallog (userName = người đăng nhập lúc bấm).
     *
     * @return int[] room id
     */
    public function heldRoomIdsOf(string $userName, string $deparmentCode): array
    {
        $receive = [];  // stage_plan id => room id, đang chạy (chưa Trả phòng)
        $cleaning = []; // stage_plan id => room id, đang vệ sinh sau BT
        foreach ($this->runningRooms($deparmentCode) as $roomId => $plans) {
            foreach ($plans as $p) {
                if ($p->maintenance && $p->actual_end) {
                    if ($p->actual_start_clearning) {
                        $cleaning[$p->id] = $roomId;
                    }
                } else {
                    $receive[$p->id] = $roomId;
                }
            }
        }
        if (!$receive && !$cleaning) {
            return [];
        }

        return DB::table('audittriallog')
            ->where('table_Audit', 'stage_plan')
            ->where('userName', $userName)
            ->where(fn($q) => $q
                ->where(fn($q2) => $q2->where('action', 'Nhận phòng')->whereIn('record_Id_AuditTrial', array_keys($receive) ?: [0]))
                ->orWhere(fn($q2) => $q2->where('action', 'Nhận phòng vệ sinh sau BT')->whereIn('record_Id_AuditTrial', array_keys($cleaning) ?: [0])))
            ->pluck('record_Id_AuditTrial')
            ->map(fn($id) => $receive[$id] ?? $cleaning[$id] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** Lần trả phòng gần nhất của từng phòng */
    private function releasedAt(array $roomIds): array
    {
        return DB::table('stage_plan')
            ->whereIn('resourceId', $roomIds)
            ->where('active', 1)
            ->groupBy('resourceId')
            ->selectRaw('resourceId, MAX(' . self::RELEASE_SQL . ') AS released_at')
            ->pluck('released_at', 'resourceId')
            ->filter()
            ->all();
    }

    /** Các lô đang giữ phòng: đã nhận phòng (actual_start), chưa trả phòng, nhận sau lần trả phòng gần nhất */
    private function openPlans(array $roomIds, array $released): Collection
    {
        return DB::table('stage_plan')
            ->whereIn('resourceId', $roomIds)
            ->where('active', 1)
            ->where('actual_start', '>=', self::TRACK_FROM)
            ->whereRaw('(' . self::RELEASE_SQL . ') IS NULL')
            ->orderBy('actual_start')
            ->orderBy('id')
            ->get(['id', 'resourceId', 'actual_start'])
            ->filter(fn($p) => !isset($released[$p->resourceId]) || $p->actual_start >= $released[$p->resourceId])
            ->groupBy('resourceId');
    }

    public function planDetails(array $ids): Collection
    {
        $ids = array_values(array_unique(array_filter($ids)));
        if (!$ids) {
            return collect();
        }

        $rows = DB::table('stage_plan as sp')
            ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
            ->leftJoin('finished_product_category as fpc', 'sp.product_caterogy_id', '=', 'fpc.id')
            ->leftJoin('intermediate_category as ic', 'fpc.intermediate_code', '=', 'ic.intermediate_code')
            ->leftJoin('product_name as pn', 'ic.product_name_id', '=', 'pn.id')
            ->leftJoin('market as mk', 'fpc.market_id', '=', 'mk.id')
            // Lịch stage_code 8: product_caterogy_id là quota_maintenance.id (thiết bị), không phải thành phẩm
            ->leftJoin('quota_maintenance as qm', fn($j) => $j->on('sp.product_caterogy_id', '=', 'qm.id')->where('sp.stage_code', self::MAINTENANCE_STAGE))
            ->whereIn('sp.id', $ids)
            ->select(
                'sp.id',
                'sp.stage_code',
                'sp.title',
                'sp.start',
                'sp.end',
                'sp.end_clearning',
                'sp.title_clearning',
                'sp.resourceId',
                'sp.actual_start',
                'sp.actual_end',
                'sp.actual_start_clearning',
                'sp.start_clearning',
                'sp.Theoretical_yields',
                'pn.name as product_name',
                DB::raw('COALESCE(pm.actual_batch, pm.batch) AS batch'),
                'pm.is_val',
                'fpc.intermediate_code',
                'fpc.finished_product_code',
                'mk.code as market',
                'qm.inst_id',
                DB::raw('COALESCE(qm.Eqp_name, qm.inst_name) AS equipment'),
                DB::raw(self::MAINTENANCE_TYPE_SQL . ' AS maintenance_type')
            )
            ->get();

        foreach ($rows as $row) {
            $row->maintenance = (int) $row->stage_code === self::MAINTENANCE_STAGE;
            if ($row->maintenance) {
                // Tiêu đề lịch bảo trì là của cả nhóm thiết bị, có <br/> cho Gantt:
                // "PDS-205, PDS-275 _ MÁY RÂY RUNG :<br/> - PDS-205<br/> - PDS-275<br/> Ngày tới hạn: 12/10/2026"
                $raw = (string) $row->title;
                $row->title = trim(strip_tags(preg_split('/<br\s*\/?>/i', $raw)[0]), " \t\n\r-:");
                $row->due = preg_match('/Ngày tới hạn:\s*([\d\/]+)/u', strip_tags($raw), $m) ? $m[1] : null;
                // Mỗi dòng là 1 thiết bị: tên + mã thiết bị để phân biệt các dòng cùng nhóm
                $row->equipment_label = '[' . $row->maintenance_type . '] ' . ($row->equipment ?: $row->title)
                    . ($row->inst_id ? ' (' . $row->inst_id . ')' : '');
            }
            $row->unit = $row->stage_code <= 4 ? 'Kg' : 'ĐVL';
            $row->label = $row->maintenance
                ? $row->equipment_label
                : ($row->batch ? ($row->product_name ?? $row->title) . ' - ' . $row->batch : $row->title);
        }

        return $rows->keyBy('id');
    }

    /**
     * Nhân sự đang được phân công tại từng phòng lúc này, lấy từ Lịch Công Tác → Sản Xuất (assignments + assignment_personnel).
     */
    private function onDutyStaff(Collection $rooms): Collection
    {
        $now = now();

        return DB::table('assignments as a')
            ->join('assignment_personnel as ap', 'ap.assignment_id', '=', 'a.id')
            ->join('employees as e', 'e.id', '=', 'ap.personnel_id')
            ->whereIn('a.deparment_code', $rooms->pluck('deparment_code')->unique()->values()->all())
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
            ->addSelect(['a.id', 'a.room_id', 'a.Sheet', 'a.start', 'a.end', 'ap.start as person_start', 'ap.end as person_end', 'ap.notification', 'e.code', 'e.name'])
            ->get()
            ->groupBy('room_id')
            ->map(fn($rows) => $rows->groupBy('id')->map(function ($people) {
                $a = $people->first();
                return (object) [
                    'shift'  => self::SHIFT_NAMES[$a->Sheet] ?? ('Ca ' . $a->Sheet),
                    'people' => $people->map(fn($p) => (object) [
                        'label' => chr(65 + (int) $p->position),
                        'code'  => $p->code,
                        'name'  => $p->name,
                        'start' => $p->person_start ?? $a->start,
                        'end'   => $p->person_end ?? $a->end,
                        'note'  => $p->notification,
                    ])->values(),
                ];
            })->values());
    }

    /** Loại lịch bảo trì phòng ban được nhận phòng; null = phòng ban khác, chỉ nhận lô sản xuất */
    public static function maintenanceTypesFor(?string $department): ?array
    {
        return self::MAINTENANCE_TYPES_BY_DEPARTMENT[$department] ?? null;
    }

    /**
     * Lô / lịch bảo trì nhận phòng được: đã sắp lịch vào phòng, chưa nhận phòng, chưa hoàn thành.
     * Phòng cân (Cân NL / Cân NL Khác) nhận cả lô của 2 công đoạn và lô chưa gán phòng.
     * $department: EN chỉ thấy lịch BT/TI, QA chỉ thấy lịch HC, phòng ban khác chỉ thấy lô sản xuất.
     */
    private function candidateQuery(object $room, ?string $department)
    {
        $weighing = in_array((int) $room->stage_code, self::GROUP_STAGES, true);
        $types = self::maintenanceTypesFor($department);

        $q = DB::table('stage_plan as sp');
        if ($types) {
            $q->leftJoin('quota_maintenance as qm', 'sp.product_caterogy_id', '=', 'qm.id')
                ->where('sp.stage_code', self::MAINTENANCE_STAGE)
                ->whereIn(DB::raw(self::MAINTENANCE_TYPE_SQL), $types);
        } else {
            $q->whereIn('sp.stage_code', $weighing ? self::GROUP_STAGES : [(int) $room->stage_code]);
        }

        return $q
            ->where('sp.active', 1)
            ->where('sp.deparment_code', $room->deparment_code)
            ->where('sp.finished', 0)
            ->whereNull('sp.actual_start')
            ->whereNotNull('sp.start')
            ->where(function ($q) use ($room, $weighing) {
                $q->where('sp.resourceId', $room->id);
                if ($weighing) {
                    $q->orWhere(fn($q2) => $q2->whereNull('sp.resourceId')->where('sp.stage_code', '!=', self::MAINTENANCE_STAGE));
                }
            });
    }

    public function candidatePlans(object $room, ?string $department): Collection
    {
        $ids = $this->candidateQuery($room, $department)
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
       THAO TÁC
       ========================================================= */

    /**
     * Nhận phòng: ghi actual_start = giờ hệ thống cho lô / lịch bảo trì đã chọn. Chọn nhiều: phòng cân (lô cùng mã BTP)
     * hoặc nhiều lịch bảo trì (mỗi dòng 1 thiết bị). Lô nhận ở phòng cân mà chưa gán phòng thì gán vào phòng này.
     * $department: phòng ban người bấm, quyết định loại lịch được nhận (maintenanceTypesFor).
     */
    public function receive(int $roomId, array $stagePlanIds, ?string $department): string
    {
        $stagePlanIds = array_values(array_unique(array_map('intval', array_filter($stagePlanIds))));
        if (!$stagePlanIds) {
            throw new ProductionExecutionException('❌ Chọn lô / lịch cần nhận phòng');
        }

        return DB::transaction(function () use ($roomId, $stagePlanIds, $department) {
            [$room] = $this->lockState($roomId, [self::READY]);

            $plans = $this->candidateQuery($room, $department)->whereIn('sp.id', $stagePlanIds)->lockForUpdate()
                ->get(['sp.id', 'sp.stage_code', 'sp.resourceId']);
            if ($plans->count() !== count($stagePlanIds)) {
                throw new ProductionExecutionException('❌ Lô / lịch không hợp lệ, không thuộc phòng ban của bạn, đã hoàn thành hoặc đã được nhận phòng', 409);
            }
            $maintenance = $plans->where('stage_code', self::MAINTENANCE_STAGE)->count();
            if ($maintenance && $maintenance !== $plans->count()) {
                throw new ProductionExecutionException('❌ Không nhận chung lịch bảo trì với lô sản xuất');
            }
            if (count($stagePlanIds) > 1 && !$maintenance) {
                if (!in_array((int) $room->stage_code, self::GROUP_STAGES, true)) {
                    throw new ProductionExecutionException('❌ Chỉ phòng cân nguyên liệu được nhận phòng cho nhiều lô');
                }
                $btp = DB::table('stage_plan as sp')
                    ->join('finished_product_category as fpc', 'sp.product_caterogy_id', '=', 'fpc.id')
                    ->whereIn('sp.id', $stagePlanIds)
                    ->distinct()
                    ->count('fpc.intermediate_code');
                if ($btp > 1) {
                    throw new ProductionExecutionException('❌ Chỉ nhận chung các lô cùng mã bán thành phẩm (BTP)');
                }
            }

            $now = now();
            foreach ($plans as $p) {
                DB::table('stage_plan')->where('id', $p->id)->update(array_filter([
                    'actual_start' => $now,
                    'resourceId'   => $p->resourceId ? null : $room->id,
                ]));
            }

            $labels = $this->planDetails($stagePlanIds)->pluck('label', 'id');
            foreach ($labels as $id => $label) {
                AuditTrialController::log('Nhận phòng', 'stage_plan', $id, 'NA',
                    'Phòng ' . $room->code . ' · actual_start ' . $now->format('Y-m-d H:i:s') . ' · ' . $label);
            }

            return '✅ Đã nhận phòng ' . $room->code . ' lúc ' . $now->format('H:i') . ' cho ' . $labels->implode(', ');
        });
    }

    /**
     * Trả phòng = giờ hệ thống cho mọi dòng đang giữ phòng.
     * - Phòng Bận: lô sản xuất ghi actual_end_clearning; lịch bảo trì ghi actual_end (lịch có vệ sinh → Chờ VS Sau BT).
     * - Đang VS Sau BT: ghi actual_end_clearning cho các lịch bảo trì → Sẵn Sàng.
     * Sau khi lưu, nếu công tắc tịnh tuyến của phân xưởng đang bật thì dịch lịch lý thuyết theo giờ trả phòng của lô sản xuất
     * (ScheduleRerouteService::rerouteOnRelease). Lỗi tịnh tuyến không làm hỏng việc trả phòng đã lưu.
     *
     * @return array{message: string, reroute: ?array}
     */
    public function release(int $roomId): array
    {
        [$message, $deparmentCode, $productionIds] = DB::transaction(function () use ($roomId) {
            [$room, $state, $plans] = $this->lockState($roomId, [self::BUSY, self::CLEANING]);

            $now = now();
            foreach ($plans as $p) {
                $column = $state === self::CLEANING || !$p->maintenance ? 'actual_end_clearning' : 'actual_end';
                DB::table('stage_plan')->where('id', $p->id)->update([$column => $now]);
                AuditTrialController::log($state === self::CLEANING ? 'Trả phòng vệ sinh sau BT' : 'Trả phòng', 'stage_plan', $p->id, 'NA',
                    'Phòng ' . $room->code . ' · ' . $column . ' ' . $now->format('Y-m-d H:i:s') . ' · ' . $p->label);
            }

            $after = $this->stateOf($this->planDetails(
                $this->openPlans([$room->id], $this->releasedAt([$room->id]))->get($room->id, collect())->pluck('id')->all()
            )->values());

            return [
                '✅ Đã trả phòng ' . $room->code . ' lúc ' . $now->format('H:i')
                    . ($after === self::CLEAN_WAIT ? ' · phòng chuyển sang ' . self::STATE_LABELS[self::CLEAN_WAIT] : ''),
                $room->deparment_code,
                $state === self::BUSY ? $plans->where('maintenance', false)->pluck('id')->all() : [],
            ];
        });

        return ['message' => $message, 'reroute' => $this->rerouteAfterRelease($productionIds, $deparmentCode)];
    }

    /**
     * Tịnh tuyến lịch theo giờ trả phòng cho các lô vừa trả (công tắc của phân xưởng phải đang bật).
     * null = không chạy (tắt công tắc / không có lô sản xuất); gộp kết quả nếu nhiều lô.
     */
    private function rerouteAfterRelease(array $stagePlanIds, string $deparmentCode): ?array
    {
        if (!$stagePlanIds || !RealtimeRerouteSwitch::enabled($deparmentCode)) {
            return null;
        }

        $result = ['count' => 0, 'delta' => 0, 'cleaning_moved' => false, 'error' => false];
        foreach ($stagePlanIds as $id) {
            try {
                $r = app(ScheduleRerouteService::class)->rerouteOnRelease((int) $id);
                $result['count'] += count($r['changes']);
                $result['delta'] = $result['delta'] ?: $r['delta_minutes'];
                $result['cleaning_moved'] = $result['cleaning_moved'] || $r['source_cleaning_moved'];
            } catch (\Throwable $e) {
                $result['error'] = true;
                Log::error('[Reroute] Tịnh tuyến khi Trả phòng thất bại cho stage_plan ' . $id, [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return $result;
    }

    /** Nhận phòng vệ sinh sau bảo trì: actual_start_clearning = giờ hệ thống cho các lịch bảo trì đã xong đang chờ vệ sinh */
    public function receiveCleaning(int $roomId): string
    {
        return DB::transaction(function () use ($roomId) {
            [$room, , $plans] = $this->lockState($roomId, [self::CLEAN_WAIT]);

            $now = now();
            foreach ($plans->whereNull('actual_start_clearning') as $p) {
                DB::table('stage_plan')->where('id', $p->id)->update(['actual_start_clearning' => $now]);
                AuditTrialController::log('Nhận phòng vệ sinh sau BT', 'stage_plan', $p->id, 'NA',
                    'Phòng ' . $room->code . ' · actual_start_clearning ' . $now->format('Y-m-d H:i:s') . ' · ' . $p->label);
            }

            return '✅ Đã nhận phòng ' . $room->code . ' vệ sinh sau BT lúc ' . $now->format('H:i');
        });
    }

    /**
     * Khóa dòng phòng (2 người bấm cùng lúc thì người sau chờ) và kiểm tra phòng đang ở 1 trong các trạng thái mong đợi.
     * Trả về [room, state, các dòng đang giữ phòng (planDetails)].
     */
    private function lockState(int $roomId, array $expected): array
    {
        $room = DB::table('room')->where('id', $roomId)->where('active', 1)->lockForUpdate()->first();
        if (!$room) {
            throw new ProductionExecutionException('❌ Không tìm thấy phòng sản xuất', 404);
        }

        $ids = $this->openPlans([$room->id], $this->releasedAt([$room->id]))->get($room->id, collect())->pluck('id')->all();
        $plans = $this->planDetails($ids)->values();
        $state = $this->stateOf($plans);
        if (!in_array($state, $expected, true)) {
            throw new ProductionExecutionException('⚠️ Phòng đang ' . self::STATE_LABELS[$state]
                . ' (có thể do người khác vừa thao tác). Đã tải lại thông tin phòng.', 409);
        }

        return [$room, $state, $plans];
    }
}
