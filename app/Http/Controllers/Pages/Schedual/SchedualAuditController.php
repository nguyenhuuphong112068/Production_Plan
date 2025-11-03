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
      
                $datas = DB::table('stage_plan_history')
                ->select('stage_plan_history.*',
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
                ->whereBetween('stage_plan_history.start', [$fromDate, $toDate])
                ->where('stage_plan_history.active', 1)->where ('stage_plan_history.stage_code', $stage_code)
                ->where('stage_plan_history.deparment_code', $production)->where('stage_plan_history.finished', 0)->whereNotNull('stage_plan_history.start')
                ->leftJoin('room', 'stage_plan_history.resourceId', 'room.id')
                ->leftJoin('plan_master', 'stage_plan_history.plan_master_id', 'plan_master.id')
                ->leftJoin('finished_product_category', 'stage_plan_history.product_caterogy_id', '=', 'finished_product_category.id')
                ->leftJoin('product_name','finished_product_category.product_name_id','product_name.id')
                ->leftJoin('market','finished_product_category.market_id','market.id')
                ->get();

                $stages = DB::table('stage_plan')
                ->select('stage_plan.stage_code', 'room.stage')
                ->where('stage_plan.active', 1)
                ->where('stage_plan.deparment_code', $production)
                ->where('stage_plan.finished', 0)
                ->whereNotNull('stage_plan.start')
                ->leftJoin('room', 'stage_plan.resourceId', 'room.id')
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
