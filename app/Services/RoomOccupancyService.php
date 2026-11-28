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
 * - Bảng room_execution_log / room_execution_batch của bản cũ đã bỏ (26/11/2026).
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

    const MAINTENANCE_TYPE_LABELS = ['BT' => 'Bảo trì', 'TI' => 'Tiện ích', 'HC' => 'Hiệu chuẩn'];

    // Modal Nhận phòng (12/10/2026, user quy định): nhân viên phân xưởng nhận cả lô sản xuất lẫn lịch BT / TI / HC, chọn
    // 1 trong 2 chế độ. EN / QA không còn Nhận / Trả phòng (ProductionExecutionController::canOperate).
    const RECEIVE_PRODUCTION = 'production';
    const RECEIVE_MAINTENANCE = 'maintenance';

    // Hoàn tác (06/10/2026): chỉ thao tác MỚI NHẤT của phòng, do chính người đó bấm, trong 2 phút. Quyền = quyền của thao tác gốc.
    const UNDO_SECONDS = 120;
    const UNDO_ACTIONS = [
        'Nhận phòng'                => 'execution_receive',
        'Nhận phòng vệ sinh sau BT' => 'execution_receive_cleaning',
        'Trả phòng'                 => 'execution_release',
        'Trả phòng vệ sinh sau BT'  => 'execution_release',
    ];
    const UNDO_PREFIX = 'Hoàn tác ';
    // Ghi vào audittriallog.new_values của dòng Nhận phòng khi lô chưa gán phòng được gán vào phòng (để hoàn tác bỏ gán)
    const ASSIGNED_MARK = ' · gán phòng';
    const REROUTE_AUDIT = 'Tịnh tuyến khi Trả phòng';
    const REROUTE_RECEIVE_AUDIT = 'Tịnh tuyến khi Nhận phòng';

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

    /** $roomIds: chỉ lấy các phòng này (trang Nhận – Trả Phòng: phòng người đăng nhập đang được phân công) */
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

    /**
     * Phòng sản xuất (công đoạn 1-7). Phòng chỉ dùng cho bảo trì (room.only_maintenance, "Khu Vực Không Sắp Lịch Sản Xuất")
     * không Nhận / Trả phòng (user quy định 12/10/2026).
     */
    private function roomQuery()
    {
        return DB::table('room')
            ->where('active', 1)
            ->whereBetween('stage_code', [1, 7])
            ->where('only_maintenance', 0)
            ->orderBy('stage_code')
            ->orderBy('order_by')
            ->orderBy('code')
            ->select('id', 'code', 'name', 'main_equiment_name', 'stage', 'stage_code', 'deparment_code', 'active', 'max_parallel');
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
                'state'   => $state,
                'label'   => self::STATE_LABELS[$state],
                'since'   => $this->sinceOf($state, $plans),
                'plans'   => $plans,
                // Phòng đôi gói đầu: đang Bận với lô sản xuất, còn chỗ cho lô cùng mã BTP
                'overlap' => self::canOverlap($room, $state, $plans),
                // Đóng gói lô chia thị trường: số lô cùng nhóm chia (main_parkaging_id) còn nhận thêm được
                'split'   => ($group = self::splitGroup($room, $state, $plans))
                    ? $this->splitCandidateQuery($room, $group)->count() : 0,
            ];
            $room->stage_group = in_array((int) $room->stage_code, self::GROUP_STAGES, true) ? 1 : (int) $room->stage_code;
            $room->staff = $staff->get($room->id, collect());
        }

        $undos = $this->pendingUndos($ids, $rooms->mapWithKeys(fn($r) => [$r->id => $r->st->state])->all());
        foreach ($rooms as $room) {
            $room->undo = $undos[$room->id] ?? null;
        }
    }

    /**
     * Thao tác hoàn tác được của từng phòng (room id => {action, plan_ids, until, permission, assigned}).
     * Điều kiện: là thao tác mới nhất của phòng (audittriallog, kể cả dòng "Hoàn tác ..."), do người đang đăng nhập bấm,
     * trong UNDO_SECONDS, và trạng thái hiện tại của phòng đúng là kết quả của thao tác đó.
     * stage_plan không có cột người / giờ thao tác nên đọc từ audittriallog.
     */
    private function pendingUndos(array $roomIds, array $states): array
    {
        $user = session('user')['userName'] ?? null;
        if (!$user || !$roomIds) {
            return [];
        }

        $actions = array_keys(self::UNDO_ACTIONS);
        $rows = DB::table('audittriallog as a')
            ->join('stage_plan as sp', 'sp.id', '=', 'a.record_Id_AuditTrial')
            ->where('a.table_Audit', 'stage_plan')
            ->where('a.created_at', '>=', now()->subSeconds(self::UNDO_SECONDS))
            ->whereIn('a.action', array_merge($actions, array_map(fn($x) => self::UNDO_PREFIX . $x, $actions)))
            ->whereIn('sp.resourceId', $roomIds)
            ->orderByDesc('a.created_at')
            ->orderByDesc('a.id')
            ->get(['a.action', 'a.userName', 'a.created_at', 'a.new_values', 'a.record_Id_AuditTrial as plan_id', 'sp.resourceId']);

        $expected = [
            'Nhận phòng'                => [self::BUSY],
            'Nhận phòng vệ sinh sau BT' => [self::CLEANING],
            'Trả phòng'                 => [self::READY, self::CLEAN_WAIT, self::BUSY], // BUSY: trả 1 lô ở phòng đôi gói đầu
            'Trả phòng vệ sinh sau BT'  => [self::READY],
        ];

        $result = [];
        foreach ($rows->groupBy('resourceId') as $roomId => $list) {
            $first = $list->first();
            if (!isset(self::UNDO_ACTIONS[$first->action]) || $first->userName !== $user
                || !in_array($states[$roomId] ?? null, $expected[$first->action], true)) {
                continue;
            }

            // Cùng lượt thao tác: cùng hành động + người, gần nhau vài giây (phòng cân nhiều lô, bảo trì nhiều thiết bị)
            $t = Carbon::parse($first->created_at);
            $group = $list->filter(fn($r) => $r->action === $first->action && $r->userName === $user
                && abs(Carbon::parse($r->created_at)->diffInSeconds($t)) <= 5);

            $result[$roomId] = (object) [
                'action'     => $first->action,
                'plan_ids'   => $group->pluck('plan_id')->unique()->values()->all(),
                'assigned'   => $group->filter(fn($r) => str_contains((string) $r->new_values, self::ASSIGNED_MARK))->pluck('plan_id')->all(),
                'until'      => $t->copy()->addSeconds(self::UNDO_SECONDS),
                'permission' => self::UNDO_ACTIONS[$first->action],
            ];
        }

        return $result;
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
     * Các lô / lịch BT đang giữ phòng (đã Nhận phòng, chưa Trả phòng) của các phòng, cùng quy tắc trạng thái phòng của trang
     * này (kể cả phòng đôi gói đầu). Dùng cho lệnh quét tịnh tuyến (ScheduleRerouteService::overrunHolds).
     *
     * @param  int[]  $roomIds
     * @return Collection room id => Collection các dòng {id, resourceId, actual_start}
     */
    public function openPlansOf(array $roomIds): Collection
    {
        $roomIds = array_values(array_unique(array_filter($roomIds)));

        return $roomIds ? $this->openPlans($roomIds, $this->releasedAt($roomIds)) : collect();
    }

    /**
     * Khoảng phòng đang bị lô giữ phòng chiếm, cho sắp lịch tự động: từ Nhận phòng tới dự kiến trả phòng, cùng quy tắc
     * với thanh "đang diễn ra" trên Gantt (Nhận phòng + thời lượng giữ phòng theo lịch = KT vệ sinh − BĐ; quá lịch thì
     * tới hiện tại). null = mọi phòng đang có lô giữ.
     *
     * @return array<int, array{0: Carbon, 1: Carbon}> room id => [nhận phòng, dự kiến trả phòng]
     */
    public function heldUntil(?array $roomIds = null): array
    {
        $roomIds ??= DB::table('stage_plan')
            ->where('active', 1)
            ->where('actual_start', '>=', self::TRACK_FROM)
            ->whereRaw('(' . self::RELEASE_SQL . ') IS NULL')
            ->whereNotNull('resourceId')
            ->distinct()
            ->pluck('resourceId')
            ->all();
        if (!$roomIds) {
            return [];
        }

        $open = $this->openPlans($roomIds, $this->releasedAt($roomIds));
        $planned = DB::table('stage_plan')
            ->whereIn('id', $open->flatten()->pluck('id')->all() ?: [0])
            ->get(['id', 'start', 'end', 'end_clearning'])
            ->keyBy('id');

        $now = now();
        $held = [];
        foreach ($open as $roomId => $rows) {
            $received = Carbon::parse($rows->min('actual_start'));
            // Mỗi lô: nhận phòng + thời lượng theo lịch (phòng đôi gói đầu: lô 2 nhận sau nên tính riêng từng lô)
            $until = $rows->map(function ($r) use ($planned) {
                $p = $planned->get($r->id);
                $sec = $p && $p->start && ($p->end_clearning ?? $p->end)
                    ? max(0, (int) Carbon::parse($p->start)->diffInSeconds(Carbon::parse($p->end_clearning ?? $p->end), false)) : 0;
                return Carbon::parse($r->actual_start)->addSeconds($sec);
            })->max();
            $held[(int) $roomId] = [$received, $until->max($now)];
        }

        return $held;
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

    /**
     * Lô / lịch bảo trì đã Trả phòng nhưng chưa xác nhận hoàn thành (Gantt vẽ màu riêng "chờ xác nhận"):
     * lô sản xuất chưa xác nhận vệ sinh ✓✓ (actual_start_clearning NULL), lịch bảo trì chưa finished = 1.
     * Chỉ lô nhận phòng từ TRACK_FROM, có khoảng Nhận → Trả phòng giao với [$from, $to]. Mỗi dòng có thêm ->released_at.
     */
    public function awaitingConfirmation(string $deparmentCode, $from, $to): Collection
    {
        $ids = $this->roomQuery()->where('deparment_code', $deparmentCode)->pluck('id')->all();
        if (!$ids) {
            return collect();
        }

        $rows = DB::table('stage_plan')
            ->whereIn('resourceId', $ids)
            ->where('active', 1)
            ->where('actual_start', '>=', self::TRACK_FROM)
            ->where('actual_start', '<=', $to)
            ->whereRaw('(' . self::RELEASE_SQL . ') >= ?', [$from])
            ->where(fn($q) => $q
                ->where(fn($q2) => $q2->where('stage_code', self::MAINTENANCE_STAGE)->where('finished', 0))
                ->orWhere(fn($q2) => $q2->where('stage_code', '!=', self::MAINTENANCE_STAGE)->whereNull('actual_start_clearning')))
            ->selectRaw('id, (' . self::RELEASE_SQL . ') AS released_at')
            ->pluck('released_at', 'id');

        return $this->planDetails($rows->keys()->all())
            ->each(fn($p) => $p->released_at = $rows[$p->id])
            ->values();
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

    /**
     * Các lô đang giữ phòng: đã nhận phòng (actual_start), chưa trả phòng, nhận sau lần trả phòng gần nhất.
     * Phòng đôi gói đầu (room.max_parallel = n > 1): lô 1 trả phòng khi lô 2 còn chạy nên không lọc theo lần trả phòng gần
     * nhất; lô chỉ bị coi là bỏ quên khi đã có từ n lô nhận phòng SAU nó được trả phòng.
     */
    private function openPlans(array $roomIds, array $released): Collection
    {
        $open = DB::table('stage_plan')
            ->whereIn('resourceId', $roomIds)
            ->where('active', 1)
            ->where('actual_start', '>=', self::TRACK_FROM)
            ->whereRaw('(' . self::RELEASE_SQL . ') IS NULL')
            ->orderBy('actual_start')
            ->orderBy('id')
            ->get(['id', 'resourceId', 'actual_start']);

        $parallel = $open->isEmpty() ? collect() : DB::table('room')
            ->whereIn('id', $open->pluck('resourceId')->unique()->values()->all())
            ->where('max_parallel', '>', 1)
            ->pluck('max_parallel', 'id');
        // Lô đã trả phòng của phòng đôi, nhận từ lô mở sớm nhất trở đi
        $closed = $parallel->isEmpty() ? collect() : DB::table('stage_plan')
            ->whereIn('resourceId', $parallel->keys()->all())
            ->where('active', 1)
            ->where('actual_start', '>=', $open->whereIn('resourceId', $parallel->keys()->all())->min('actual_start'))
            ->whereRaw('(' . self::RELEASE_SQL . ') IS NOT NULL')
            ->get(['resourceId', 'actual_start'])
            ->groupBy('resourceId');

        return $open
            ->filter(function ($p) use ($released, $parallel, $closed) {
                if ($n = (int) ($parallel[$p->resourceId] ?? 0)) {
                    return $closed->get($p->resourceId, collect())->where('actual_start', '>', $p->actual_start)->count() < $n;
                }
                return !isset($released[$p->resourceId]) || $p->actual_start >= $released[$p->resourceId];
            })
            ->groupBy('resourceId');
    }

    /** Phòng đôi gói đầu đang Bận chỉ với lô sản xuất (không phải phòng cân), còn chỗ nhận thêm lô */
    private static function canOverlap(object $room, int $state, Collection $plans): bool
    {
        return $state === self::BUSY
            && (int) ($room->max_parallel ?? 1) > 1
            && !in_array((int) $room->stage_code, self::GROUP_STAGES, true)
            && $plans->isNotEmpty()
            && $plans->every(fn($p) => !$p->maintenance)
            && $plans->count() < (int) $room->max_parallel;
    }

    // Công đoạn được nhận thêm lô chia thị trường (cùng plan_master.main_parkaging_id) khi phòng đang Bận
    const SPLIT_STAGE = 7;

    /**
     * Đóng gói lô chia thị trường (27/11/2026): phòng Đóng Gói đang Bận chỉ với lô sản xuất cùng 1 nhóm chia
     * (plan_master.main_parkaging_id) → trả main_parkaging_id để nhận thêm lô cùng nhóm mà không phải Trả phòng; null nếu không.
     */
    private static function splitGroup(object $room, int $state, Collection $plans): ?int
    {
        if ($state !== self::BUSY || (int) $room->stage_code !== self::SPLIT_STAGE || $plans->isEmpty()
            || $plans->contains(fn($p) => $p->maintenance || !$p->pack_group)) {
            return null;
        }
        $groups = $plans->pluck('pack_group')->unique();

        return $groups->count() === 1 ? (int) $groups->first() : null;
    }

    /** Lô cùng nhóm chia thị trường chưa nhận phòng, đã sắp lịch vào phòng này */
    private function splitCandidateQuery(object $room, int $group)
    {
        return $this->candidateQuery($room, self::RECEIVE_PRODUCTION)
            ->join('plan_master as spm', 'spm.id', '=', 'sp.plan_master_id')
            ->where('spm.main_parkaging_id', $group);
    }

    /** Danh sách lô cho modal "Nhận lô chia thị trường" (rỗng nếu phòng không ở trạng thái nhận thêm được) */
    public function splitCandidatePlans(int $roomId): Collection
    {
        $room = $this->room($roomId);
        $group = $room ? self::splitGroup($room, $room->st->state, $room->st->plans) : null;
        if (!$group) {
            return collect();
        }
        $ids = $this->splitCandidateQuery($room, $group)->orderBy('sp.start')->orderBy('sp.id')->pluck('sp.id')->all();
        $details = $this->planDetails($ids);

        return collect($ids)->map(fn($id) => $details->get($id))->filter()->values();
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
            ->leftJoin('specification as spec', 'fpc.specification_id', '=', 'spec.id') // qui cách đóng gói (modal Nhận phòng ĐG)
            // Lịch stage_code 8: product_caterogy_id là quota_maintenance.id (thiết bị), không phải thành phẩm
            ->leftJoin('quota_maintenance as qm', fn($j) => $j->on('sp.product_caterogy_id', '=', 'qm.id')->where('sp.stage_code', self::MAINTENANCE_STAGE))
            ->whereIn('sp.id', $ids)
            ->select(
                'sp.id',
                'sp.stage_code',
                'sp.nextcessor_code', // nhãn loại cân trên thanh Nhận / Trả phòng của Gantt
                'sp.title',
                'sp.start',
                'sp.end',
                'sp.end_clearning',
                'sp.title_clearning',
                'sp.resourceId',
                'sp.actual_start',
                'sp.actual_end',
                'sp.actual_start_clearning',
                'sp.actual_end_clearning',
                'sp.start_clearning',
                'sp.Theoretical_yields',
                'pn.name as product_name',
                DB::raw('COALESCE(pm.actual_batch, pm.batch) AS batch'),
                'pm.is_val',
                'pm.excess_pro_return_lot',
                'pm.main_parkaging_id as pack_group', // nhóm chia thị trường (Đóng Gói nhận thêm lô cùng nhóm)
                'pm.percent_parkaging',
                'pm.number_parkaging',
                'fpc.intermediate_code',
                'fpc.finished_product_code',
                'mk.code as market',
                'spec.name as specification',
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
            $row->unit = $row->stage_code <= 4 ? 'Kg' : 'ĐV';
            // Cỡ lô hiển thị: Đóng Gói chia thị trường (percent_parkaging < 1) mỗi dòng chỉ là 1 phần lô,
            // stage_plan.Theoretical_yields vẫn là cỡ lô gốc → lấy số chia number_parkaging (đúng số, không làm tròn %),
            // thiếu thì cỡ lô gốc × percent_parkaging như các báo cáo.
            $row->theory = (float) $row->Theoretical_yields;
            $percent = (float) ($row->percent_parkaging ?? 1);
            if ((int) $row->stage_code === self::SPLIT_STAGE && $percent > 0 && $percent < 1) {
                $row->theory = (float) $row->number_parkaging > 0
                    ? (float) $row->number_parkaging
                    : $row->theory * $percent;
            }
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

    /**
     * Lô / lịch bảo trì nhận phòng được: đã sắp lịch vào phòng, chưa nhận phòng, chưa hoàn thành.
     * Phòng cân (Cân NL / Cân NL Khác) nhận cả lô của 2 công đoạn và lô chưa gán phòng.
     * $mode: RECEIVE_PRODUCTION = lô sản xuất của công đoạn phòng; RECEIVE_MAINTENANCE = lịch BT / TI / HC (stage_code 8).
     */
    public static function receiveMode(?string $mode): string
    {
        return $mode === self::RECEIVE_MAINTENANCE ? self::RECEIVE_MAINTENANCE : self::RECEIVE_PRODUCTION;
    }

    private function candidateQuery(object $room, ?string $mode)
    {
        $weighing = in_array((int) $room->stage_code, self::GROUP_STAGES, true);
        $maintenance = self::receiveMode($mode) === self::RECEIVE_MAINTENANCE;

        $q = DB::table('stage_plan as sp');
        if ($maintenance) {
            $q->where('sp.stage_code', self::MAINTENANCE_STAGE);
            $weighing = false; // lịch bảo trì luôn đã gán phòng
        } else {
            $q->whereIn('sp.stage_code', $weighing ? self::GROUP_STAGES : [(int) $room->stage_code]);
        }

        return $q
            ->where('sp.active', 1)
            ->where('sp.deparment_code', $room->deparment_code)
            ->where('sp.finished', 0)
            ->whereNull('sp.actual_start')
            ->whereNotNull('sp.start')
            // Bỏ lô mà công đoạn sau đã xác nhận hoàn thành (công đoạn này bị bỏ sót xác nhận) - không còn nhận phòng được
            ->whereNotExists(fn($q) => $q->select(DB::raw(1))
                ->from('stage_plan as nx')
                ->whereColumn('nx.plan_master_id', 'sp.plan_master_id')
                ->whereColumn('nx.code', 'sp.nextcessor_code')
                ->where('nx.active', 1)
                ->where('nx.finished', 1))
            ->where(function ($q) use ($room, $weighing) {
                $q->where('sp.resourceId', $room->id);
                if ($weighing) {
                    $q->orWhere(fn($q2) => $q2->whereNull('sp.resourceId')->where('sp.stage_code', '!=', self::MAINTENANCE_STAGE));
                }
            });
    }

    public function candidatePlans(object $room, ?string $mode): Collection
    {
        $ids = $this->candidateQuery($room, $mode)
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
     * $mode: RECEIVE_PRODUCTION (lô sản xuất) / RECEIVE_MAINTENANCE (lịch BT / TI / HC), chọn trong modal Nhận phòng.
     * Sau khi lưu: lô sản xuất bắt đầu trễ từ 30 phút thì tịnh tuyến chỉ đẩy trễ (ScheduleRerouteService::rerouteOnReceive);
     * reroute = null khi không dịch lô nào.
     *
     * @return array{message: string, reroute: ?array}
     */
    public function receive(int $roomId, array $stagePlanIds, ?string $mode): array
    {
        return $this->withRerouteLock($roomId, fn() => $this->receiveLocked($roomId, $stagePlanIds, $mode));
    }

    private function receiveLocked(int $roomId, array $stagePlanIds, ?string $mode): array
    {
        $stagePlanIds = array_values(array_unique(array_map('intval', array_filter($stagePlanIds))));
        if (!$stagePlanIds) {
            throw new ProductionExecutionException('❌ Chọn lô / lịch cần nhận phòng');
        }

        [$message, $deparmentCode, $productionIds, $receivedMaintenance] = DB::transaction(function () use ($roomId, $stagePlanIds, $mode) {
            [$room, $state, $held] = $this->lockState($roomId, [self::READY, self::BUSY]);
            // Phòng Đóng Gói Bận với lô chia thị trường: nhận thêm (1 hay nhiều) lô cùng nhóm main_parkaging_id
            $split = false;
            if ($state === self::BUSY && ($group = self::splitGroup($room, $state, $held))
                && self::receiveMode($mode) === self::RECEIVE_PRODUCTION) {
                $split = $this->splitCandidateQuery($room, $group)->whereIn('sp.id', $stagePlanIds)->count() === count($stagePlanIds);
                if (!$split && !self::canOverlap($room, $state, $held)) {
                    throw new ProductionExecutionException('❌ Chia thị trường: chỉ nhận thêm lô cùng nhóm chia lô với lô đang giữ phòng ('
                        . $held->pluck('batch')->filter()->unique()->implode(', ') . '), đã sắp lịch vào phòng này và chưa nhận phòng', 409);
                }
            }
            // Phòng Bận chỉ nhận thêm được lô gói đầu ở phòng đôi (room.max_parallel): 1 lô, cùng mã BTP với lô đang giữ
            $overlap = $state === self::BUSY && !$split;
            if ($overlap) {
                if (!self::canOverlap($room, $state, $held)) {
                    throw new ProductionExecutionException('⚠️ Phòng đang ' . self::STATE_LABELS[$state]
                        . ' (có thể do người khác vừa thao tác). Đã tải lại thông tin phòng.', 409);
                }
                if (self::receiveMode($mode) !== self::RECEIVE_PRODUCTION || count($stagePlanIds) !== 1) {
                    throw new ProductionExecutionException('❌ Gói đầu: chỉ nhận thêm 1 lô sản xuất');
                }
            }

            $plans = $this->candidateQuery($room, $mode)->whereIn('sp.id', $stagePlanIds)->lockForUpdate()
                ->get(['sp.id', 'sp.stage_code', 'sp.resourceId']);
            if ($plans->count() !== count($stagePlanIds)) {
                throw new ProductionExecutionException('❌ Lô / lịch không hợp lệ, đã hoàn thành hoặc đã được nhận phòng', 409);
            }
            if ($overlap) {
                $btp = DB::table('stage_plan as sp')
                    ->join('finished_product_category as fpc', 'sp.product_caterogy_id', '=', 'fpc.id')
                    ->whereIn('sp.id', array_merge($stagePlanIds, $held->pluck('id')->all()))
                    ->distinct()
                    ->count('fpc.intermediate_code');
                if ($btp > 1) {
                    throw new ProductionExecutionException('❌ Gói đầu: lô nhận thêm phải cùng mã bán thành phẩm (BTP) với lô đang giữ phòng ('
                        . $held->pluck('intermediate_code')->filter()->unique()->implode(', ') . ')');
                }
            }
            $maintenance = $plans->where('stage_code', self::MAINTENANCE_STAGE)->count();
            if ($maintenance && $maintenance !== $plans->count()) {
                throw new ProductionExecutionException('❌ Không nhận chung lịch bảo trì với lô sản xuất');
            }
            if (count($stagePlanIds) > 1 && !$maintenance && !$split) {
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
            $assigned = $plans->whereNull('resourceId')->pluck('id')->all();
            foreach ($labels as $id => $label) {
                AuditTrialController::log('Nhận phòng', 'stage_plan', $id, 'NA',
                    'Phòng ' . $room->code . ' · actual_start ' . $now->format('Y-m-d H:i:s') . ($overlap ? ' · gói đầu' : '') . ($split ? ' · chia thị trường' : '') . ' · ' . $label
                    . (in_array($id, $assigned) ? self::ASSIGNED_MARK : ''));
            }

            // Lịch bảo trì (28/11/2026): tịnh tuyến 1 lần theo dòng có giờ dự kiến trả phòng muộn nhất (cùng giờ nhận phòng)
            $maintenanceSource = $maintenance
                ? DB::table('stage_plan')->whereIn('id', $stagePlanIds)->get()
                    ->sortByDesc(fn($p) => ScheduleRerouteService::maintenanceProjectedFinish($p))->take(1)->pluck('id')->all()
                : [];

            return [
                '✅ Đã nhận phòng ' . $room->code . ($overlap ? ' (gói đầu)' : '') . ($split ? ' (thêm lô chia thị trường)' : '') . ' lúc ' . $now->format('H:i') . ' cho ' . $labels->implode(', '),
                $room->deparment_code,
                $maintenance ? $maintenanceSource : $plans->pluck('id')->all(),
                $maintenance,
            ];
        });

        // Lô sản xuất bắt đầu trễ từ 30 phút: đẩy lịch các lô sau theo giờ kết thúc dự kiến; lịch bảo trì: đẩy lô đè lên
        // khoảng Nhận phòng → dự kiến trả phòng của bảo trì ra sau (công tắc tịnh tuyến phải bật)
        $reroute = $this->rerouteAfter($productionIds, $deparmentCode,
            $receivedMaintenance ? 'rerouteOnMaintenanceReceive' : 'rerouteOnReceive', self::REROUTE_RECEIVE_AUDIT);

        return ['message' => $message, 'reroute' => $reroute && ($reroute['count'] || $reroute['error']) ? $reroute : null];
    }

    /**
     * Đổi sản phẩm (20/11/2026, chỉ Leader trở lên): phòng đang Bận với lô sản xuất nhận nhầm → bỏ actual_start của lô
     * đang giữ (bỏ gán phòng nếu lúc Nhận phòng lô được gán vào phòng này) và ghi actual_start cho lô mới bằng ĐÚNG giờ
     * nhận phòng cũ (thời gian giữ phòng không đổi). Không tịnh tuyến lịch, không hoàn tác được (đổi lại lần nữa).
     */
    public function changeProduct(int $roomId, array $stagePlanIds): string
    {
        $stagePlanIds = array_values(array_unique(array_map('intval', array_filter($stagePlanIds))));
        if (!$stagePlanIds) {
            throw new ProductionExecutionException('❌ Chọn lô sản phẩm mới');
        }

        return DB::transaction(function () use ($roomId, $stagePlanIds) {
            [$room, , $old] = $this->lockState($roomId, [self::BUSY]);

            if ($old->contains(fn($p) => $p->maintenance)) {
                throw new ProductionExecutionException('❌ Phòng đang giữ lịch bảo trì, không đổi sản phẩm được');
            }
            if ($old->count() > 1 && !in_array((int) $room->stage_code, self::GROUP_STAGES, true)) {
                throw new ProductionExecutionException('❌ Phòng đang giữ ' . $old->count() . ' lô (gói đầu / chia thị trường), không đổi sản phẩm được: Hoàn tác hoặc Trả phòng rồi nhận lại');
            }
            $oldRows = DB::table('stage_plan')->whereIn('id', $old->pluck('id'))->lockForUpdate()
                ->get(['id', 'actual_start', 'actual_end', 'finished']);
            if ($oldRows->contains(fn($p) => (int) $p->finished === 1 || $p->actual_end)) {
                throw new ProductionExecutionException('❌ Lô đang giữ phòng đã được xác nhận ở trang Xác nhận hoàn thành, không đổi sản phẩm được', 409);
            }
            $since = $oldRows->min('actual_start');

            $new = $this->candidateQuery($room, self::RECEIVE_PRODUCTION)->whereIn('sp.id', $stagePlanIds)->lockForUpdate()
                ->get(['sp.id', 'sp.resourceId']);
            if ($new->count() !== count($stagePlanIds)) {
                throw new ProductionExecutionException('❌ Lô không hợp lệ, đã hoàn thành hoặc đã được nhận phòng', 409);
            }
            if (count($stagePlanIds) > 1) {
                if (!in_array((int) $room->stage_code, self::GROUP_STAGES, true)) {
                    throw new ProductionExecutionException('❌ Chỉ phòng cân nguyên liệu được giữ nhiều lô');
                }
                $btp = DB::table('stage_plan as sp')
                    ->join('finished_product_category as fpc', 'sp.product_caterogy_id', '=', 'fpc.id')
                    ->whereIn('sp.id', $stagePlanIds)->distinct()->count('fpc.intermediate_code');
                if ($btp > 1) {
                    throw new ProductionExecutionException('❌ Chỉ giữ chung các lô cùng mã bán thành phẩm (BTP)');
                }
            }

            // Lô cũ được gán vào phòng lúc Nhận phòng (trước đó chưa có phòng) thì bỏ gán
            $assigned = DB::table('audittriallog')->where('table_Audit', 'stage_plan')->whereIn('action', ['Nhận phòng', 'Đổi sản phẩm'])
                ->whereIn('record_Id_AuditTrial', $old->pluck('id'))
                ->where('new_values', 'like', '%' . self::ASSIGNED_MARK . '%')
                ->pluck('record_Id_AuditTrial')->map(fn($x) => (int) $x)->all();

            $newLabels = $this->planDetails($stagePlanIds)->pluck('label', 'id');
            foreach ($old as $p) {
                DB::table('stage_plan')->where('id', $p->id)->update(['actual_start' => null]
                    + (in_array((int) $p->id, $assigned, true) ? ['resourceId' => null] : []));
                AuditTrialController::log('Đổi sản phẩm', 'stage_plan', $p->id, 'Phòng ' . $room->code . ' · ' . $p->label,
                    'actual_start = NULL · đổi sang ' . $newLabels->implode(', '));
            }
            foreach ($new as $p) {
                DB::table('stage_plan')->where('id', $p->id)->update(array_filter([
                    'actual_start' => $since,
                    'resourceId'   => $p->resourceId ? null : $room->id,
                ]));
                AuditTrialController::log('Đổi sản phẩm', 'stage_plan', $p->id, $old->pluck('label')->implode(', '),
                    'Phòng ' . $room->code . ' · actual_start ' . Carbon::parse($since)->format('Y-m-d H:i:s') . ' · ' . ($newLabels[$p->id] ?? '')
                    . ($p->resourceId ? '' : self::ASSIGNED_MARK));
            }

            return '✅ Đã đổi sản phẩm phòng ' . $room->code . ' sang ' . $newLabels->implode(', ')
                . ' (giữ giờ nhận phòng ' . Carbon::parse($since)->format('H:i d/m') . ')';
        });
    }

    /**
     * Trả phòng = giờ hệ thống cho mọi dòng đang giữ phòng.
     * - Phòng Bận: lô sản xuất ghi actual_end_clearning; lịch bảo trì ghi actual_end (lịch có vệ sinh → Chờ VS Sau BT).
     * - Đang VS Sau BT: ghi actual_end_clearning cho các lịch bảo trì → Sẵn Sàng.
     * Lịch BT / TI / HC không có trang Xác nhận hoàn thành: lần trả phòng cuối (HC: trả phòng; BT / TI: trả phòng vệ sinh
     * sau BT) coi như xác nhận hoàn thành tạm thời như nút ✅ ở Lịch HC-BT (finished = 1, finished_date, finished_by).
     * Sau khi lưu, nếu công tắc tịnh tuyến của phân xưởng đang bật thì dịch lịch lý thuyết theo giờ trả phòng của lô sản xuất
     * (ScheduleRerouteService::rerouteOnRelease). Lỗi tịnh tuyến không làm hỏng việc trả phòng đã lưu.
     *
     * @return array{message: string, reroute: ?array}
     */
    public function release(int $roomId, bool $cleanAfterHc = false, ?int $stagePlanId = null): array
    {
        return $this->withRerouteLock($roomId, fn() => $this->releaseLocked($roomId, $cleanAfterHc, $stagePlanId));
    }

    /** Phòng Bận chỉ giữ lịch HC không có vệ sinh: Trả phòng được chọn có vệ sinh sau HC hay không */
    private static function hcOnly(int $state, Collection $plans): bool
    {
        return $state === self::BUSY && $plans->isNotEmpty()
            && $plans->every(fn($p) => $p->maintenance && $p->maintenance_type === 'HC' && !$p->title_clearning);
    }

    /**
     * $cleanAfterHc (chỉ khi phòng chỉ giữ lịch HC): người trả phòng chọn có vệ sinh sau HC → ghi title_clearning = VS-II
     * (start_clearning để NULL, dùng nhận ra khi hoàn tác), chưa xác nhận hoàn thành → Chờ VS Sau BT như lịch BT.
     */
    private function releaseLocked(int $roomId, bool $cleanAfterHc = false, ?int $stagePlanId = null): array
    {
        [$message, $deparmentCode, $reroutePlan] = DB::transaction(function () use ($roomId, $cleanAfterHc, $stagePlanId) {
            [$room, $state, $plans] = $this->lockState($roomId, [self::BUSY, self::CLEANING]);
            $cleanAfterHc = $cleanAfterHc && self::hcOnly($state, $plans);
            // Phòng đôi gói đầu giữ nhiều lô sản xuất: trả phòng từng lô ($stagePlanId).
            // Lô chia thị trường (Đóng Gói, cùng main_parkaging_id) luôn trả phòng chung 1 lần: cùng actual_end_clearning.
            $splitLots = $plans->count() > 1 && self::splitGroup($room, $state, $plans);
            if ($state === self::BUSY && !$splitLots && (int) ($room->max_parallel ?? 1) > 1
                && !in_array((int) $room->stage_code, self::GROUP_STAGES, true) && $plans->count() > 1) {
                if (!$stagePlanId || !$plans->contains('id', $stagePlanId)) {
                    throw new ProductionExecutionException('⚠️ Phòng đang giữ ' . $plans->count() . ' lô gói đầu: chọn lô cần trả phòng. Đã tải lại thông tin phòng.', 409);
                }
                $plans = $plans->where('id', $stagePlanId)->values();
            }

            $now = now();
            foreach ($plans as $p) {
                $column = $state === self::CLEANING || !$p->maintenance ? 'actual_end_clearning' : 'actual_end';
                $finish = !$cleanAfterHc && $p->maintenance && ($state === self::CLEANING || !$p->title_clearning);
                DB::table('stage_plan')->where('id', $p->id)->update([$column => $now]
                    + ($cleanAfterHc ? ['title_clearning' => 'VS-II'] : [])
                    + ($finish ? [
                    'finished'      => 1,
                    'finished_date' => $now,
                    'finished_by'   => session('user')['fullName'] ?? null,
                ] : []));
                AuditTrialController::log($state === self::CLEANING ? 'Trả phòng vệ sinh sau BT' : 'Trả phòng', 'stage_plan', $p->id, 'NA',
                    'Phòng ' . $room->code . ' · ' . $column . ' ' . $now->format('Y-m-d H:i:s') . ($finish ? ' · xác nhận hoàn thành' : '')
                    . ($cleanAfterHc ? ' · có vệ sinh sau HC' : '') . ' · ' . $p->label);
            }

            $after = $this->stateOf($this->planDetails(
                $this->openPlans([$room->id], $this->releasedAt([$room->id]))->get($room->id, collect())->pluck('id')->all()
            )->values());

            // Tịnh tuyến: Trả phòng lô sản xuất; Trả phòng lần cuối của lịch bảo trì (VS sau BT, hoặc HC không vệ sinh) thì
            // lấy 1 dòng bảo trì (giờ kết thúc lý thuyết muộn nhất) làm lô gốc. Trả phòng phần bảo trì còn chờ vệ sinh không
            // tịnh tuyến (lô sau đã được đẩy lúc Nhận phòng bảo trì / quét quá giờ, 28/11/2026).
            $maintenanceFinal = $plans->isNotEmpty() && $plans->every(fn($p) => $p->maintenance)
                && ($state === self::CLEANING || (!$cleanAfterHc && $plans->every(fn($p) => !$p->title_clearning)));
            $rerouteIds = $maintenanceFinal
                ? $plans->sortByDesc(fn($p) => $p->end_clearning ?? $p->end)->take(1)->pluck('id')->all()
                : $plans->where('maintenance', false)->pluck('id')->all();

            return [
                '✅ Đã trả phòng ' . $room->code . ' lúc ' . $now->format('H:i')
                    . ($plans->count() === 1 && !$plans->first()->maintenance ? ' cho ' . $plans->first()->label : '')
                    . ($splitLots ? ' cho ' . $plans->count() . ' lô chia thị trường' : '')
                    . ($after === self::CLEAN_WAIT ? ' · phòng chuyển sang ' . self::STATE_LABELS[self::CLEAN_WAIT] : '')
                    . ($after === self::BUSY ? ' · phòng vẫn Bận với lô gói đầu' : ''),
                $room->deparment_code,
                [$rerouteIds, $maintenanceFinal ? 'rerouteOnMaintenanceRelease' : 'rerouteOnRelease'],
            ];
        });

        [$rerouteIds, $method] = $reroutePlan;

        return ['message' => $message, 'reroute' => $this->rerouteAfter($rerouteIds, $deparmentCode, $method, self::REROUTE_AUDIT)];
    }

    /**
     * Tịnh tuyến lịch cho các lô vừa Nhận phòng (rerouteOnReceive) / Trả phòng (rerouteOnRelease), công tắc của phân xưởng
     * phải đang bật. null = không chạy (tắt công tắc / không có lô sản xuất); gộp kết quả nếu nhiều lô.
     * Mã lần chạy ghi audittriallog ($auditAction) để Hoàn tác thao tác khôi phục luôn lịch đã tịnh tuyến.
     */
    private function rerouteAfter(array $stagePlanIds, string $deparmentCode, string $method, string $auditAction): ?array
    {
        if (!$stagePlanIds || !RealtimeRerouteSwitch::enabled($deparmentCode)) {
            return null;
        }

        $result = ['trigger' => in_array($method, ['rerouteOnReceive', 'rerouteOnMaintenanceReceive'], true) ? 'receive' : 'release',
            'count' => 0, 'delta' => 0, 'cleaning_moved' => false, 'error' => false,
            'maintenance' => in_array($method, ['rerouteOnMaintenanceRelease', 'rerouteOnMaintenanceReceive'], true)];
        foreach ($stagePlanIds as $id) {
            try {
                $r = $method === 'rerouteOnRelease'
                    // Lô đã đẩy lịch lúc Nhận phòng: Trả phòng so với giờ kết thúc dự kiến của lần đó
                    ? app(ScheduleRerouteService::class)->rerouteOnRelease((int) $id, $this->lastRerouteRun((int) $id, self::REROUTE_RECEIVE_AUDIT))
                    : app(ScheduleRerouteService::class)->{$method}((int) $id);
                if ($r['run_code']) {
                    AuditTrialController::log($auditAction, 'stage_plan', $id, 'NA', $r['run_code']);
                }
                $result['count'] += count($r['changes']);
                $result['delta'] = $result['delta'] ?: $r['delta_minutes'];
                $result['cleaning_moved'] = $result['cleaning_moved'] || $r['source_cleaning_moved'];
            } catch (\Throwable $e) {
                $result['error'] = true;
                Log::error('[Reroute] ' . $auditAction . ' thất bại cho stage_plan ' . $id, [
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
     * Hoàn tác thao tác mới nhất của phòng (Nhận phòng / Nhận phòng vệ sinh sau BT / Trả phòng), trong UNDO_SECONDS,
     * chỉ người đã bấm. Nhận phòng / Trả phòng: lịch đã tịnh tuyến cũng được khôi phục (lô nào đã bị đổi lịch lần nữa thì giữ nguyên).
     * Không hoàn tác được khi lô đã đi tiếp ở trang Xác nhận hoàn thành (✓ sau Nhận phòng, ✓✓ sau Trả phòng).
     */
    public function undo(int $roomId): string
    {
        return $this->withRerouteLock($roomId, fn() => $this->undoLocked($roomId));
    }

    /**
     * Nhận phòng / Trả phòng / Hoàn tác có thể tịnh tuyến lịch: giữ khóa tịnh tuyến của phân xưởng (RerouteLock) từ lúc lưu
     * tới khi tịnh tuyến xong, phòng khác bấm cùng lúc thì chờ. Chờ quá RerouteLock::WAIT_SECONDS: thao tác không được lưu,
     * người dùng phải bấm lại (user chọn, 06/10/2026).
     */
    private function withRerouteLock(int $roomId, callable $fn)
    {
        try {
            return RerouteLock::run(DB::table('room')->where('id', $roomId)->value('deparment_code'), $fn);
        } catch (RerouteBusyException $e) {
            throw new ProductionExecutionException($e->getMessage(), 423);
        }
    }

    private function undoLocked(int $roomId): string
    {
        return DB::transaction(function () use ($roomId) {
            [$room, $state] = $this->lockState($roomId, [self::READY, self::BUSY, self::CLEAN_WAIT, self::CLEANING]);

            $undo = $this->pendingUndos([$room->id], [$room->id => $state])[$room->id] ?? null;
            if (!$undo) {
                throw new ProductionExecutionException('⚠️ Không còn hoàn tác được: chỉ hoàn tác thao tác mới nhất của phòng, do chính bạn bấm, trong '
                    . (self::UNDO_SECONDS / 60) . ' phút. Đã tải lại thông tin phòng.', 409);
            }

            $plans = DB::table('stage_plan')->whereIn('id', $undo->plan_ids)->get(['id', 'stage_code', 'finished', 'actual_end', 'actual_start_clearning', 'title', 'title_clearning', 'start_clearning']);
            $labels = $this->planDetails($undo->plan_ids)->pluck('label', 'id');
            $note = '';

            foreach ($plans as $p) {
                $name = $labels[$p->id] ?? $p->title;
                $maint = (int) $p->stage_code === self::MAINTENANCE_STAGE;

                switch ($undo->action) {
                    case 'Nhận phòng':
                        if ((int) $p->finished === 1 || $p->actual_end) {
                            throw new ProductionExecutionException("❌ Lô \"$name\" đã được xác nhận ở trang Xác nhận hoàn thành, không hoàn tác Nhận phòng được", 409);
                        }
                        $update = ['actual_start' => null] + (in_array($p->id, $undo->assigned) ? ['resourceId' => null] : []);
                        $note .= $this->undoReroute((int) $p->id, self::REROUTE_RECEIVE_AUDIT);
                        break;
                    case 'Nhận phòng vệ sinh sau BT':
                        $update = ['actual_start_clearning' => null];
                        break;
                    case 'Trả phòng':
                        if ($p->actual_start_clearning) {
                            throw new ProductionExecutionException("❌ Lô \"$name\" đã xác nhận vệ sinh (✓✓), không hoàn tác Trả phòng được", 409);
                        }
                        $update = [$maint ? 'actual_end' : 'actual_end_clearning' => null]
                            + ($maint && !$p->title_clearning ? self::UNFINISH : [])
                            // HC trả phòng có chọn vệ sinh sau HC: VS-II ghi lúc trả phòng (lịch BT luôn có start_clearning) → gỡ
                            + ($maint && $p->title_clearning && !$p->start_clearning ? ['title_clearning' => null] : []);
                        $note .= $this->undoReroute((int) $p->id, self::REROUTE_AUDIT);
                        break;
                    default: // Trả phòng vệ sinh sau BT
                        $update = ['actual_end_clearning' => null] + self::UNFINISH;
                        $note .= $this->undoReroute((int) $p->id, self::REROUTE_AUDIT);
                }

                DB::table('stage_plan')->where('id', $p->id)->update($update);
                AuditTrialController::log(self::UNDO_PREFIX . $undo->action, 'stage_plan', $p->id, 'NA',
                    'Phòng ' . $room->code . ' · ' . implode(', ', array_keys($update)) . ' = NULL · ' . $name);
            }

            return '✅ Đã hoàn tác ' . mb_strtolower($undo->action) . ' ' . $room->code . $note;
        });
    }

    /** Hoàn tác lần trả phòng cuối của lịch bảo trì: bỏ xác nhận hoàn thành tạm thời đi kèm */
    private const UNFINISH = ['finished' => 0, 'finished_date' => null, 'finished_by' => null];

    /** Mã lần tịnh tuyến gần nhất của 1 lô theo thao tác ($auditAction), lưu ở audittriallog.new_values */
    private function lastRerouteRun(int $stagePlanId, string $auditAction): ?string
    {
        return DB::table('audittriallog')
            ->where('table_Audit', 'stage_plan')
            ->where('action', $auditAction)
            ->where('record_Id_AuditTrial', $stagePlanId)
            ->orderByDesc('id')
            ->value('new_values');
    }

    /**
     * Khôi phục lịch đã tịnh tuyến lúc Nhận phòng / Trả phòng ($auditAction) của 1 lô (mã lần chạy lưu ở audittriallog);
     * trả đoạn ghi chú cho thông báo
     */
    private function undoReroute(int $stagePlanId, string $auditAction): string
    {
        $code = DB::table('audittriallog')
            ->where('table_Audit', 'stage_plan')
            ->where('action', $auditAction)
            ->where('record_Id_AuditTrial', $stagePlanId)
            ->where('created_at', '>=', now()->subSeconds(self::UNDO_SECONDS + 10))
            ->orderByDesc('id')
            ->value('new_values');
        if (!$code) {
            return '';
        }

        try {
            $r = app(ScheduleRerouteService::class)->undo((string) $code);

            return " · đã khôi phục lịch tịnh tuyến ({$r['restored']} lô" . ($r['skipped'] ? ", bỏ qua {$r['skipped']} lô đã đổi lịch" : '') . ')';
        } catch (\Throwable $e) {
            Log::error('[Reroute] Hoàn tác "' . $auditAction . '" thất bại cho stage_plan ' . $stagePlanId, ['error' => $e->getMessage()]);

            return ' · không khôi phục được lịch tịnh tuyến (đã ghi log)';
        }
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
