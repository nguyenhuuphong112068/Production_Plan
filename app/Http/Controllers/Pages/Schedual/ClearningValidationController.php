<?php

namespace App\Http\Controllers\Pages\Schedual;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ClearningValidationController extends Controller
{
        public function index(Request $request){
          
            $production = session('user')['production_code'];
      
                // 🔹 1. Lấy dữ liệu mới nhất cho mỗi stage_plan_id
            $datas = DB::table('stage_plan as h')
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
                ->leftJoin('room', 'h.resourceId', '=', 'room.id')
                ->leftJoin('plan_master', 'h.plan_master_id', '=', 'plan_master.id')
                ->leftJoin('finished_product_category', 'h.product_caterogy_id', '=', 'finished_product_category.id')
                ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
                ->leftJoin('market', 'finished_product_category.market_id', '=', 'market.id')
                ->where('h.clearning_validation', 1)
                ->where('h.finished', 0)
                ->where('h.active', 1)
                ->where('h.deparment_code', $production)
                ->whereNotNull('h.start')
                ->orderBy('h.start', 'asc')
                ->get();
                

                //dd ($datas);
                session()->put(['title'=> 'LỊCH THẨM ĐỊNH VỆ SINH']);
                return view('pages.Schedual.clearning_validation.list',[
                        'datas' => $datas,
                     
                    
                ]);
        }
}
