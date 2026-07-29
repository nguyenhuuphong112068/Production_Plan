<?php

namespace App\Http\Controllers\Pages\Schedual;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SchedualAuditController extends Controller
{
    /**
     * Lý do chuẩn hoá (rỗng/null -> nhãn mặc định), dùng chung cho query thống kê & chi tiết.
     */
    private const REASON_EXPR = "COALESCE(NULLIF(TRIM(h.type_of_change), ''), 'Không ghi nhận lý do')";

    /** Nhóm người dùng được xem cột "Lý do". */
    private const REASON_USER_GROUPS = ['Admin', 'Schedualer'];

    /** Phòng ban được xem cột "Lý do". */
    private const REASON_DEPARTMENTS = ['COMP'];

    /**
     * Chỉ Admin / Schedualer hoặc user thuộc phòng COMP mới được xem lý do thay đổi.
     */
    private function canViewReason(): bool
    {
        $user = session('user') ?? [];

        return in_array($user['userGroup'] ?? '', self::REASON_USER_GROUPS, true)
            || in_array($user['department'] ?? '', self::REASON_DEPARTMENTS, true);
    }

    /**
     * THỐNG KÊ LỊCH SỬ THAY ĐỔI THEO NGÀY.
     *
     * Quy tắc đếm: các bản ghi stage_plan_history có CÙNG NGÀY THAY ĐỔI và CÙNG LÝ DO
     * được tính là 1 lần thay đổi.
     */
    public function index(Request $request)
    {
        $production_code = session('user')['production_code'];

        // 0. KHOẢNG THỜI GIAN LỌC (mặc định: từ đầu tháng hiện tại đến hôm nay)
        $from = $request->input('from_date') ?: Carbon::now()->startOfMonth()->toDateString();
        $to   = $request->input('to_date') ?: Carbon::now()->toDateString();

        // Nếu nhập ngược thì tự đảo lại cho đúng
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $fromAt = Carbon::parse($from)->startOfDay();
        $toAt   = Carbon::parse($to)->endOfDay();

        // 1. GOM NHÓM THEO (NGÀY THAY ĐỔI + LÝ DO) => mỗi nhóm là 1 lần thay đổi
        $groups = DB::table('stage_plan_history as h')
            ->where('h.deparment_code', $production_code)
            ->whereNotNull('h.created_date')
            ->whereBetween('h.created_date', [$fromAt, $toAt])
            ->selectRaw('DATE(h.created_date) as change_date')
            ->selectRaw(self::REASON_EXPR . ' as reason')
            ->selectRaw('COUNT(DISTINCT h.stage_plan_id) as plan_count')
            ->selectRaw('COUNT(*) as history_count')
            ->selectRaw("GROUP_CONCAT(DISTINCT h.created_by ORDER BY h.created_by SEPARATOR '|') as changed_by")
            ->selectRaw('MIN(h.created_date) as first_at')
            ->selectRaw('MAX(h.created_date) as last_at')
            // Group theo alias để tương thích chế độ ONLY_FULL_GROUP_BY của MySQL
            ->groupByRaw('change_date, reason')
            ->get();

        // 2. GOM CÁC NHÓM LÝ DO LẠI THEO NGÀY => 1 dòng / 1 ngày
        $datas = $groups
            ->groupBy('change_date')
            ->map(function ($group, $date) {
                $users = $group
                    ->pluck('changed_by')
                    ->flatMap(fn($u) => explode('|', (string) $u))
                    ->map(fn($u) => trim($u))
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values();

                return (object) [
                    'change_date'  => $date,
                    // Số lần thay đổi = số nhóm lý do khác nhau trong ngày
                    'change_count' => $group->count(),
                    'plan_count'   => $group->sum('plan_count'),
                    'reasons'      => $group->sortByDesc('plan_count')->values(),
                    'changed_by'   => $users,
                    'last_at'      => $group->max('last_at'),
                ];
            })
            ->sortByDesc('change_date')
            ->values();

        // 3. TỔNG HỢP TOÀN KHOẢNG THỜI GIAN
        $summary = (object) [
            'from'          => $from,
            'to'            => $to,
            // Tổng số lần thay đổi = tổng số nhóm (ngày + lý do) trong khoảng
            'change_count'  => $groups->count(),
            'day_count'     => $datas->count(),
            'history_count' => $groups->sum('history_count'),
            'plan_count'    => DB::table('stage_plan_history as h')
                ->where('h.deparment_code', $production_code)
                ->whereBetween('h.created_date', [$fromAt, $toAt])
                ->distinct()
                ->count('h.stage_plan_id'),
        ];

        session()->put(['title' => 'LỊCH SỬ THAY ĐỔI LỊCH SẢN XUẤT']);

        return view('pages.Schedual.audit.plan_list', [
            'datas' => $datas,
            'summary' => $summary,
            'canViewReason' => $this->canViewReason(),
        ]);
    }

    /**
     * CHI TIẾT CÁC STAGE_PLAN ĐÃ THAY ĐỔI TRONG 1 NGÀY (tuỳ chọn: lọc theo 1 lý do).
     */
    public function daily(Request $request)
    {
        $production_code = session('user')['production_code'];
        $date = $request->input('date');
        $canViewReason = $this->canViewReason();

        if (! $date) {
            return response()->json([]);
        }

        $datas = DB::table('stage_plan_history as h')
            ->select(
                'h.stage_plan_id',
                'h.version',
                'h.title',
                'h.stage_code',
                'h.start',
                'h.end',
                'h.start_clearning',
                'h.end_clearning',
                // Không có quyền xem lý do => không trả dữ liệu lý do về client
                $canViewReason ? 'h.type_of_change' : DB::raw('NULL as type_of_change'),
                'h.created_by',
                'h.created_date',
                'room.name as room_name',
                'room.code as room_code',
                'room.stage as stage',
                'prev.start as prev_start',
                'prev.end as prev_end',
                'prev_room.name as prev_room_name',
                'prev_room.code as prev_room_code',
                DB::raw('COALESCE(plan_master.actual_batch, plan_master.batch) AS batch'),
                'finished_product_category.intermediate_code',
                'finished_product_category.finished_product_code',
                'finished_product_category.batch_qty',
                'finished_product_category.unit_batch_qty',
                'product_name.name as product_name'
            )
            ->leftJoin('stage_plan as sp', 'h.stage_plan_id', '=', 'sp.id')
            ->leftJoin('room', 'h.resourceId', '=', 'room.id')
            // Phiên bản liền trước để so sánh "trước - sau"
            ->leftJoin('stage_plan_history as prev', function ($join) {
                $join->on('prev.stage_plan_id', '=', 'h.stage_plan_id')
                    ->whereRaw('prev.version = h.version - 1');
            })
            ->leftJoin('room as prev_room', 'prev.resourceId', '=', 'prev_room.id')
            ->leftJoin('plan_master', 'sp.plan_master_id', '=', 'plan_master.id')
            ->leftJoin('finished_product_category', 'sp.product_caterogy_id', '=', 'finished_product_category.id')
            ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
            ->where('h.deparment_code', $production_code)
            ->whereDate('h.created_date', $date)
            ->when($canViewReason && $request->filled('reason'), function ($q) use ($request) {
                return $q->whereRaw(self::REASON_EXPR . ' = ?', [$request->input('reason')]);
            })
            ->orderBy('h.created_date')
            ->orderBy('h.stage_code')
            ->orderBy('h.start')
            ->get();

        return response()->json($datas);
    }


    public function open(Request $request)
    {
        $plan_list_id = $request->plan_list_id;
        $production = session('user')['production_code'];

        // 1. Lấy danh sách công đoạn trước
        $stages = DB::table('stage_plan_history')
            ->select('stage_plan_history.stage_code', 'room.stage')
            ->where('stage_plan_history.deparment_code', $production)
            ->whereNotNull('stage_plan_history.start')
            ->leftJoin('room', 'stage_plan_history.resourceId', 'room.id')
            ->distinct()
            ->orderby('stage_code')
            ->get();

        // 2. Xác định stageCode cần lọc (mặc định lấy cái đầu tiên nếu không truyền)
        $stageCode = $request->input('stage_code', optional($stages->first())->stage_code);

        // Subquery: lấy version lớn nhất cho mỗi stage_plan_id
        $maxVersionSub = DB::table('stage_plan_history')
            ->select('stage_plan_id', DB::raw('MAX(version) as max_version'))
            ->groupBy('stage_plan_id');

        // 3. Truy vấn dữ liệu có lọc theo stageCode
        $datas = DB::table('stage_plan_history as h')
            ->select(
                'h.*',
                'room.name as room_name',
                'room.code as room_code',
                'room.stage as stage',
                DB::raw("COALESCE(plan_master.actual_batch, plan_master.batch) AS batch"),
                'plan_master.expected_date',
                'plan_master.is_val',
                'finished_product_category.intermediate_code',
                'finished_product_category.finished_product_code',
                'finished_product_category.batch_qty',
                'finished_product_category.unit_batch_qty',
                'product_name.name as product_name',
                'market.name as name'
            )
            ->joinSub($maxVersionSub, 'mv', function ($join) {
                $join->on('h.stage_plan_id', '=', 'mv.stage_plan_id')
                    ->whereColumn('h.version', 'mv.max_version');
            })
            ->leftJoin('stage_plan as sp', 'h.stage_plan_id', '=', 'sp.id')
            ->leftJoin('room', 'h.resourceId', '=', 'room.id')
            ->leftJoin('plan_master', 'sp.plan_master_id', '=', 'plan_master.id')
            ->leftJoin('finished_product_category', 'sp.product_caterogy_id', '=', 'finished_product_category.id')
            ->leftJoin('intermediate_category', 'finished_product_category.intermediate_code', '=', 'intermediate_category.intermediate_code')
            ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
            ->leftJoin('market', 'finished_product_category.market_id', '=', 'market.id')
            ->whereIn('sp.plan_list_id', explode(',', $plan_list_id))
            ->where('sp.deparment_code', $production)
            ->when($stageCode, function ($q) use ($stageCode) {
                return $q->where('sp.stage_code', $stageCode);
            })
            ->orderBy('h.start', 'desc')
            ->get();

        session()->put(['title' => 'CHI TIẾT LỊCH SỬ THAY ĐỔI LỊCH SẢN XUẤT']);
        return view('pages.Schedual.audit.list', [
            'datas' => $datas,
            'stages' => $stages,
            'stageCode' => $stageCode,
            'plan_list_id' => $plan_list_id
        ]);
    }

    public function history(Request $request)
    {

        $datas = DB::table('stage_plan_history as h')
            ->select(
                'h.*',
                'room.name as room_name',
                'room.code as room_code',
                'room.stage as stage',
                DB::raw("COALESCE(plan_master.actual_batch, plan_master.batch) AS batch"),
                'plan_master.expected_date',
                'plan_master.is_val',
                'finished_product_category.intermediate_code',
                'finished_product_category.finished_product_code',
                'finished_product_category.batch_qty',
                'finished_product_category.unit_batch_qty',
                'market.name as market_name',
                'product_name.name as product_name'
            )
            ->leftJoin('stage_plan as sp', 'h.stage_plan_id', '=', 'sp.id')
            ->leftJoin('room', 'h.resourceId', '=', 'room.id')
            ->leftJoin('plan_master', 'sp.plan_master_id', '=', 'plan_master.id')
            ->leftJoin('finished_product_category', 'sp.product_caterogy_id', '=', 'finished_product_category.id')
            ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
            ->leftJoin('market', 'finished_product_category.market_id', '=', 'market.id')
            ->when($request->plan_list_id, function ($q) use ($request) {
                return $q->whereIn('sp.plan_list_id', explode(',', $request->plan_list_id));
            })
            ->when($request->id, function ($q) use ($request) {
                return $q->where('h.stage_plan_id', $request->id);
            })
            ->orderBy('h.version', 'desc')
            ->get();

        return response()->json($datas);
    }

    public function compare(Request $request)
    {
        session()->put(['title' => 'SO SÁNH LỊCH SỬ THAY ĐỔI LỊCH SẢN XUẤT']);
        return view('pages.Schedual.audit.compare');
    }

    public function compare_data(Request $request)
    {
        $targetDate = $request->input('target_date'); // Format: YYYY-MM-DD HH:mm:ss
        $production = session('user')['production_code'];

        // Bước 2: Truy vấn so sánh tất cả các lịch sử từ targetDate đến nay
        $changedPlans = DB::table('stage_plan as p')
            ->join('plan_master as pm', 'p.plan_master_id', '=', 'pm.id')
            ->join('plan_list as pl', 'pm.plan_list_id', '=', 'pl.id')
            ->join('finished_product_category as fpc', 'p.product_caterogy_id', '=', 'fpc.id')
            ->leftJoin('intermediate_category as ic', 'fpc.intermediate_code', '=', 'ic.intermediate_code')
            ->leftJoin('product_name as pn', 'ic.product_name_id', '=', 'pn.id')
            ->leftJoin('room as current_room', 'p.resourceId', '=', 'current_room.id')
            ->join('stage_plan_history as h', 'p.id', '=', 'h.stage_plan_id')
            ->leftJoin('room as old_room', 'h.resourceId', '=', 'old_room.id')
            ->where('p.deparment_code', $production)
            ->where('h.created_date', '>=', $targetDate)
            ->where('p.active', 1)
            ->where('p.stage_code', '<=', 7)
            ->where(function ($query) {
                $query->whereColumn('p.start', '!=', 'h.start')
                    ->orWhereColumn('p.end', '!=', 'h.end')
                    ->orWhereColumn('p.resourceId', '!=', 'h.resourceId');
            })
            ->select(
                'p.id as plan_id',
                DB::raw("
                    CASE
                            WHEN p.stage_code >=8 THEN p.title
                            ELSE CONCAT(
                            pn.name,
                            '-',
                            COALESCE(pm.actual_batch, pm.batch)
                            )
                    END AS plan_title
                "),
                'pn.name as product_name',
                'p.start as current_start',
                'h.start as old_start',
                'p.end as current_end',
                'h.end as old_end',
                'current_room.name as current_room_name',
                'old_room.name as old_room_name',
                'h.created_date as history_saved_at',
                'fpc.finished_product_code',
                DB::raw("COALESCE(pm.actual_batch, pm.batch) AS batch"),
                'p.finished',
                'p.stage_code',
                'p.schedualed_at as current_created_date',
                'h.version'
            )
            ->orderBy('h.created_date', 'desc')
            ->get();

        // Bước 3: Lấy các lịch tạo mới trong khoảng thời gian này (không có trong history)
        $newPlans = DB::table('stage_plan as p')
            ->join('plan_master as pm', 'p.plan_master_id', '=', 'pm.id')
            ->join('plan_list as pl', 'pm.plan_list_id', '=', 'pl.id')
            ->join('finished_product_category as fpc', 'p.product_caterogy_id', '=', 'fpc.id')
            ->leftJoin('intermediate_category as ic', 'fpc.intermediate_code', '=', 'ic.intermediate_code')
            ->leftJoin('product_name as pn', 'ic.product_name_id', '=', 'pn.id')
            ->leftJoin('room as current_room', 'p.resourceId', '=', 'current_room.id')
            ->leftJoin('stage_plan_history as h', 'p.id', '=', 'h.stage_plan_id')
            ->whereNull('h.stage_plan_id')
            ->where('p.deparment_code', $production)
            ->where('p.schedualed_at', '>=', $targetDate)
            ->where('p.active', 1)
            ->where('p.stage_code', '<=', 7)
            ->select(
                'p.id as plan_id',
                DB::raw("
                    CASE
                            WHEN p.stage_code >=8 THEN p.title
                            ELSE CONCAT(
                            pn.name,
                            '-',
                            COALESCE(pm.actual_batch, pm.batch)
                            )
                    END AS plan_title
                "),
                'pn.name as product_name',
                'p.start as current_start',
                DB::raw("NULL as old_start"),
                'p.end as current_end',
                DB::raw("NULL as old_end"),
                'current_room.name as current_room_name',
                DB::raw("NULL as old_room_name"),
                DB::raw("NULL as history_saved_at"),
                'fpc.finished_product_code',
                DB::raw("COALESCE(pm.actual_batch, pm.batch) AS batch"),
                'p.finished',
                'p.stage_code',
                'p.schedualed_at as current_created_date',
                DB::raw("1 as version")
            )
            ->orderBy('p.schedualed_at', 'desc')
            ->get();

        $allPlans = $changedPlans->merge($newPlans);

        return response()->json($allPlans);
    }
}
