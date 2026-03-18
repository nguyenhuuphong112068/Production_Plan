<?php

namespace App\Http\Controllers\Pages\History;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HistoryController extends Controller
{

        public function index(Request $request)
        {

                $fromDate = $request->from_date ?? Carbon::now()->subMonth(1)->toDateString();
                $toDate   = $request->to_date ?? Carbon::now()->addDays(1)->toDateString();
                //dd ($fromDate, $toDate  );
                $stage_code = $request->stage_code ?? 1;
                $production = session('user')['production_code'];

                $yieldSub = DB::table('yields')
                        ->select(
                                'stage_plan_id',
                                DB::raw('SUM(yield) as sum_actual_yeild')
                        )
                        ->groupBy('stage_plan_id');

                $datas = DB::table('stage_plan')
                        ->leftJoinSub($yieldSub, 'y_sum', function ($join) {
                                $join->on('stage_plan.id', '=', 'y_sum.stage_plan_id');
                        })
                        ->leftJoin('room', 'stage_plan.resourceId', 'room.id')
                        ->leftJoin('plan_master', 'stage_plan.plan_master_id', 'plan_master.id')
                        ->leftJoin('finished_product_category', 'stage_plan.product_caterogy_id', '=', 'finished_product_category.id')
                        ->leftJoin('intermediate_category', 'finished_product_category.intermediate_code', '=', 'intermediate_category.intermediate_code')
                        ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
                        ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
                        ->select(
                                'stage_plan.*',
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
                                'market.name as market',
                                'product_name.name as name',
                                DB::raw("COALESCE(y_sum.sum_actual_yeild,0) as sum_actual_yeild")
                        )
                        ->where('stage_plan.deparment_code', $production)
                        ->whereBetween('stage_plan.actual_start', [$fromDate, $toDate])
                        ->whereNotNull('stage_plan.actual_start')
                        ->where('stage_plan.active', 1)
                        ->where('stage_plan.finished', 1)
                        ->where('stage_plan.stage_code', $stage_code)
                        ->get();



                $stages = DB::table('stage_plan')
                        ->select(
                                'stage_plan.stage_code',
                                DB::raw("
                        CASE 
                                WHEN stage_plan.stage_code = 2 THEN 'Cân Nguyên Liệu Khác'
                                ELSE room.stage
                        END AS stage
                        ")
                        )
                        ->leftJoin('room', 'stage_plan.stage_code', '=', 'room.stage_code')
                        ->where('stage_plan.deparment_code', $production)
                        ->distinct()
                        ->orderBy('stage_plan.stage_code')
                        ->get();

                $stageCode = $request->input('stage_code', optional($stages->first())->stage_code);



                session()->put(['title' => 'LỊCH SỬ SẢN XUẤT']);
                return view('pages.History.list', [

                        'datas' => $datas,
                        'stages' => $stages,
                        'stageCode' => $stageCode

                ]);
        }

        public function returnStage(Request $request)
        {

                DB::beginTransaction();
                try {

                        // 🔎 Kiểm tra tồn tại
                        if (!$request->has('stage_plan_id')) {
                                return response()->json([
                                        'success' => false,
                                        'message' => 'Stage plan id is required'
                                ], 400);
                        }

                        // ✅ Reset trạng thái stage
                        DB::table('stage_plan')
                                ->where('id', $request->stage_plan_id)
                                ->update([
                                        'finished' => 0,
                                        'actual_start' => null,
                                        'actual_end' => null,
                                        'actual_start_clearning' => null,
                                        'actual_end_clearning' => null,
                                        'yields' => null,
                                        'finished_by' => session('user')['fullName'],
                                        'finished_date' => now(),
                                ]);


                        // ✅ Xoá toàn bộ yields liên quan
                        DB::table('yields')
                                ->where('stage_plan_id', $request->stage_plan_id)
                                ->delete();

                        DB::commit();

                        return response()->json([
                                'success' => true,
                                'message' => 'Trả Về Thành Công!'
                        ]);
                } catch (\Exception $e) {

                        DB::rollBack();

                        return response()->json([
                                'success' => false,
                                'message' => 'Error while returning stage',
                                'error' => $e->getMessage()
                        ], 500);
                }
        }
}
