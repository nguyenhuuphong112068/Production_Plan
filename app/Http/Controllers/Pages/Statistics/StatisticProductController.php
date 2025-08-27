<?php

namespace App\Http\Controllers\Pages\Statistics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StatisticProductController extends Controller
{
    public function index(Request $request){
        $production = session('user')['production'];

        // ---- 1. Xác định khoảng thời gian người dùng chọn hoặc mặc định ----
        $fromDate = $request->from_date ?? Carbon::now()->subMonth(1)->toDateString();
        $toDate   = $request->to_date   ?? Carbon::now()->toDateString();

        $fromDate = Carbon::parse($fromDate);
        $toDate   = Carbon::parse($toDate);

        $totalDays   = $fromDate->diffInDays($toDate);
        $totalHours  = $totalDays * 24;

       
        $products = DB::table('stage_plan')
                ->select(
                        'finished_product_category.finished_product_code','finished_product_category.intermediate_code',
                        'finished_product_category.name','finished_product_category.batch_qty',
                        'finished_product_category.unit_batch_qty','finished_product_category.market',
                        'stage_plan.product_caterogy_id', 'stage_plan.stage_code',
                        DB::raw('COUNT(DISTINCT stage_plan.plan_master_id) as so_lo'),
                        DB::raw('SUM(TIMESTAMPDIFF(HOUR, stage_plan.start_clearning, stage_plan.end_clearning)) as tong_thoi_gian_vesinh'),
                        DB::raw('SUM(TIMESTAMPDIFF(HOUR, stage_plan.start, stage_plan.end)) as tong_thoi_gian_sanxuat'),
                        DB::raw('SUM(stage_plan.yields) as san_luong_thuc_te'),
                        DB::raw('COUNT(DISTINCT stage_plan.plan_master_id) * finished_product_category.batch_qty as san_luong_ly_thuyet')
                )
                ->leftJoin('finished_product_category','stage_plan.product_caterogy_id','finished_product_category.id')
                ->whereBetween('stage_plan.start', [$fromDate, $toDate])
                ->where('stage_plan.active', 1)
                ->where('stage_plan.deparment_code', $production)
                ->where('stage_plan.finished', 1)
                ->where('stage_plan.stage_code', ">=",7)
                ->groupBy(  'finished_product_category.finished_product_code','finished_product_category.intermediate_code',
                            'finished_product_category.name','finished_product_category.batch_qty',
                            'finished_product_category.unit_batch_qty','finished_product_category.market',
                            'stage_plan.product_caterogy_id', 'stage_plan.stage_code')
                ->get();
        
        

      
        
        session()->put(['title' => 'THỐNG KÊ THỜI GIAN HOẠT ĐỘNG THEO PHÒNG SẢN XUẤT']);
       
        return view('pages.statistics.product.list', [
            'products'         => $products,
            'totalHours'       => $totalHours,
        ]);
    }   
    
    public function yield($startDate, $endDate, $group_By){
               return DB::table('stage_plan as sp')
                ->leftJoin('intermediate_category as ic', 'sp.product_caterogy_id', '=', 'ic.id')
                ->leftJoin('finished_product_category as fc', 'sp.product_caterogy_id', '=', 'fc.id')
                ->whereBetween('sp.start', [$startDate, $endDate])
                ->whereNotNull('sp.start')
                ->select(
                    "sp.$group_By",
                    DB::raw('
                        SUM(
                            CASE 
                                WHEN sp.stage_code <= 4 THEN ic.batch_size
                                WHEN sp.stage_code <= 6 THEN ic.batch_qty
                                ELSE fc.batch_qty
                            END
                        ) as total_qty
                    '),
                    DB::raw('
                        CASE 
                            WHEN sp.stage_code <= 4 THEN "Kg"
                            ELSE "ĐVL"
                        END as unit
                    ')
                )
                ->groupBy("sp.$group_By", "unit")
                ->get();
    }
}
