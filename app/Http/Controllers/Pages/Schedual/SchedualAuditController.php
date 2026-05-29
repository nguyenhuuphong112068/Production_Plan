<?php

namespace App\Http\Controllers\Pages\Schedual;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SchedualAuditController extends Controller
{
        public function index(Request $request){
                $production_code = session('user')['production_code'];

                // 1. LẤY DANH SÁCH PLAN LIST
                $datas = DB::table('plan_list')
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

                // 5. MERGE DỮ LIỆU
                $datas = $datas->map(function ($item) use ($total_batch_qtys, $tong_lo_counts, $historyCountsGrouped) {
                        $item->total_batch_qty = $total_batch_qtys[$item->id]->total_batch_qty ?? 0;
                        $item->tong_lo = $tong_lo_counts[$item->id]->total ?? 0;

                        $itemHistory = $historyCountsGrouped->get($item->id) ?? collect();
                        $item->status_counts = [
                                'Đã Cân' => $itemHistory->whereIn('stage_code', [1, 2])->sum('total'),
                                'Đã Pha chế' => $itemHistory->firstWhere('stage_code', 3)->total ?? 0,
                                'Đã THT' => $itemHistory->firstWhere('stage_code', 4)->total ?? 0,
                                'Đã định hình' => $itemHistory->firstWhere('stage_code', 5)->total ?? 0,
                                'Đã Bao phim' => $itemHistory->firstWhere('stage_code', 6)->total ?? 0,
                                'Hoàn Tất ĐG' => $itemHistory->firstWhere('stage_code', 7)->total ?? 0,
                        ];

                        return $item;
                });

                session()->put(['title'=> 'LỊCH SỬ THAY ĐỔI LỊCH SẢN XUẤT']);
                return view('pages.Schedual.audit.plan_list',[
                        'datas' => $datas,
                ]);
        }

        public function open(Request $request){
                $plan_list_id = $request->plan_list_id;
                $production = session('user')['production_code'];
                
                $fromDate = $request->from_date;
                $toDate   = $request->to_date; 
                $stage_code = $request->stage_code;

                // Subquery: lấy version lớn nhất cho mỗi stage_plan_id
                $maxVersionSub = DB::table('stage_plan_history')
                    ->select('stage_plan_id', DB::raw('MAX(version) as max_version'))
                    ->groupBy('stage_plan_id');

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
                    ->where('sp.plan_list_id', $plan_list_id)
                    ->where('sp.deparment_code', $production)
                    ->when($fromDate, function ($q) use ($fromDate) {
                        return $q->whereDate('h.start', '>=', $fromDate);
                    })
                    ->when($toDate, function ($q) use ($toDate) {
                        return $q->whereDate('h.start', '<=', $toDate);
                    })
                    ->when($stage_code, function ($q) use ($stage_code) {
                        return $q->where('sp.stage_code', $stage_code);
                    })
                    ->orderBy('h.start', 'desc')
                    ->get();

                $stages = DB::table('stage_plan_history')
                    ->select('stage_plan_history.stage_code', 'room.stage')
                    ->where('stage_plan_history.deparment_code', $production)
                    ->whereNotNull('stage_plan_history.start')
                    ->leftJoin('room', 'stage_plan_history.resourceId', 'room.id')
                    ->distinct()
                    ->orderby('stage_code')
                    ->get();

                $stageCode = $request->input('stage_code', $stage_code ?? optional($stages->first())->stage_code);

                session()->put(['title'=> 'CHI TIẾT LỊCH SỬ THAY ĐỔI LỊCH SẢN XUẤT']);
                return view('pages.Schedual.audit.list',[
                    'datas' => $datas,
                    'stages' => $stages,
                    'stageCode' => $stageCode,
                    'plan_list_id' => $plan_list_id
                ]);
        }

        public function history (Request $request){
          
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
                    return $q->where('sp.plan_list_id', $request->plan_list_id);
                })
                ->when($request->id, function ($q) use ($request) {
                    return $q->where('h.stage_plan_id', $request->id);
                })
                ->orderBy('h.version', 'desc')
                ->get();

            return response()->json($datas);
        }
}
