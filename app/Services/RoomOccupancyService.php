<?php

namespace App\Services;

use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Trang "Thực Thi Sản Xuất" (bản tinh gọn 10/2026): phòng chỉ có 2 trạng thái, ghi thẳng vào stage_plan.
 *
 *   Sẵn Sàng --(Nhận phòng: chọn lô / lịch bảo trì, ghi actual_start)--> Phòng Bận
 *   Phòng Bận --(Trả phòng: ghi actual_end_clearning; lịch bảo trì ghi actual_end)--> Sẵn Sàng
 *
 * - Phòng Bận = có lô của phòng đã có actual_start mà chưa có mốc trả phòng, và actual_start sau lần trả phòng gần
 *   nhất của phòng (lô cũ chưa từng xác nhận ✓✓ không giữ phòng mãi), chỉ tính lô nhận phòng từ TRACK_FROM.
 * - Lịch bảo trì (stage_code 8) không có vệ sinh: mốc trả phòng là actual_end.
 * - Không dùng room_execution_log / room_execution_batch (bảng của bản cũ, Báo cáo ngày vẫn đọc dữ liệu cũ ở đó).
 * - Sản lượng, KT sản xuất, vệ sinh vẫn xác nhận ở trang Xác nhận hoàn thành.
 */
class RoomOccupancyService
{
    const READY = 1;
    const BUSY  = 2;

    const STATE_LABELS = [
        self::READY => 'Sẵn Sàng',
        self::BUSY  => 'Phòng Bận',
    ];

    // [hậu tố class CSS (dùng lại màu của bản cũ trong _styles), icon]
    const STATE_META = [
        self::READY => ['clean', 'fa-check-circle'],
        self::BUSY  => ['producing', 'fa-cog'],
    ];

    const DISPLAY_ORDER = [self::BUSY, self::READY];

    const MAINTENANCE_STAGE = 8;

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

    // Mốc trả phòng của 1 dòng stage_plan
    private const RELEASE_SQL = 'CASE WHEN stage_code = ' . self::MAINTENANCE_STAGE . ' THEN actual_end ELSE actual_end_clearning END';

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
            $busy = $plans->isNotEmpty();

            $room->st = (object) [
                'state' => $busy ? self::BUSY : self::READY,
                'label' => self::STATE_LABELS[$busy ? self::BUSY : self::READY],
                'since' => $busy ? Carbon::parse($plans->min('actual_start')) : null,
                'plans' => $plans,
            ];
            $room->stage_group = in_array((int) $room->stage_code, self::GROUP_STAGES, true) ? 1 : (int) $room->stage_code;
            $room->staff = $staff->get($room->id, collect());
        }
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
                'sp.Theoretical_yields',
                'pn.name as product_name',
                DB::raw('COALESCE(pm.actual_batch, pm.batch) AS batch'),
                'pm.is_val',
                'fpc.intermediate_code',
                'fpc.finished_product_code',
                'mk.code as market'
            )
            ->get();

        foreach ($rows as $row) {
            $row->maintenance = (int) $row->stage_code === self::MAINTENANCE_STAGE;
            if ($row->maintenance) {
                // Tiêu đề lịch bảo trì có <br/> cho Gantt: "WHC-004 _ BUỒNG CÂN :<br/> - WHC-004<br/> Ngày tới hạn: ..."
                $row->title = collect(preg_split('/<br\s*\/?>/i', (string) $row->title))
                    ->map(fn($s) => trim(strip_tags($s), " \t\n\r-:"))
                    ->filter()
                    ->implode(' · ');
            }
            $row->unit = $row->stage_code <= 4 ? 'Kg' : 'ĐVL';
            $row->label = $row->maintenance || !$row->batch
                ? $row->title
                : ($row->product_name ?? $row->title) . ' - ' . $row->batch;
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
     */
    private function candidateQuery(object $room)
    {
        $weighing = in_array((int) $room->stage_code, self::GROUP_STAGES, true);
        $stageCodes = array_merge($weighing ? self::GROUP_STAGES : [(int) $room->stage_code], [self::MAINTENANCE_STAGE]);

        return DB::table('stage_plan as sp')
            ->where('sp.active', 1)
            ->where('sp.deparment_code', $room->deparment_code)
            ->whereIn('sp.stage_code', $stageCodes)
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

    public function candidatePlans(object $room): Collection
    {
        $ids = $this->candidateQuery($room)
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
     * Nhận phòng: ghi actual_start = giờ hệ thống cho lô đã chọn (phòng cân: nhiều lô cùng mã BTP).
     * Lô nhận ở phòng cân mà chưa gán phòng thì gán vào phòng này.
     */
    public function receive(int $roomId, array $stagePlanIds): string
    {
        $stagePlanIds = array_values(array_unique(array_map('intval', array_filter($stagePlanIds))));
        if (!$stagePlanIds) {
            throw new ProductionExecutionException('❌ Chọn lô cần nhận phòng');
        }

        return DB::transaction(function () use ($roomId, $stagePlanIds) {
            $room = $this->lockReady($roomId, self::READY);

            $plans = $this->candidateQuery($room)->whereIn('sp.id', $stagePlanIds)->lockForUpdate()->get(['sp.id', 'sp.stage_code', 'sp.resourceId']);
            if ($plans->count() !== count($stagePlanIds)) {
                throw new ProductionExecutionException('❌ Lô không hợp lệ, đã hoàn thành hoặc đã được nhận phòng', 409);
            }
            if (count($stagePlanIds) > 1) {
                if (!in_array((int) $room->stage_code, self::GROUP_STAGES, true) || $plans->contains('stage_code', self::MAINTENANCE_STAGE)) {
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
     * Trả phòng: ghi mốc trả phòng = giờ hệ thống cho mọi lô đang giữ phòng
     * (actual_end_clearning; lịch bảo trì ghi actual_end).
     */
    public function release(int $roomId): string
    {
        return DB::transaction(function () use ($roomId) {
            $room = $this->lockReady($roomId, self::BUSY);
            $plans = $this->openPlans([$room->id], $this->releasedAt([$room->id]))->get($room->id, collect());

            $now = now();
            foreach ($this->planDetails($plans->pluck('id')->all()) as $p) {
                $column = $p->maintenance ? 'actual_end' : 'actual_end_clearning';
                DB::table('stage_plan')->where('id', $p->id)->update([$column => $now]);
                AuditTrialController::log('Trả phòng', 'stage_plan', $p->id, 'NA',
                    'Phòng ' . $room->code . ' · ' . $column . ' ' . $now->format('Y-m-d H:i:s') . ' · ' . $p->label);
            }

            return '✅ Đã trả phòng ' . $room->code . ' lúc ' . $now->format('H:i');
        });
    }

    /**
     * Khóa dòng phòng (2 người bấm cùng lúc thì người sau chờ) và kiểm tra phòng đang đúng trạng thái mong đợi.
     */
    private function lockReady(int $roomId, int $expected): object
    {
        $room = DB::table('room')->where('id', $roomId)->where('active', 1)->lockForUpdate()->first();
        if (!$room) {
            throw new ProductionExecutionException('❌ Không tìm thấy phòng sản xuất', 404);
        }

        $busy = $this->openPlans([$room->id], $this->releasedAt([$room->id]))->isNotEmpty();
        if ($busy !== ($expected === self::BUSY)) {
            throw new ProductionExecutionException('⚠️ Phòng đang ' . self::STATE_LABELS[$busy ? self::BUSY : self::READY]
                . ' (có thể do người khác vừa thao tác). Đã tải lại thông tin phòng.', 409);
        }

        return $room;
    }
}
