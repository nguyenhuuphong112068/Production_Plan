<?php

namespace App\Http\Controllers\Pages\History;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
class HistoryController extends Controller{
       public function index(Request $request){

                $fromDate = $request->from_date ?? Carbon::now()->subMonth(1)->toDateString();
                $toDate   = $request->to_date   ?? Carbon::now()->toDateString(); 
                $stage_code = $request->stage_code??3;
                $production = session('user')['production'];
      
                $datas = DB::table('stage_plan')
                ->select('stage_plan.*',
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
                        'finished_product_category.market'
                )
                ->whereBetween('stage_plan.start', [$fromDate, $toDate])
                ->where('stage_plan.active', 1)->where ('stage_plan.stage_code', $stage_code)
                ->where('stage_plan.deparment_code', $production)->where('stage_plan.finished', 1)->whereNotNull('stage_plan.start')
                ->leftJoin('room', 'stage_plan.resourceId', 'room.id')
                ->leftJoin('plan_master', 'stage_plan.plan_master_id', 'plan_master.id')
                ->leftJoin('finished_product_category', 'stage_plan.product_caterogy_id', '=', 'finished_product_category.id')
                ->get();

                $stages = DB::table('stage_plan')
                ->select('stage_plan.stage_code', 'room.stage')
                ->where('stage_plan.active', 1)
                ->where('stage_plan.deparment_code', $production)
                ->where('stage_plan.finished', 1)
                ->whereNotNull('stage_plan.start')
                ->leftJoin('room', 'stage_plan.resourceId', 'room.id')
                ->distinct()
                ->orderby ('stage_code')
                ->get();

                 $stageCode = $request->input('stage_code', optional($stages->first())->stage_code);
               
            
                session()->put(['title'=> 'Lịch Sử Sản Xuất']);
                return view('pages.History.list',[

                        'datas' => $datas,
                        'stages' => $stages,
                        'stageCode' => $stageCode
                    
                ]);
        }
    
}
