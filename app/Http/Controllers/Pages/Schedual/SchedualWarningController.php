<?php

namespace App\Http\Controllers\Pages\Schedual;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SchedualWarningController extends Controller
{
    public function index(Request $request)
    {
        $production = session('user')['production_code'];

        // 1. Không Đáp Ứng Ngày Cần Hàng Theo Kế Hoạch
        // Lô có stage_code == 7, start không null, và end > expected_date - 5 ngày
        $unmetPlans = DB::table('stage_plan')
            ->select(
                'plan_master.id',
                'plan_master.batch',
                'plan_master.expected_date',
                'finished_product_category.finished_product_code',
                'product_name.name as product_name',
                DB::raw('MAX(stage_plan.end) as max_end'),
                DB::raw('MIN(stage_plan.start) as min_start')
            )
            ->leftJoin('plan_master', 'stage_plan.plan_master_id', '=', 'plan_master.id')
            ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', '=', 'finished_product_category.id')
            ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
            ->where('stage_plan.active', 1)
            ->where('stage_plan.finished', 0)
            ->where('stage_plan.stage_code', 7)
            ->whereNotNull('stage_plan.start')
            ->where('stage_plan.deparment_code', $production)
            ->whereRaw('stage_plan.end > DATE_SUB(plan_master.expected_date, INTERVAL 5 DAY)')
            ->groupBy('plan_master.id', 'plan_master.batch', 'plan_master.expected_date', 'finished_product_category.finished_product_code', 'product_name.name')
            ->orderBy('plan_master.expected_date', 'asc')
            ->get();

        // 2. Cảnh Báo Ngày Đáp Ứng NL/BB
        // Lô có start < responsed_date, start không null
        $materialWarnings = DB::table('stage_plan')
            ->select(
                'plan_master.id',
                'plan_master.batch',
                'plan_master.responsed_date',
                'finished_product_category.finished_product_code',
                'product_name.name as product_name',
                DB::raw('MIN(stage_plan.start) as min_start')
            )
            ->leftJoin('plan_master', 'stage_plan.plan_master_id', '=', 'plan_master.id')
            ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', '=', 'finished_product_category.id')
            ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
            ->where('stage_plan.active', 1)
            ->where('stage_plan.finished', 0)
            // Lấy công đoạn 1 (Cân) để cảnh báo ngày đáp ứng NL/BB
            ->whereIn('stage_plan.stage_code', [1, 2])
            ->whereNotNull('stage_plan.start')
            ->whereNotNull('plan_master.responsed_date')
            ->where('stage_plan.deparment_code', $production)
            ->whereRaw('DATE(stage_plan.start) < DATE(plan_master.responsed_date)')
            ->groupBy('plan_master.id', 'plan_master.batch', 'plan_master.responsed_date', 'finished_product_category.finished_product_code', 'product_name.name')
            ->orderBy(DB::raw('MIN(stage_plan.start)'), 'asc')
            ->get();

        session()->put(['title' => 'CẢNH BÁO LỊCH SẢN XUẤT']);
        
        return view('pages.schedual.warning.index', compact('unmetPlans', 'materialWarnings'));
    }
}
