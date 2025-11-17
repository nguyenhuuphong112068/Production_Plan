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
            $toDate   = $request->to_date ?? Carbon::now()->addMonth(2)->toDateString(); 
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
                ->leftJoin('stage_plan as sp', 'h.stage_plan_id', '=', 'sp.id')
                ->leftJoin('room', 'h.resourceId', '=', 'room.id')
                ->leftJoin('plan_master', 'sp.plan_master_id', '=', 'plan_master.id')
                ->leftJoin('finished_product_category', 'sp.product_caterogy_id', '=', 'finished_product_category.id')
                ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
                ->leftJoin('market', 'finished_product_category.market_id', '=', 'market.id')
                ->where('h.version', '>=', 1)
                ->whereBetween('h.start', [$fromDate, $toDate])
                ->where('h.stage_code', $stage_code)
                ->where('h.deparment_code', $production)
                ->whereNotNull('h.start')
                ->whereIn('h.stage_plan_id', function ($query) {
                    $query->select('stage_plan_id')
                        ->from('stage_plan_history')
                        ->groupBy('stage_plan_id')
                        ->havingRaw('COUNT(*) > 1');
                })
                //->orderBy('h.plan_master_id')
                ->orderBy('h.version', 'desc')
                ->get();


                  
        
                $stages = DB::table('stage_plan_history')
                    ->select('stage_plan_history.stage_code', 'room.stage')
                    ->where('stage_plan_history.deparment_code', $production)
        
                    ->whereNotNull('stage_plan_history.start')
                    ->leftJoin('room', 'stage_plan_history.resourceId', 'room.id')
                    ->distinct()
                    ->orderby ('stage_code')
                ->get();

                 $stageCode = $request->input('stage_code', optional($stages->first())->stage_code);
                
                //dd ($datas);
                session()->put(['title'=> 'LỊCH SỬ THAY ĐỔI LỊCH SẢN XUẤT']);
                return view('pages.Schedual.audit.list',[

                        'datas' => $datas,
                        'stages' => $stages,
                        'stageCode' => $stageCode
                    
                ]);
        }

        public function history (Request $request){
          
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
                    
                    ->leftJoin('room', 'h.resourceId', '=', 'room.id')
                    ->leftJoin('plan_master', 'h.plan_master_id', '=', 'plan_master.id')
                    ->leftJoin('finished_product_category', 'h.product_caterogy_id', '=', 'finished_product_category.id')
                    ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
                    ->leftJoin('market', 'finished_product_category.market_id', '=', 'market.id')
                    ->where('h.stage_plan_id', $request->id)
                    ->orderBy('h.version', 'desc')
                    ->get();

                    return response()->json( $datas);
        }
}
