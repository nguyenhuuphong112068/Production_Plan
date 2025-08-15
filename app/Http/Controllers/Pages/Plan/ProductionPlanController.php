<?php

namespace App\Http\Controllers\Pages\Plan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;


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
                        'finished_product_category.deparment_code',
                        'source_material.name as source_material_name'
 
                        )
                ->where ('plan_master.active',1)->where ('plan_list_id',$request->plan_list_id)
                ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', 'finished_product_category.id')
                ->leftJoin('source_material', 'plan_master.material_source_id', 'source_material.id')
                ->orderBy('level','asc')->orderBy('expected_date','asc')->get();

                $finished_product_category = DB::table('finished_product_category')->where ('active',1)->orderBy('name','asc')->get();
                
                $source_material_list = DB::table('source_material')
                ->select('source_material.*', 'intermediate_category.name as product_name')
                ->leftJoin('intermediate_category', 'source_material.intermediate_code', 'intermediate_category.intermediate_code')
                ->where ('source_material.active',1)->orderBy('source_material.name','asc')->get();
      

                session()->put(['title'=> "Kế Hoạch Sản Xuất Tháng $request->month - $request->production"]);
        
                return view('pages.plan.production.list',[
                        'datas' => $datas, 
                        'plan_list_id' => $request->plan_list_id,
                        'month' => $request->month, 
                        'production' => $request->production,
                        'finished_product_category' => $finished_product_category,
                        'source_material_list'=> $source_material_list
                
                ]);
        }

        public function store (Request $request) {
               

                $validator = Validator::make($request->all(), [
                        'product_caterogy_id' => 'required',
                        'plan_list_id'   => 'required',
                        'batch' => 'required',
                        'expected_date' => 'required',
                        'level' => 'required',
                        'after_weigth_date' => 'required',
                        'before_weigth_date' => 'required',
                        'after_parkaging_date' => 'required',
                        'before_parkaging_date' => 'required',
                        'material_source_id' => 'required',
                        'percent_packaging' => 'required',
                        'number_of_batch' => 'required',
                ], [
                        'product_caterogy_id' => 'Vui lòng chọn lại sản phẩm.',
                        'plan_list_id'   => 'Vui lòng chọn lại sản phẩm',
                        'batch' => 'Vui lòng nhập số lô',
                        'expected_date' => 'Vui lòng chọn ngày dự kiến KCS',
                        'level' => 'vui lòng chọn mức độ ưu tiên',
                        'after_weigth_date' => 'vui lòng chọn ngày có thể cân',
                        'before_weigth_date' => 'vui lòng chọn ngày cân trước',
                        'after_parkaging_date' => 'vui lòng chọn ngày có thể đóng gói',
                        'before_parkaging_date' => 'vui lòng chọn ngày có đóng gói trước',
                        'material_source_id' => 'vui lòng chọn nguồn nguyên liệu',
                        'percent_packaging' => 'vui lòng nhập số lượng đơn vị liều đóng gói',
                        'number_of_batch' => 'vui lòng chọn số lượng lô',
                ]);
        

                if ($validator->fails() ) {
                        return redirect()->back()->withErrors($validator, 'create_Errors')->withInput();
                }
                if ($request->is_val = "on"){$is_val = 1; $charater_val = "V";}else {$is_val = 0;$charater_val = "";}

                // Tạo số lô
                $batches = [];
        
                if ($request->format_batch_no == "on"){
                        $prefix = Str::substr($request->batch, -4);
                        $aa     = intval(Str::substr($request->batch, 0, Str::length($request->batch) - 4));
                        for ($i = 1; $i <= $request->number_of_batch; $i++) {
                                $batches[] = sprintf("%02d", $aa) . $prefix . $charater_val;
                                $aa++;
                        }
                }else{
                        $prefix = Str::substr($request->batch,0,3);
                        $aa     = intval(Str::substr($request->batch, 3,3));
                        for ($i = 1; $i <= $request->number_of_batch; $i++) {
                                $batches[] = $prefix. sprintf("%02d", $aa) . $charater_val;
                                $aa++;
                        }
                }
                
                
                $dataToInsert = [];

                foreach ($batches as $batch) {

                        $dataToInsert[] = [

                                "product_caterogy_id" => $request->product_caterogy_id,
                                "plan_list_id" => $request->plan_list_id,
                                "batch" => $batch,
                                "expected_date" => $request->expected_date,
                                "level" => $request->level,
                                "is_val" => $is_val,
                                "after_weigth_date" => $request->after_weigth_date,
                                "before_weigth_date" => $request->before_weigth_date,
                                "after_parkaging_date" => $request->after_parkaging_date,
                                "before_parkaging_date" => $request->before_parkaging_date,
                                "material_source_id" => $request->material_source_id,
                                "percent_parkaging" => $request->percent_packaging,
                                "only_parkaging" => $request->only_parkaging??0,
                                "note" => $request->note??"NA",
                                'deparment_code'=>  session('user')['production'],
                                'prepared_by' => session('user')['fullName'],
                                'created_at' => now(),
                        ];
                }
                //dd ($dataToInsert);
                DB::table('plan_master')->insert($dataToInsert);


                return redirect()->back()->with('success', 'Đã thêm thành công!');    
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

                $dataToInsert = [];

               foreach ($plans as $plan) {
                        foreach ($stages as $stage) {
                                if ($plan->$stage) {
                                $dataToInsert[] = [
                                        'plan_list_id' => $plan->plan_list_id,
                                        'plan_master_id' => $plan->id,
                                        'product_caterogy_id'=> $plan->product_caterogy_id,
                                        'stage_code'=> $stage_code[$stage],
                                        'order_by'=>  $plan->id,
                                        'code'=>  $plan->id ."_". $stage_code[$stage]
                                ];
                                }
                        }
                }
                DB::table('stage_plan')->insert($dataToInsert);


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
