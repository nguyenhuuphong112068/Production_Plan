<?php

namespace App\Http\Controllers\Pages\Plan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;


class ProductionPlanController extends Controller
{
        public function index(){
           
                $datas = DB::table('plan_list')
                ->where ('active',1)
                ->where ('deparment_code',session('user')['production_code'])
                ->where ('type',1)
                ->orderBy('id','desc')
                ->get();
        
                session()->put(['title'=> 'KẾ HOẠCH SẢN XUẤT THÁNG']);
        
                return view('pages.plan.production.plan_list',[
                        'datas' => $datas
                        
                ]);
        }

        public function create_plan_list (Request $request) {
                       
                 DB::table('plan_list')->insert([
                        'name' => $request->name,
                        'month' => date('m'),
                        'type' => 1,
                        'send' => false,
                        'deparment_code'  => session('user')['production_code'],
                        'prepared_by' => session('user')['fullName'],
                        'created_at' => now(),
                ]);
                return redirect()->back()->with('success', "Tạo Mới $request->name Thành Công!");

        }


        public function open(Request  $request){
               
                $datas = DB::table('plan_master')
                ->select('plan_master.*', 
                        'finished_product_category.intermediate_code', 
                        'finished_product_category.finished_product_code', 
                        'product_name.name',
                        'market.name as market', 
                        'specification.name as specification', 
                        'finished_product_category.batch_qty',
                        'finished_product_category.unit_batch_qty',
                        'finished_product_category.deparment_code',
                        'source_material.name as source_material_name'
                        )
                ->where ('plan_list_id',$request->plan_list_id)->where('plan_master.active',1)
                ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', 'finished_product_category.id')
                ->leftJoin('source_material', 'plan_master.material_source_id', 'source_material.id')
                ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
                ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
                ->leftJoin('specification', 'finished_product_category.specification_id', 'specification.id') 
                ->orderBy('expected_date','asc')
                ->orderBy('level','asc')
                ->orderBy('batch','asc')
                ->get();

                $planMasterIds = $datas->pluck('id')->toArray();

                $historyCounts = DB::table('plan_master_history')
                        ->select('plan_master_id', DB::raw('COUNT(*) as total'))
                        ->whereIn('plan_master_id', $planMasterIds)
                        ->groupBy('plan_master_id')
                        ->pluck('total', 'plan_master_id')
                        ->toArray();
                $datas = $datas->map(function($item) use ($historyCounts) {
                        $item->history_count = $historyCounts[$item->id] ?? 0; // nếu không có history thì = 0
                        return $item;
                        });
               
                $finished_product_category = DB::table('finished_product_category')
                ->select('finished_product_category.*', 'product_name.name', 'market.name as market', 'specification.name as specification',)
                ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
                ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
                ->leftJoin('specification', 'finished_product_category.specification_id', 'specification.id')
                ->where ('finished_product_category.active',1)
                ->where ('finished_product_category.deparment_code',session ('user')['production_code'])
                ->orderBy('name','asc')
                ->get();
                
                $source_material_list = DB::table('source_material')
                ->select('source_material.*', 'product_name.name as product_name')
                ->leftJoin('intermediate_category', 'source_material.intermediate_code', 'intermediate_category.intermediate_code')
                ->leftJoin('product_name', 'intermediate_category.product_name_id', 'product_name.id')
                ->where ('source_material.active',1)->orderBy('source_material.name','asc')->get();

                $production  =  session('user')['production_name'];

                session()->put(['title'=> " $request->name - $production"]);
        
                return view('pages.plan.production.list',[
                        'datas' => $datas, 
                        'plan_list_id' => $request->plan_list_id,
                        'month' => $request->month, 
                        'production' => $request->production,
                        'finished_product_category' => $finished_product_category,
                        'source_material_list'=> $source_material_list,
                        'send'=> $request->send??1,
                        
                ]);
        }

        public function history(Request $request) {
                //dd ($request->all());
                $histories = DB::table('plan_master_history')
                ->select('plan_master_history.*', 
                        'finished_product_category.intermediate_code', 
                        'finished_product_category.finished_product_code', 
                        'product_name.name',
                        'market.name as market', 
                        'specification.name as specification', 
                        'finished_product_category.batch_qty',
                        'finished_product_category.unit_batch_qty',
                        'finished_product_category.deparment_code',
                        'source_material.name as source_material_name'
                        )
                ->where('plan_master_history.plan_master_id', $request->id)
                ->leftJoin('finished_product_category', 'plan_master_history.product_caterogy_id', 'finished_product_category.id')
                ->leftJoin('source_material', 'plan_master_history.material_source_id', 'source_material.id')
                ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
                ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
                ->leftJoin('specification', 'finished_product_category.specification_id', 'specification.id')
                ->orderBy('version','desc')->orderBy('expected_date','asc')->get();
                 return response()->json($histories);
        }

        public function source_material(Request $request) {
                //>where ('intermediate_code', $request->intermediate_code)
                $source_material_list = DB::table('source_material')
                ->select('source_material.*', 'product_name.name as product_name')
                ->leftJoin('intermediate_category', 'source_material.intermediate_code', 'intermediate_category.intermediate_code')
                ->leftJoin('product_name', 'intermediate_category.product_name_id', 'product_name.id')
                ->where ('source_material.active',1)
                ->where ('source_material.intermediate_code',$request->intermediate_code)
                ->orderBy('source_material.name','asc')->get();
                
                 return response()->json($source_material_list);
        }

       public function store(Request $request){
               //dd ($request->all());
        $validator = Validator::make($request->all(), [
                        'product_caterogy_id' => 'required',
                        'plan_list_id'   => 'required',
                        'batch' => 'required',
                        'expected_date' => 'required',
                        'level' => 'required',
                        'after_weigth_date' => 'required',
                        //'before_weigth_date' => 'required',
                        'after_parkaging_date' => 'required',
                        //'before_parkaging_date' => 'required',
                        'material_source_id' => 'required',
                       
                ], [
                        'product_caterogy_id' => 'Vui lòng chọn lại sản phẩm.',
                        'plan_list_id'   => 'Vui lòng chọn lại sản phẩm',
                        'batch' => 'Vui lòng nhập số lô',
                        'expected_date' => 'Vui lòng chọn ngày dự kiến KCS',
                        'level' => 'vui lòng chọn mức độ ưu tiên',
                        'after_weigth_date' => 'vui lòng chọn ngày có thể cân',
                        //'before_weigth_date' => 'vui lòng chọn ngày cân trước',
                        'after_parkaging_date' => 'vui lòng chọn ngày có thể đóng gói',
                        //'before_parkaging_date' => 'vui lòng chọn ngày có đóng gói trước',
                        'material_source_id' => 'vui lòng chọn nguồn nguyên liệu',
                ]);

                if ($validator->fails()) {
                        return redirect()->back()
                        ->withErrors($validator, 'create_Errors')
                        ->withInput();
                }
               
                $first_val_batch = $request->first_val_batch == "on" ? 1 : 0;
                $second_val_batch = $request->second_val_batch == "on" ? 1 : 0;
                $third_val_batch = $request->third_val_batch == "on" ? 1 : 0;
                $total =  $first_val_batch +  $second_val_batch + $third_val_batch;

                if ($first_val_batch == 1){
                        $current_val_batch = 1;
                }else if ($second_val_batch == 1){
                        $current_val_batch = 2;
                }else if ($third_val_batch == 1){
                         $current_val_batch = 3;                       
                }
                
                $code_val_part_0 = explode("_", $request->code_val_first)[0] ;

                // // Tạo số lô
                $batches = [];

                if ($request->format_batch_no == "on") {
                        $prefix = Str::substr($request->batch, -4);
                        $aa     = intval(Str::substr($request->batch, 0, Str::length($request->batch) - 4));
                        for ($i = 1; $i <= $request->number_of_batch; $i++) {
                                $charater_val = "";//($i <= $total) ? "V" : "";
                                $batches[] = sprintf("%02d", $aa) . $prefix . $charater_val;
                                $aa++;
                        }
                } else {
                        $prefix = Str::substr($request->batch, 0, 3);
                        $aa     = intval(Str::substr($request->batch, 3, 3));
                        for ($i = 1; $i <= $request->number_of_batch; $i++) {
                                 $charater_val = ($i <= $total) ? "V" : "";
                                $batches[] = $prefix . sprintf("%02d", $aa) . $charater_val;
                                $aa++;
                        }
                }
                

                $first_val_batch = $request->first_val_batch == "on" ? 1 : 0;
                $second_val_batch = $request->second_val_batch == "on" ? 1 : 0;
                $third_val_batch = $request->third_val_batch == "on" ? 1 : 0;

              
                $i = 1;
                foreach ($batches as  $batch) {
                        if ($i <= $total){
                                $code_val_part_1 = $current_val_batch - 1 + $i;
                        }
                        
                        //dd ($total, $current_val_batch, $code_val_part_1);
                        // Insert vào plan_master
                        $planMasterId = DB::table('plan_master')->insertGetId([
                                "product_caterogy_id" => $request->product_caterogy_id,
                                "plan_list_id" => $request->plan_list_id,
                                "batch" => $batch,
                                "expected_date" => $request->expected_date,
                                "level" => $request->level,
                                "is_val" => ($i <= $total) ? 1 : 0,
                                "code_val" => ($i <= $total) ? $code_val_part_0 . "_" . $code_val_part_1 : null,
                                "after_weigth_date" => $request->after_weigth_date,
                                //"before_weigth_date" => $request->before_weigth_date,
                                "after_parkaging_date" => $request->after_parkaging_date,
                               // "before_parkaging_date" => $request->before_parkaging_date,
                                "material_source_id" => $request->material_source_id,
                                "percent_parkaging" => 1,
                                "number_parkaging" => $request->max_number_of_unit,
                                "only_parkaging" => 0,
                                "note" => $request->note ?? "NA",
                                'deparment_code'=> session('user')['production_code'],
                                'prepared_by' => session('user')['fullName'],
                                'created_at' => now(),
                        ]);

                        // Cập nhật lại chính bản ghi đó
                        DB::table('plan_master')
                        ->where('id', $planMasterId)
                        ->update(['main_parkaging_id' => $planMasterId]);

                        // Insert vào plan_master_history
                        DB::table('plan_master_history')->insert([
                                "plan_master_id" => $planMasterId,
                                "plan_list_id" => $request->plan_list_id,
                                "product_caterogy_id" => $request->product_caterogy_id,
                                "batch" => $batch,
                                "expected_date" => $request->expected_date,
                                "level" => $request->level,
                                "is_val" => ($i <= $total) ? 1 : 0,
                                "after_weigth_date" => $request->after_weigth_date,
                                //"before_weigth_date" => $request->before_weigth_date,
                                "after_parkaging_date" => $request->after_parkaging_date,
                                //"before_parkaging_date" => $request->before_parkaging_date,
                                "material_source_id" => $request->material_source_id,
                                "percent_parkaging" => 1,
                                "number_parkaging" => $request->max_number_of_unit,
                                "only_parkaging" => 0,
                                "note" => $request->note ?? "NA",
                                'deparment_code'=> session('user')['production_code'],
                                'prepared_by' => session('user')['fullName'],
                                'created_at' => now(),
                                'updated_at' => now(),
                                "version" => 1,
                                "reason" => "Tạo Mới", // lần đầu tạo thì version = 1
                        ]);
                        $i++;
                }
                return redirect()->back()->with('success', 'Đã thêm thành công!');
        }

        public function store_source(Request $request){
               
                $validator = Validator::make($request->all(), [
                        'name' => 'required',  
                ], [
                        'name.required' => 'Vui lòng nhập nguồn nguyên liệu',
                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'create_source_Errors')->withInput();
                }

                // Update dữ liệu chính
                $id = DB::table('source_material')->insertGetId([
                        "intermediate_code" => $request->intermediate_code,
                        "name" => $request->name,
                        'prepared_by' => session('user')['fullName'],
                        'created_at' => now(),
                ]);

                return response()->json([
                        'id'   => $id,
                        'name' => $request->name
                ]);

        }
 
        public function update(Request $request){
                //dd ($request->all());
                $validator = Validator::make($request->all(), [
                       
                        'batch' => 'required',
                        'expected_date' => 'required',
                        'level' => 'required',
                        'after_weigth_date' => 'required',
                        //'before_weigth_date' => 'required',
                        'after_parkaging_date' => 'required',
                        //'before_parkaging_date' => 'required',
                        'material_source_id' => 'required',
                       
                ], [
                        
                        'batch' => 'Vui lòng nhập số lô',
                        'expected_date' => 'Vui lòng chọn ngày dự kiến KCS',
                        'level' => 'vui lòng chọn mức độ ưu tiên',
                        'after_weigth_date' => 'vui lòng chọn ngày có thể cân',
                        //'before_weigth_date' => 'vui lòng chọn ngày cân trước',
                        'after_parkaging_date' => 'vui lòng chọn ngày có thể đóng gói',
                        //'before_parkaging_date' => 'vui lòng chọn ngày có đóng gói trước',
                        'material_source_id' => 'vui lòng chọn nguồn nguyên liệu',
                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'update_Errors')->withInput();
                }

                $first_val_batch = $request->first_val_batch == "on" ? 1 : 0;
                $second_val_batch = $request->second_val_batch == "on" ? 1 : 0;
                $third_val_batch = $request->third_val_batch == "on" ? 1 : 0;

                $is_val = 0;
                $code_val = null;
                if ($first_val_batch == 1){
                        $code_val_part_0 = explode("_", $request->code_val_first)[0] ;
                        $is_val = 1;
                        $code_val =  $code_val_part_0."_1";
                }else if ($second_val_batch == 1){
                        $code_val_part_0 = explode("_", $request->code_val_first)[0] ;
                        $is_val = 1;
                        $code_val =  $code_val_part_0."_2";
                }else if ($third_val_batch == 1){
                        $code_val_part_0 = explode("_", $request->code_val_first)[0] ;
                        $is_val = 1;    
                        $code_val =  $code_val_part_0."_3";         
                }

                // Update dữ liệu chính
                DB::table('plan_master')->where('main_parkaging_id', $request->id)->update([
                        "batch" => $request->batch,
                        "expected_date" => $request->expected_date,
                        "level" => $request->level,
                        "is_val" => $is_val,
                        "code_val" => $code_val,
                        "after_weigth_date" => $request->after_weigth_date,
                        //"before_weigth_date" => $request->before_weigth_date,
                        "after_parkaging_date" => $request->after_parkaging_date,
                        //"before_parkaging_date" => $request->before_parkaging_date,
                        "material_source_id" => $request->material_source_id,
                        "note" => $request->note ?? "NA",
                        'prepared_by' => session('user')['fullName'],
                        'updated_at' => now(),
                ]);

                // Lấy dữ liệu gốc từ plan_master
                $plan = DB::table('plan_master')->where('id', $request->id)->first();
                
                // Tìm version cao nhất hiện tại trong history
                $lastVersion = DB::table('plan_master_history')
                        ->where('plan_master_id', $request->id)
                        ->max('version');

                $newVersion = $lastVersion ? $lastVersion + 1 : 1;
                //dd ($plan);
                // Insert lịch sử
                        DB::table('plan_master_history')->insert([
                        'plan_master_id' => $plan->id,
                        'plan_list_id' => $plan->plan_list_id,
                        'product_caterogy_id' => $plan->product_caterogy_id,
                        'version' => $newVersion,

                        'level' => $request->level,
                        'batch' => $request->batch,
                        'expected_date' => $request->expected_date,
                        'is_val' => $request->is_val == null ? 0 : 1,
                        'after_weigth_date' => $request->after_weigth_date,
                        //'before_weigth_date' => $request->before_weigth_date,
                        'after_parkaging_date' => $request->after_parkaging_date,
                        //'before_parkaging_date' => $request->before_parkaging_date,
                        'material_source_id' => $request->material_source_id,
                        'percent_parkaging' => $plan->percent_parkaging,
                        'only_parkaging' => $plan->only_parkaging,
                        "number_parkaging" => $plan->number_parkaging,
                        'note' => $request->note ?? "NA",
                        'reason' => $request->reason ?? "NA",
                        'deparment_code' => $plan->deparment_code,

                        'prepared_by' => session('user')['fullName'],
                        'created_at' => now(),
                        'updated_at' => now(),
                        ]);

                return redirect()->back()->with('success', 'Đã cập nhật thành công!');

        }

        public function splitting(Request $request){
                //dd ($request->all());
                $validator = Validator::make($request->all(), [
                        'batch' => 'required',
                        'expected_date' => 'required',
                        'level' => 'required',
                        'percent_packaging' => 'required',
                        'number_of_batch' => 'required',
                ], [
                        'batch.required' => 'Vui lòng nhập số lô',
                        'expected_date.required' => 'Vui lòng chọn ngày dự kiến KCS',
                        'level.required' => 'Vui lòng chọn mức độ ưu tiên',
                        'percent_packaging.required' => 'Vui lòng nhập số lượng đơn vị liều đóng gói',
                        'number_of_batch.required' => 'Vui lòng chọn số lượng lô',
                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'update_Errors')->withInput();
                }

                $mainPlanMaster = DB::table('plan_master')->where ('id', $request->id)->first();

                $planMasterId = DB::table('plan_master')->insertGetId([
                        "product_caterogy_id" => $mainPlanMaster->product_caterogy_id,
                        "plan_list_id" => $mainPlanMaster->plan_list_id,
                        "batch" => $mainPlanMaster->batch,
                        "expected_date" => $request->expected_date,
                        "level" => $request->level,
                        "is_val" => $mainPlanMaster->is_val,
                        "code_val" => $mainPlanMaster->code_val,
                        "after_weigth_date" => $mainPlanMaster->after_weigth_date,
                        "before_weigth_date" => $mainPlanMaster->before_weigth_date,
                        "after_parkaging_date" => $mainPlanMaster->after_parkaging_date,
                        "before_parkaging_date" => $mainPlanMaster->before_parkaging_date,
                        "material_source_id" => $mainPlanMaster->material_source_id,
                        "percent_parkaging" => round($request->number_of_unit/$request->max_number_of_unit,4),
                        "number_parkaging" => $request->number_of_unit,
                        "only_parkaging" => 1,
                        "note" => $request->note ?? "NA",
                        'deparment_code'=> session('user')['production_code'],
                        'prepared_by' => session('user')['fullName'],
                        'created_at' => now(),
                ]);

                DB::table('plan_master')
                        ->where('id', $planMasterId)
                        ->update(['main_parkaging_id' => $request->id]);

                DB::table('plan_master')
                        ->where('id', $request->id)
                        ->update([
                                'number_parkaging' => $mainPlanMaster->number_parkaging - $request->number_of_unit,
                                "percent_parkaging" => round(($mainPlanMaster->number_parkaging - $request->number_of_unit)/$request->max_number_of_unit,4),
                        ]);

                        // Insert vào plan_master_history
                DB::table('plan_master_history')->insert([
                        "plan_master_id" => $planMasterId,
                        "plan_list_id" => $mainPlanMaster->plan_list_id,
                        "product_caterogy_id" => $mainPlanMaster->product_caterogy_id,
                        "batch" => $mainPlanMaster->batch,
                        "expected_date" => $request->expected_date,
                        "level" => $request->level,
                        "is_val" => $mainPlanMaster->is_val,
                        "after_weigth_date" => $mainPlanMaster->after_weigth_date,
                        "before_weigth_date" => $mainPlanMaster->before_weigth_date,
                        "after_parkaging_date" => $mainPlanMaster->after_parkaging_date,
                        "before_parkaging_date" => $mainPlanMaster->before_parkaging_date,
                        "material_source_id" => $mainPlanMaster->material_source_id,
                        "percent_parkaging" => round($request->number_of_unit/$request->max_number_of_unit,2),
                        "number_parkaging" =>  $request->number_of_unit,
                        "only_parkaging" => 1,
                        "note" => $request->note ?? "NA",
                        'deparment_code'=> session('user')['production_code'],
                        'prepared_by' => session('user')['fullName'],
                        'created_at' => now(),
                        'updated_at' => now(),
                        "version" => 1,
                        "reason" => "Chia Lô Đóng Gói", // lần đầu tạo thì version = 1
                ]);

                return redirect()->back()->with('success', 'Đã cập nhật thành công!');

        }

        public function splittingUpdate(Request $request){
                //dd ($request->all());
                $validator = Validator::make($request->all(), [
                        'batch' => 'required',
                        'expected_date' => 'required',
                        'level' => 'required',
                        'percent_packaging' => 'required',
                        'number_of_batch' => 'required',
                ], [
                        'batch.required' => 'Vui lòng nhập số lô',
                        'expected_date.required' => 'Vui lòng chọn ngày dự kiến KCS',
                        'level.required' => 'Vui lòng chọn mức độ ưu tiên',
                        'percent_packaging.required' => 'Vui lòng nhập số lượng đơn vị liều đóng gói',
                        'number_of_batch.required' => 'Vui lòng chọn số lượng lô',
                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'update_Errors')->withInput();
                }

                $mainPlanMaster = DB::table('plan_master')->where ('id', $request->id)->first();

                DB::table('plan_master')->where ('id',$request->id )->update([
                        "batch" => $mainPlanMaster->batch,
                        "expected_date" => $request->expected_date,
                        "level" => $request->level,
                        "percent_parkaging" => round($request->number_of_unit/$request->max_number_of_unit,4),
                        "number_parkaging" => $request->number_of_unit,
                        "note" => $request->note ?? "NA",
                        'prepared_by' => session('user')['fullName'],
                        'created_at' => now(),
                ]);

                $sum_number_parkaging =  DB::table('plan_master')->where('main_parkaging_id', $mainPlanMaster->main_parkaging_id)->where('only_parkaging',1)->sum('number_parkaging');
                //dd ($request->all());
                DB::table('plan_master')
                        ->where('id', $mainPlanMaster->main_parkaging_id)
                        ->update([
                                'number_parkaging' => $request->max_number_of_unit - $sum_number_parkaging,
                                "percent_parkaging" => round(($request->max_number_of_unit - $sum_number_parkaging)/$request->max_number_of_unit,4),
                        ]);

                        // Insert vào plan_master_history
                DB::table('plan_master_history')->insert([
                        "plan_master_id" => $mainPlanMaster->id,
                        "plan_list_id" => $mainPlanMaster->plan_list_id,
                        "product_caterogy_id" => $mainPlanMaster->product_caterogy_id,
                        "batch" => $mainPlanMaster->batch,
                        "expected_date" => $request->expected_date,
                        "level" => $request->level,
                        "is_val" => $mainPlanMaster->is_val,
                        "after_weigth_date" => $mainPlanMaster->after_weigth_date,
                        "before_weigth_date" => $mainPlanMaster->before_weigth_date,
                        "after_parkaging_date" => $mainPlanMaster->after_parkaging_date,
                        "before_parkaging_date" => $mainPlanMaster->before_parkaging_date,
                        "material_source_id" => $mainPlanMaster->material_source_id,
                        "percent_parkaging" => round($request->number_of_unit/$request->max_number_of_unit,2),
                        "number_parkaging" =>  $request->number_of_unit,
                        "only_parkaging" => 1,
                        "note" => $request->note ?? "NA",
                        'deparment_code'=> session('user')['production_code'],
                        'prepared_by' => session('user')['fullName'],
                        'created_at' => now(),
                        'updated_at' => now(),
                        "version" => 1,
                        "reason" => "Cập Nhật Chia Lô Đóng Gói", // lần đầu tạo thì version = 1
                ]);

                return redirect()->back()->with('success', 'Đã cập nhật thành công!');

        }


        public function deActive(Request $request){
               
                $reason = $request->deactive_reason;
                $updatesql = [
                        'prepared_by' => session('user')['fullName'],
                        'updated_at' => now(),
                ];

                if ($request->type === 'delete') {
                        $updatesql['active'] = 0;
                         $active_stage_plan = 0;
                } elseif ($request->type === 'cancel') {
                        $updatesql['cancel'] = 1;
                        $active_stage_plan = 0;
                } elseif ($request->type === 'restore') {
                        $updatesql['cancel'] = 0;
                        $active_stage_plan = 1;
                }
                if ($request->only_parkaging == 1){

                        $main_parkaging_id =  DB::table('plan_master')->where('id', $request->id)->value('main_parkaging_id');
                        $max_number_parkaging =  DB::table('plan_master')->where('main_parkaging_id', $main_parkaging_id)->sum('number_parkaging');
                        DB::table('plan_master')->where('id', $request->id)->update($updatesql);
                        $sum_number_parkaging =  DB::table('plan_master')->where('main_parkaging_id', $request->id)->where('only_parkaging',1)->sum('number_parkaging');
                        DB::table('plan_master')
                        ->where('id', $main_parkaging_id)
                        ->update([
                                'number_parkaging' => $max_number_parkaging - $sum_number_parkaging,
                                "percent_parkaging" => round(($max_number_parkaging - $sum_number_parkaging)/$max_number_parkaging,4),
                        ]);

                }else{
                        DB::table('plan_master')->where('main_parkaging_id', $request->id)->update($updatesql);
                }

                $latest = DB::table('plan_master_history')
                ->where('plan_master_id', $request->id)
                ->orderByDesc('version')
                ->first();

                if ($latest) {
                        DB::table('plan_master_history')
                        ->where('id', $latest->id)
                        ->update(['reason' => $reason]);
                }

                DB::table('stage_plan')->where('plan_master_id', $request->id)->update([
                        'active' => $active_stage_plan
                ]);

                return redirect()->back()->with('success', 'Cập nhật trạng thái thành công!');
        }

        
        public function send(Request $request){
                
                $exists = DB::table('plan_master')->where('plan_master.plan_list_id', $request->plan_list_id)->exists();

                if ($exists){return;}

                // Phần 1: Các plan không chỉ đóng gói (only_parkaging = 0)
                $plans_main = DB::table('plan_master')
                ->where('plan_master.plan_list_id', $request->plan_list_id)
                ->where('plan_master.active', 1)
                ->where('plan_master.cancel', 0)
                ->where('plan_master.only_parkaging', 0)
                ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', '=', 'finished_product_category.id')
                ->leftJoin('intermediate_category', 'intermediate_category.intermediate_code', '=', 'finished_product_category.intermediate_code')
                ->select(
                        'plan_master.id',
                        'plan_master.plan_list_id',
                        'plan_master.product_caterogy_id',
                        'plan_master.expected_date',
                        'plan_master.level',
                        'plan_master.batch',
                        'plan_master.only_parkaging',
                        'plan_master.percent_parkaging',
                        'plan_master.main_parkaging_id',
                        'intermediate_category.weight_1',
                        'intermediate_category.weight_2',
                        'intermediate_category.prepering',
                        'intermediate_category.blending',
                        'intermediate_category.forming',
                        'intermediate_category.coating',
                        'intermediate_category.batch_size',
                        'finished_product_category.primary_parkaging',
                        'finished_product_category.intermediate_code',
                        'finished_product_category.finished_product_code',
                        'finished_product_category.batch_qty'
                )
                ->orderBy('expected_date', 'asc')
                ->orderBy('level', 'asc')
                ->orderBy('batch', 'asc')
                ->get();
                
                // Phần 2: Các plan chỉ đóng gói (only_parkaging = 1)
                $plans_packaging = DB::table('plan_master')
                ->where('plan_master.plan_list_id', $request->plan_list_id)
                ->where('plan_master.active', 1)
                ->where('plan_master.cancel', 0)
                ->where('plan_master.only_parkaging', 1)
                ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', '=', 'finished_product_category.id')
                ->leftJoin('intermediate_category', 'intermediate_category.intermediate_code', '=', 'finished_product_category.intermediate_code')
                ->select(
                        'plan_master.id',
                        'plan_master.plan_list_id',
                        'plan_master.product_caterogy_id',
                        'plan_master.expected_date',
                        'plan_master.level',
                        'plan_master.batch',
                        'plan_master.only_parkaging',
                        'plan_master.percent_parkaging',
                        'plan_master.main_parkaging_id',
                        'intermediate_category.weight_1',
                        'intermediate_category.weight_2',
                        'intermediate_category.prepering',
                        'intermediate_category.blending',
                        'intermediate_category.forming',
                        'intermediate_category.coating',
                        'intermediate_category.batch_size',
                        'finished_product_category.primary_parkaging',
                        'finished_product_category.intermediate_code',
                        'finished_product_category.finished_product_code',
                        'finished_product_category.batch_qty'
                )
                ->orderBy('expected_date', 'asc')
                ->orderBy('level', 'asc')
                ->orderBy('batch', 'asc')
                ->get();


                $stages = ['weight_1','weight_2', 'prepering', 'blending', 'forming', 'coating', 'primary_parkaging'];
                $stage_code = [
                        'weight_1' => 1,
                        'weight_2' => 2,
                        'prepering' => 3,
                        'blending'=> 4,
                        'forming'=> 5,
                        'coating'=> 6,
                        'primary_parkaging'=> 7,
                ];
                
                $dataToInsert = [];

                foreach ($plans_main as $plan) {
                        $stageList = [];

                        // Vòng 1: gom các stage có tồn tại cho plan này
                        foreach ($stages as $index => $stage) {
                                if ($plan->$stage) {
                                $stageList[] = [
                                        'code'       => $plan->id . "_" . $stage_code[$stage],
                                        'stage_code' => $stage_code[$stage],
                                        'order_by'   => $index,
                                ];
                                }
                        }
                        
                        
                        // Vòng 2: set predecessor và nextcessor
                        foreach ($stageList as $i => $stageItem) {
                                $prevCode = null;
                                $nextCode = null;

                                // ✅ set prevCode
                                if ($i > 0) {
                                        $prevItem = $stageList[$i - 1];
                                        // nếu stage hiện tại >=3 và prev là 2 thì bỏ qua, tìm lại stage_code = 1
                                        if ($stageItem['stage_code'] >= 3 && $prevItem['stage_code'] == 2) {
                                                $prevCode = collect($stageList)->firstWhere('stage_code', 1)['code'] ?? null;
                                        } else {
                                                $prevCode = $prevItem['code'];
                                        }
                                }

                                // ✅ set nextCode
                                if ($i < count($stageList) - 1) {
                                        $nextItem = $stageList[$i + 1];
                                        // nếu stage hiện tại = 1 và next là 2 thì bỏ qua, tìm stage_code >= 3
                                        if ($stageItem['stage_code'] == 1 && $nextItem['stage_code'] == 2) {
                                        $nextCode = collect($stageList)
                                                ->first(fn($s) => $s['stage_code'] >= 3)['code'] ?? null;
                                        } else {
                                        $nextCode = $nextItem['code'];
                                        }
                                }


                                $tank = DB::table('quota')
                                        ->select('tank', 'keep_dry')
                                        ->when($stageItem['stage_code'] != 7, function ($q) use ($plan, $stageItem) {
                                                return $q->where('intermediate_code', $plan->intermediate_code)
                                                        ->where('stage_code', $stageItem['stage_code']);
                                        })
                                        ->when($stageItem['stage_code'] == 7, function ($q) use ($plan, $stageItem) {
                                                return $q->where('finished_product_code', $plan->finished_product_code)
                                                        ->where('stage_code', $stageItem['stage_code']);
                                        })
                                        ->first();
                             
                                
                                        $dataToInsert[] = [
                                        'plan_list_id'        => $plan->plan_list_id,
                                        'plan_master_id'      => $plan->id,
                                        'product_caterogy_id' => $plan->product_caterogy_id,
                                        'stage_code'          => $stageItem['stage_code'],
                                        'order_by'            => $stageItem['order_by'],
                                        'code'                => $stageItem['code'],
                                        'predecessor_code'    => $prevCode,
                                        'nextcessor_code'     => $nextCode,
                                        'tank'                => $tank->tank??0,
                                        'keep_dry'            => $tank->keep_dry??0,
                                        'deparment_code'      => session('user')['production_code'],
                                        'created_date'        => now(),
                                        'Theoretical_yields' => $stageItem['stage_code'] <= 6 ? $plan->batch_size:$plan->batch_qty,
                                        ];

                                if ($plan->percent_parkaging  < 1 && $stageItem['stage_code'] == 7){
                                        $plan_packagings = $plans_packaging->where ('main_parkaging_id',$plan->id);
                                        foreach ($plan_packagings as $plan_packaging) {
                                                $dataToInsert[] = [
                                                        'plan_list_id'        => $plan_packaging->plan_list_id,
                                                        'plan_master_id'      => $plan_packaging->id,
                                                        'product_caterogy_id' => $plan_packaging->product_caterogy_id,
                                                        'stage_code'          => $stageItem['stage_code'],
                                                        'order_by'            => $stageItem['order_by'],
                                                        'code'                => $stageItem['code'],
                                                        'predecessor_code'    => $prevCode,
                                                        'nextcessor_code'     => $nextCode,
                                                        'tank'                => $tank->tank??0,
                                                        'keep_dry'            => $tank->keep_dry??0,
                                                        'deparment_code'      => session('user')['production_code'],
                                                        'created_date'        => now(),
                                                        'Theoretical_yields' => $stageItem['stage_code'] <= 6 ? $plan_packaging->batch_size:$plan_packaging->batch_qty,
                                                ];
                                        }

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
                ->where ('type',1)
                ->where ('deparment_code',session('user')['production_code'])
                ->orderBy('created_at','desc')->get();

                 session()->put(['title'=> 'Kế Hoạch Sản Xuất Tháng']);
                 return view('pages.plan.production.plan_list',['datas' => $datas ]);
        }

        public function updateInput(Request $request){
                DB::table('plan_master')
                        ->where('id', $request->id)
                        ->update([
                                $request->name => $request->updateValue
                ]);
                return response()->json(['success' => true]);
        }

        public function first_batch(Request $request) {
                ob_clean();
                $datas = DB::table('plan_master')
                ->select('plan_master.*', 
                        'finished_product_category.intermediate_code', 
                        'finished_product_category.finished_product_code', 
                        'product_name.name',
                        'market.name as market', 
                        'specification.name as specification', 
                        'finished_product_category.batch_qty',
                        'finished_product_category.unit_batch_qty',
                        'finished_product_category.deparment_code',
                        'source_material.name as source_material_name'
                        )
                ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', 'finished_product_category.id')
                ->leftJoin('source_material', 'plan_master.material_source_id', 'source_material.id')
                ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
                ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
                ->leftJoin('specification', 'finished_product_category.specification_id', 'specification.id') 
                ->where('plan_master.active',1)
                ->where ('is_val',1)
                ->where ('plan_master.active',1)
                //->whereRaw("SUBSTRING_INDEX(plan_master.code_val, '_', -1) = '1'") 
                ->where ('finished_product_category.intermediate_code',$request->intermediate_code)
                ->orderBy('id','desc')
                ->get();

                Log::info('first_batch', [
                        'datas' => $datas
                ]);
                 return response()->json($datas);    
        }

          public function get_last_id(Request $request) {
                ob_clean();
                $last = DB::table($request->table)->latest('id')->value('id');
                return response()->json([
                        'last_id' => $last ?? 0
                ]);
          }


}
