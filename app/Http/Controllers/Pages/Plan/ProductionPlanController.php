<?php

namespace App\Http\Controllers\Pages\Plan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionPlanController extends Controller
{
        public function index(){
        
                $datas = DB::table('plan_list')
                ->where ('active',1)
                ->orderBy('created_at','desc')->get();
        
                session()->put(['title'=> 'Kế Hoạch Sản Xuất Tháng']);
        
                return view('pages.plan.production.plan_list',['datas' => $datas ]);
        }

        public function open(Request  $request){
               
                $datas = DB::table('plan_master')
                ->select('plan_master.*', 
                        'finished_product_category.intermediate_code', 
                        'finished_product_category.finished_product_code', 
                        'finished_product_category.name',
                        'finished_product_category.market', 
                        'finished_product_category.specification', 
                        'finished_product_category.batch_qty',
                        'finished_product_category.unit_batch_qty',
                        'finished_product_category.deparment_code'
 
                        )
                ->where ('plan_master.active',1)->where ('plan_list_id',$request->plan_list_id)
                ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', 'finished_product_category.id')
                ->orderBy('created_at','desc')->get();
        
                session()->put(['title'=> "Kế Hoạch Sản Xuất Tháng $request->month - $request->production"]);
        
                return view('pages.plan.production.list',[
                        'datas' => $datas, 
                        'plan_list_id' => $request->plan_list_id,
                        'month' => $request->month, 
                        'production' => $request->production
                
                ]);
        }
        
        public function send(Request $request){
               
                $plans = DB::table('plan_master')
                ->where('plan_master.plan_list_id', $request->plan_list_id)
                ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', '=', 'finished_product_category.id')
                ->leftJoin('intermediate_category', 'intermediate_category.intermediate_code', '=', 'finished_product_category.intermediate_code')
                ->select(
                        'plan_master.id',
                        'plan_master.plan_list_id',
                        'plan_master.product_caterogy_id',
                        'intermediate_category.weight_1',
                        'intermediate_category.prepering',
                        'intermediate_category.blending',
                        'intermediate_category.forming',
                        'intermediate_category.coating',
                        'finished_product_category.primary_parkaging',
                        'finished_product_category.secondary_parkaging'
                )
                ->get();

                $stages = ['weight_1', 'prepering', 'blending', 'forming', 'coating', 'primary_parkaging' ];
                $stage_code = [
                        'weight_1' => 1,
                        'prepering' => 3,
                        'blending'=> 4,
                        'forming'=> 5,
                        'coating'=> 6,
                        'primary_parkaging'=> 7,
                ];

                foreach ($plans as $plan) {
                        foreach ($stages as $stage) {
                        if ($plan->$stage) {
                                DB::table('stage_plan')->insert([
                                'plan_list_id' => $plan->plan_list_id,
                                'plan_master_id' => $plan->id,
                                'product_caterogy_id'=> $plan->product_caterogy_id,
                                'stage_code'=> $stage_code[$stage],            
                                ]);
                        }}
                }

                DB::table('plan_list')->where ('id', $request->plan_list_id)->update([
                        'send' => 1,
                        'send_by' => session('user')['fullName'],
                        'send_date' => now(),
                 ]);


                $datas = DB::table('plan_list')
                ->where ('active',1)
                ->orderBy('created_at','desc')->get();

                 session()->put(['title'=> 'Kế Hoạch Sản Xuất Tháng']);
                 return view('pages.plan.production.plan_list',['datas' => $datas ]);
        }



}
