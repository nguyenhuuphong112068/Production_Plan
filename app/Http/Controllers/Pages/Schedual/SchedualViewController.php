<?php

namespace App\Http\Controllers\Pages\Schedual;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SchedualViewController extends Controller
{
        public function list(Request $request){
                //dd ($request->all());

                $fromDate = $request->from_date ?? Carbon::now()->toDateString();
                $toDate   = $request->to_date   ?? Carbon::now()->addMonth(2)->toDateString(); 
                $stage_code = $request->stage_code??3;
                $production = session('user')['production_code'];
      
                $datas = DB::table('stage_plan')
                ->select('stage_plan.*',
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
                        'market.name as name',
                        'product_name.name as product_name'
                )
                ->whereBetween('stage_plan.start', [$fromDate, $toDate])
                ->where('stage_plan.active', 1)->where ('stage_plan.stage_code', $stage_code)
                ->where('stage_plan.deparment_code', $production)->where('stage_plan.finished', 0)->whereNotNull('stage_plan.start')
                ->when(!in_array(session('user')['userGroup'], ['Schedualer', 'Admin', 'Leader']),fn($query) => $query->where('submit', 1))
                ->leftJoin('room', 'stage_plan.resourceId', 'room.id')
                ->leftJoin('plan_master', 'stage_plan.plan_master_id', 'plan_master.id')
                ->leftJoin('finished_product_category', 'stage_plan.product_caterogy_id', '=', 'finished_product_category.id')
                ->leftJoin('intermediate_category', 'finished_product_category.intermediate_code', '=', 'intermediate_category.intermediate_code')
                ->leftJoin('product_name','finished_product_category.product_name_id','product_name.id')
                ->leftJoin('market','finished_product_category.market_id','market.id')
                ->orderBy('start')
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
               
            
                session()->put(['title'=> 'Lịch Sản Xuất']);
                return view('pages.Schedual.list.list',[

                        'datas' => $datas,
                        'stages' => $stages,
                        'stageCode' => $stageCode
                    
                ]);
        }



}
