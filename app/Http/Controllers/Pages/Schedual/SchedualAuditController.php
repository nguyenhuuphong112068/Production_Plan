<?php

namespace App\Http\Controllers\Pages\Schedual;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SchedualAuditController extends Controller
{
    public function index(Request $request)
    {
        $production_code = session('user')['production_code'];

        // 1. LẤY DANH SÁCH PLAN LIST
        $rawDatas = DB::table('plan_list')
            ->where('active', 1)
            ->where('deparment_code', $production_code)
            ->where('type', 1)
            ->orderBy('id', 'desc')
            ->get();

        // 2. TỔNG BATCH THEO PLAN_LIST
        $total_batch_qtys = DB::table('plan_master as pm')
            ->join('finished_product_category as fpc', 'pm.product_caterogy_id', '=', 'fpc.id')
            ->join('plan_list as pl', 'pm.plan_list_id', '=', 'pl.id')
            ->where('pm.active', 1)
            ->where('pm.cancel', 0)
            ->where('pm.only_parkaging', 0)
            ->where('fpc.active', 1)
            ->where('pm.deparment_code', $production_code)
            ->where('pl.type', 1)
            ->groupBy('pm.plan_list_id')
            ->select(
                'pm.plan_list_id',
                DB::raw('SUM(fpc.batch_qty) as total_batch_qty')
            )
            ->get()
            ->keyBy('plan_list_id');

        // 3. TÍNH TỔNG SỐ LÔ THEO PLAN_LIST
        $tong_lo_counts = DB::table('plan_master')
            ->where('active', 1)
            ->where('deparment_code', $production_code)
            ->groupBy('plan_list_id')
            ->select('plan_list_id', DB::raw('COUNT(*) as total'))
            ->get()
            ->keyBy('plan_list_id');

        // 4. THỐNG KÊ SỐ LẦN THAY ĐỔI THEO CÔNG ĐOẠN (stage_plan_history version > 1)
        $historyCounts = DB::table('stage_plan_history as h')
            ->join('stage_plan as sp', 'h.stage_plan_id', '=', 'sp.id')
            ->select('sp.plan_list_id', 'sp.stage_code', DB::raw('COUNT(*) as total'))
            ->where('h.version', '>', 1)
            ->groupBy('sp.plan_list_id', 'sp.stage_code')
            ->get();

        $historyCountsGrouped = $historyCounts->groupBy('plan_list_id');

        // 5. GOM NHÓM THEO THÁNG VÀ NĂM
        $grouped = $rawDatas->groupBy(function ($item) {
            return $item->month . '-' . $item->year;
        });

        $datas = $grouped->map(function ($group) use ($total_batch_qtys, $tong_lo_counts, $historyCountsGrouped) {
            $first = $group->first();

            // Gom tất cả ID trong nhóm này lại thành chuỗi phân cách bởi dấu phẩy
            $ids = $group->pluck('id')->toArray();
            $first->id = implode(',', $ids);

            // Đặt lại tên chuẩn hóa dạng: KHSX Tháng X - Y
            $first->name = "KHSX Tháng " . $first->month . " - " . $first->year;

            // Tính tổng batch qty và tổng lô của tất cả plan_list trong nhóm
            $first->total_batch_qty = 0;
            $first->tong_lo = 0;
            foreach ($group as $item) {
                $first->total_batch_qty += $total_batch_qtys[$item->id]->total_batch_qty ?? 0;
                $first->tong_lo += $tong_lo_counts[$item->id]->total ?? 0;
            }

            // Tổng hợp tình trạng: nếu có ít nhất 1 cái chưa gửi (send = 0), thì để Pending (0), ngược lại là Send (1)
            $first->send = $group->every('send', 1) ? 1 : 0;

            // Lấy ngày tạo mới nhất và người tạo tương ứng
            $latest = $group->sortByDesc('created_at')->first();
            $first->created_at = $latest->created_at;
            $first->prepared_by = $latest->prepared_by;

            // Khởi tạo bộ đếm số lần thay đổi
            $first->status_counts = [
                'Đã Cân' => 0,
                'Đã Pha chế' => 0,
                'Đã THT' => 0,
                'Đã định hình' => 0,
                'Đã Bao phim' => 0,
                'Hoàn Tất ĐG' => 0,
            ];

            foreach ($group as $item) {
                $itemHistory = $historyCountsGrouped->get($item->id) ?? collect();

                $first->status_counts['Đã Cân'] += $itemHistory->whereIn('stage_code', [1, 2])->sum('total');
                $first->status_counts['Đã Pha chế'] += $itemHistory->firstWhere('stage_code', 3)->total ?? 0;
                $first->status_counts['Đã THT'] += $itemHistory->firstWhere('stage_code', 4)->total ?? 0;
                $first->status_counts['Đã định hình'] += $itemHistory->firstWhere('stage_code', 5)->total ?? 0;
                $first->status_counts['Đã Bao phim'] += $itemHistory->firstWhere('stage_code', 6)->total ?? 0;
                $first->status_counts['Hoàn Tất ĐG'] += $itemHistory->firstWhere('stage_code', 7)->total ?? 0;
            }

            return $first;
        })->values();

        session()->put(['title' => 'LỊCH SỬ THAY ĐỔI LỊCH SẢN XUẤT']);
        return view('pages.Schedual.audit.plan_list', [
            'datas' => $datas,
        ]);
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
