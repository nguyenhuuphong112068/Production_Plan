<?php

namespace App\Http\Controllers\Pages\Schedual;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SchedualAuditController extends Controller
{
        public function index(Request $request){
                //dd ($request->all());

                $fromDate = $request->from_date ?? Carbon::now()->toDateString();
                $toDate   = $request->to_date   ?? Carbon::now()->addMonth(2)->toDateString(); 
                $stage_code = $request->stage_code??3;
                $production = session('user')['production_code'];
      
                // 🔹 1. Lấy dữ liệu mới nhất cho mỗi stage_plan_id
                $datas = DB::table('stage_plan_history as h')
                    ->select(
                        'h.*',
                        'room.name as room_name',
                        'room.code as room_code',
                        'room.stage as stage',
                        'plan_master.batch',
                        'plan_master.expected_date',
                        'plan_master.is_val',
                        'finished_product_category.intermediate_code',
                        'finished_product_category.finished_product_code',
                        'finished_product_category.batch_qty',
                        'finished_product_category.unit_batch_qty',
                        'market.name as name'
                    )
                    ->joinSub(
                        DB::table('stage_plan_history')
                            ->select('stage_plan_id', DB::raw('MAX(version) as max_version'))
                            ->groupBy('stage_plan_id'),
                        'latest',
                        function ($join) {
                            $join->on('h.stage_plan_id', '=', 'latest.stage_plan_id')
                                ->on('h.version', '=', 'latest.max_version');
                        }
                    )
                    ->leftJoin('room', 'h.resourceId', '=', 'room.id')
                    ->leftJoin('plan_master', 'h.plan_master_id', '=', 'plan_master.id')
                    ->leftJoin('finished_product_category', 'h.product_caterogy_id', '=', 'finished_product_category.id')
                    ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
                    ->leftJoin('market', 'finished_product_category.market_id', '=', 'market.id')
                    ->whereBetween('h.start', [$fromDate, $toDate])
                    ->where('h.stage_code', $stage_code)
                    ->where('h.deparment_code', $production)
                    ->whereNotNull('h.start')
                    ->get();

                // 🔹 2. Lấy số lần xuất hiện (lịch sử version) của mỗi stage_plan_id
                $historyCounts = DB::table('stage_plan_history')
                    ->select('stage_plan_id', DB::raw('COUNT(stage_plan_id) as count'))
                    ->groupBy('stage_plan_id')
                    ->pluck('count', 'stage_plan_id');

              
                $datas = $datas->transform(function ($item) use ($historyCounts, $request) {
                    $item->history_count = $historyCounts[$item->stage_plan_id] ?? 0;
                    return $item;
                });

                if ($request->filter_has_change == 1) {
                    $datas = $datas->filter(fn($item) => $item->history_count > 1);
                }
               

                $stages = DB::table('stage_plan_history')
                    ->select('stage_plan_history.stage_code', 'room.stage')
                    ->where('stage_plan_history.deparment_code', $production)
        
                    ->whereNotNull('stage_plan_history.start')
                    ->leftJoin('room', 'stage_plan_history.resourceId', 'room.id')
                    ->distinct()
                    ->orderby ('stage_code')
                ->get();

                 $stageCode = $request->input('stage_code', optional($stages->first())->stage_code);
                
                
                session()->put(['title'=> 'LỊCH SỮ THAY ĐỔI LỊCH SẢN XUẤT']);
                return view('pages.Schedual.audit.list',[

                        'datas' => $datas,
                        'stages' => $stages,
                        'stageCode' => $stageCode
                    
                ]);
        }
}
