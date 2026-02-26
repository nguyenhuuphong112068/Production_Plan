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
               
               // 1. Lấy plan_list
                $datas = DB::table('plan_list')
                        ->where('active', 1)
                        ->where('deparment_code', session('user')['production_code'])
                        ->where('type', 1)
                        ->orderBy('id', 'desc')
                ->get();



                // 2. Lấy tổng batch theo plan_list_id
                $total_batch_qtys = DB::table('plan_master as pm')
                        ->join('finished_product_category as fpc', 'pm.product_caterogy_id', '=', 'fpc.id')
                        ->where('pm.active', 1)
                        ->where('pm.cancel', 0)
                        ->where('pm.only_parkaging', 0)
                        ->where('fpc.active', 1)
                        ->where('pm.deparment_code', session('user')['production_code'])
                        ->groupBy('pm.plan_list_id')
                        ->select(
                                'pm.plan_list_id',
                                DB::raw('SUM(fpc.batch_qty) as total_batch_qty')
                        )
                        ->get()
                        ->keyBy('plan_list_id');   // 🔥 KEY THEO plan_list_id

                        // 3. Merge vào plan_list
                        $datas = $datas->map(function ($item) use ($total_batch_qtys) {
                        $item->total_batch_qty = $total_batch_qtys[$item->id]->total_batch_qty ?? 0;
                        return $item;
                });

                $batch_status = DB::table('plan_master as pm')
                        ->join('stage_plan as sp', 'sp.plan_master_id', '=', 'pm.id')
                        ->leftJoin('finished_product_category as fc', 'pm.product_caterogy_id', '=', 'fc.id')
                        ->where('pm.active', 1)
                        ->where('pm.only_parkaging', 0)
                        ->where('pm.deparment_code', session('user')['production_code'])
                        ->groupBy('pm.plan_list_id', 'pm.id')
                        ->select(
                                'pm.plan_list_id',
                                'fc.batch_qty',
                        
                        DB::raw("
                        CASE
                                WHEN 
                                SUM(CASE WHEN sp.active = 0 THEN 1 ELSE 0 END) = 0
                                AND
                                SUM(CASE WHEN sp.finished = 1 THEN 1 ELSE 0 END) >= 1
                                THEN 1 ELSE 0
                        END AS da_lam
                        "),

                        DB::raw("
                        CASE
                                WHEN 
                                SUM(CASE WHEN sp.active = 0 THEN 1 ELSE 0 END) = 0
                                AND
                                SUM(CASE WHEN sp.finished = 1 THEN 1 ELSE 0 END) = 0
                                THEN 1 ELSE 0
                        END AS chua_lam
                        "),

                        DB::raw("
                        CASE
                                WHEN 
                                SUM(CASE WHEN pm.cancel = 0  THEN 0 ELSE 1 END) >= 1
                                THEN 1 ELSE 0
                        END AS huy
                        ")
                )
                ->groupBy('pm.plan_list_id', 'pm.id', 'fc.batch_qty')
                ->get();
              
         

                
              
                $batch_summary = $batch_status
                        ->groupBy('plan_list_id')
                        ->map(function ($rows) {
                                $so_lo_chua_lam = $rows->where('chua_lam', 1);
                                return (object)[
                                'tong_lo'        => $rows->count(),       // ✅ TỔNG LÔ
                                'so_lo_da_lam'   => $rows->sum('da_lam'),
                                'so_lo_chua_lam' => $rows->sum('chua_lam'),
                                'so_lo_huy'      => $rows->sum('huy'),
                                'batch_qty_pending' => $so_lo_chua_lam->sum('batch_qty'),
                                ];
                });

               // dd ($batch_summary);


                $datas = $datas->map(function ($item) use ($total_batch_qtys, $batch_summary) {

                // Tổng batch
                $item->total_batch_qty =
                        $total_batch_qtys[$item->id]->total_batch_qty ?? 0;

                // Thống kê lô
                $item->tong_lo =
                        $batch_summary[$item->id]->tong_lo ?? 0;

                $item->so_lo_da_lam =
                        $batch_summary[$item->id]->so_lo_da_lam ?? 0;

                $item->so_lo_chua_lam =
                        $batch_summary[$item->id]->so_lo_chua_lam ?? 0;

                $item->so_lo_huy =
                        $batch_summary[$item->id]->so_lo_huy ?? 0;

                return $item;
                });

                $pending_plan = (object)[
                        'id' => -1,
                        'deparment_code' => session('user')['production_code'],
                        'prepared_by' => "NA",
                        'created_at' => now(),
                        'send' => 1,
                        'send_by' => 'NA',
                        'send_date' => now(),
                        'month' => 'NA',

                        'name' => 'KẾ HOẠCH CHƯA THỰC HIỆN',
                        'total_batch_qty' => 0,
                        'tong_lo' => 0,
                        'so_lo_da_lam' => 0,
                        'so_lo_chua_lam' => 0,
                        'so_lo_huy' => 0,
                ];

                foreach ($datas as $item) {
                        if ($item->so_lo_chua_lam > 0) {
                                $pending_plan->total_batch_qty += $batch_summary[$item->id]->batch_qty_pending ?? 0;
                                $pending_plan->tong_lo += $item->so_lo_chua_lam;
                                $pending_plan->so_lo_chua_lam += $item->so_lo_chua_lam;
                        }
                }

                // Chỉ thêm nếu có dữ liệu
                if ($pending_plan->so_lo_chua_lam > 0) {
                        $datas->prepend($pending_plan);
                }

               // dd ($datas, $total_batch_qtys);

                session()->put(['title'=> 'KẾ HOẠCH SẢN XUẤT THÁNG']);
        
                return view('pages.plan.production.plan_list',[
                        'datas' => $datas,
                        //'total_batch_qtys' => $total_batch_qtys
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
              
                $maxStageFinished = DB::table('stage_plan')
                ->where('stage_plan.plan_list_id', $request->plan_list_id)
                ->where('finished', 1)
                ->select(
                        'plan_master_id',
                        DB::raw('MAX(stage_code) as max_stage_code')
                )
                ->groupBy('plan_master_id');
  
                $datas = DB::table('plan_master')
                        ->select(
                                'plan_master.*',
                                'finished_product_category.intermediate_code',
                                'finished_product_category.finished_product_code',
                                'finished_product_category.IsHypothesis',
                                DB::raw('fp_name.name AS finished_product_name'),
                                DB::raw('im_name.name AS intermediate_product_name'),
                                'market.name as market',
                                'specification.name as specification',
                                'finished_product_category.batch_qty',
                                'finished_product_category.unit_batch_qty',
                                'finished_product_category.deparment_code',
                                'source_material.name as source_material_name',

                                DB::raw("
                                CASE
                                        WHEN plan_master.cancel = 1 THEN 'Hủy'
                                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 1 THEN 'Đã Cân'
                                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 3 THEN 'Đã Pha chế'
                                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 4 THEN 'Đã THT'
                                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 5 THEN 'Đã định hình'
                                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 6 THEN 'Đã Bao phim'
                                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 7 THEN 'Hoàn Tất ĐG'
                                        ELSE 'Chưa làm'
                                        END AS status
                                ")
                        )
                        ->whereIn('plan_master.plan_list_id', DB::table('plan_list')->where('deparment_code', session('user')['production_code'])->pluck('id'))
                        ->where('plan_master.active', 1)
                        //->where('plan_master.only_parkaging', 0)
                        ->when($request->plan_list_id < 0,
                                function ($q) {
                                        return $q->where('plan_master.weighed', 0) 
                                                ->where('plan_master.cancel', 0) 
                                        ;
                                },
                                function ($q) use ($request) {
                                        return $q->where('plan_master.plan_list_id', $request->plan_list_id);
                                }
                        )
                        ->leftJoin('finished_product_category','plan_master.product_caterogy_id','=','finished_product_category.id')
                        ->leftJoin('intermediate_category','finished_product_category.intermediate_code','=','intermediate_category.intermediate_code')
                        ->leftJoin('source_material', 'plan_master.material_source_id','=','source_material.id')
                        ->leftJoin('product_name as fp_name','finished_product_category.product_name_id','=','fp_name.id')
                        ->leftJoin('product_name as im_name','intermediate_category.product_name_id','=','im_name.id')
                        ->leftJoin('market', 'finished_product_category.market_id', '=', 'market.id')
                        ->leftJoin('specification', 'finished_product_category.specification_id', '=', 'specification.id')
                        ->leftJoinSub($maxStageFinished, 'sp_max', function ($join) {
                                $join->on('plan_master.id', '=', 'sp_max.plan_master_id');
                        })
                        ->leftJoin('stage_plan', function ($join) {
                                $join->on('plan_master.id', '=', 'stage_plan.plan_master_id')
                                ->on('stage_plan.stage_code', '=', 'sp_max.max_stage_code');
                        })
                        ->orderBy('expected_date', 'asc')
                        ->orderBy('level', 'asc')
                        ->orderBy('batch', 'asc')
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
                        ->select('finished_product_category.*', 
                                'product_name.name', 
                                'market.name as market', 
                                'specification.name as specification',
                                'intermediate_category.id as intermediate_caterogy_id'
                                )
                        ->leftJoin('intermediate_category', 'finished_product_category.intermediate_code', 'intermediate_category.intermediate_code')
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
                $plan_list_id_title =  DB::table('plan_list')->where('deparment_code', session('user')['production_code'])->pluck('name','id');
               
                session()->put(['title'=> " $request->name - $production"]);

        
                return view('pages.plan.production.list',[
                        'datas' => $datas, 
                        'plan_list_id' => $request->plan_list_id,
                        'month' => $request->month, 
                        'production' => $request->production,
                        'finished_product_category' => $finished_product_category,
                        'source_material_list'=> $source_material_list,
                        'send'=> $request->send??1,
                        'plan_list_id_title' => $plan_list_id_title
                        
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
                try {
                $validator = Validator::make($request->all(), [
                        'product_caterogy_id' => 'required',
                        'plan_list_id'   => 'required',
                        'batch' => 'required',
                        'expected_date' => 'required',
                        'level' => 'required',
                        //'material_source_id' => 'required',
                       
                ], [
                        'product_caterogy_id' => 'Vui lòng chọn lại sản phẩm.',
                        'plan_list_id'   => 'Vui lòng chọn lại sản phẩm',
                        'batch' => 'Vui lòng nhập số lô',
                        'expected_date' => 'Vui lòng chọn ngày dự kiến KCS',
                        'level' => 'vui lòng chọn mức độ ưu tiên',
                        //'material_source_id' => 'vui lòng chọn nguồn nguyên liệu',
                ]);

        
                if ($validator->fails()) {
                        return redirect()->back()
                        ->withErrors($validator, 'createErrors')
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
                                "responsed_date" => $request->expected_date,
                                "level" => $request->level,
                                "is_val" => ($i <= $total) ? 1 : 0,
                                "code_val" => ($i <= $total) ? $code_val_part_0 . "_" . $code_val_part_1 : null,

                                "after_weigth_date" => $request->after_weigth_date,
                                "after_parkaging_date" => $request->after_parkaging_date,

                                "allow_weight_before_date" => $request->allow_weight_before_date,
                                "expired_material_date" => $request->expired_material_date,
                                "preperation_before_date" => $request->preperation_before_date,
                                "blending_before_date" => $request->blending_before_date,
                                "coating_before_date" => $request->coating_before_date,

                                "parkaging_before_date" => $request->parkaging_before_date,
                                "expired_packing_date" => $request->expired_packing_date,

                                //"material_source_id" => $request->material_source_id,
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

                        $insertData = [];

                        $materials = $request->input('materials', []);
          
                        foreach ($materials as $code => $item) {

                                $insertData[] = [
                                        
                                        'plan_master_id'          => $planMasterId,
                                        'material_packaging_code' => (string) $code,
                                        'material_packaging_type' => 0,
                                        'Revno'                   => $item['Revno'],
                                        'qty'                     => (float) $item['qty'],
                                        'unit_bom'                => $item['uom'],
                                        'MaterialName'            => $item['MaterialName'],
                                        'created_at'              => now(),
                                        'created_by'              =>session ('user')['fullName'],
                                        'active'                  => $item['active'],
                                ];
                        }

                        $packagings = $request->input('packagings', []);

                        foreach ($packagings as $code => $item) {
               
                                $insertData[] = [
                                        
                                        'plan_master_id'          => $planMasterId,
                                        'material_packaging_code' => (string) $code,
                                        'material_packaging_type' => 1,
                                        'Revno'                   => $item['Revno'],
                                        'qty'                     => (float) $item['qty'],
                                        'unit_bom'                => $item['uom'],
                                        'MaterialName'            => $item['MaterialName'],
                                        'created_at'              => now(),
                                        'created_by'              =>session ('user')['fullName'],
                                        'active'                  => $item['active'],
                                        
                                ];
                        }
                        
                       
                        if (!empty($insertData)) {
                                DB::table('plan_master_materials')->upsert(
                                $insertData,
                                ['plan_master_id', 'material_packaging_code', 'material_packaging_type'],
                                ['qty', 'unit_bom', 'active', 'Revno']
                                );
                        }

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
                                "after_parkaging_date" => $request->after_parkaging_date,

                                "allow_weight_before_date" => $request->allow_weight_before_date,
                                "expired_material_date" => $request->expired_material_date,
                                "preperation_before_date" => $request->preperation_before_date,
                                "blending_before_date" => $request->blending_before_date,
                                "coating_before_date" => $request->coating_before_date,

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

                } catch (\Throwable $e) {
                        Log::error('Lỗi store plan_master', [
                                'message' => $e->getMessage(),
                                'file'    => $e->getFile(),
                                'line'    => $e->getLine(),
                                'request' => $request->all(),
                                'user'    => session('user') ?? null,
                        ]);
                        return redirect()->back()
                        ->with('error', 'Có lỗi xảy ra, vui lòng kiểm tra log!');
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
               // dd ($request->all());
                $validator = Validator::make($request->all(), [
                       
                        'batch' => 'required',
                        'expected_date' => 'required',
                        'level' => 'required',
                
                       
                       // 'material_source_id' => 'required',
                       
                ], [
                        
                        'batch' => 'Vui lòng nhập số lô',
                        'expected_date' => 'Vui lòng chọn ngày dự kiến KCS',
                        'level' => 'vui lòng chọn mức độ ưu tiên',
                        
                        
                        //'material_source_id' => 'vui lòng chọn nguồn nguyên liệu',
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
                       
                        "after_parkaging_date" => $request->after_parkaging_date,
                        
                        "material_source_id" => $request->material_source_id,
                        "note" => $request->note ?? "NA",
                        'prepared_by' => session('user')['fullName'],
                        'updated_at' => now(),
                ]);

                // Lấy dữ liệu gốc từ plan_master
                $plan = DB::table('plan_master')->where('id', $request->id)->first();


                //  update recipe
                $allItems = array_merge(
                        $request->input('materials', []),
                        $request->input('packagings', [])
                );
                //dd ($allItems);
                foreach ($allItems as $item) {
                        DB::table('plan_master_materials')
                                ->where('id', $item['id'])
                                ->update([
                                        'active' => $item['active'],
                                        'updated_at' => now(),
                                        'created_by' =>session ('user')['fullName']
                                ]);
                        
                }

                
                // Tìm version cao nhất hiện tại trong history
                $lastVersion = DB::table('plan_master_history')
                        ->where('plan_master_id', $request->id)
                        ->max('version');

                $newVersion = $lastVersion ? $lastVersion + 1 : 1;
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
                      
                        'after_parkaging_date' => $request->after_parkaging_date,
                      
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

               

                $validator = Validator::make($request->all(), [
                        //'batch' => 'required',
                        'expected_date' => 'required',
                        'level' => 'required',
                        'percent_packaging' => 'required',
                        'number_of_unit' => 'required',
                ], [
                        //'batch.required' => 'Vui lòng nhập số lô',
                        'expected_date.required' => 'Vui lòng chọn ngày dự kiến KCS',
                        'level.required' => 'Vui lòng chọn mức độ ưu tiên',
                        'percent_packaging.required' => 'Vui lòng nhập số lượng đơn vị liều đóng gói',
                        'number_of_unit.required' => 'Vui lòng chọn số lượng đóng gói',
                ]);
                

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'update_Errors')->withInput();
                }

               

                $mainPlanMaster = DB::table('plan_master')->where ('id', $request->id)->first();
                $main_intermediate_code = DB::table('finished_product_category')->where ('id', $mainPlanMaster->product_caterogy_id)->value('intermediate_code');
                 
               
                if ($request->intermediate_code != $main_intermediate_code) {
                        $error = ['intermediate_code' => 'Mã bán thành phẩm không khớp với sản phẩm chính.'];
                        return redirect()->back()->withErrors($error, 'update_Errors')->withInput();
                }

                $planMasterId = DB::table('plan_master')->insertGetId([
                        "product_caterogy_id" => $request->product_caterogy_id,
                        "plan_list_id" => $mainPlanMaster->plan_list_id,
                        "batch" => $mainPlanMaster->batch,
                        "expected_date" => $request->expected_date,
                        "level" => $request->level,
                        "is_val" => $mainPlanMaster->is_val,
                        "code_val" => $mainPlanMaster->code_val,
                        "after_weigth_date" => $mainPlanMaster->after_weigth_date,
                        
                        "after_parkaging_date" => $mainPlanMaster->after_parkaging_date,
                        
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
                       
                        "after_parkaging_date" => $mainPlanMaster->after_parkaging_date,
                        
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
               
                $validator = Validator::make($request->all(), [
                        //'batch' => 'required',
                        'expected_date' => 'required',
                        'level' => 'required',
                        'percent_packaging' => 'required',
                        'number_of_unit' => 'required',
                ], [
                        //'batch.required' => 'Vui lòng nhập số lô',
                        'expected_date.required' => 'Vui lòng chọn ngày dự kiến KCS',
                        'level.required' => 'Vui lòng chọn mức độ ưu tiên',
                        'percent_packaging.required' => 'Vui lòng nhập số lượng đơn vị liều đóng gói',
                        'number_of_unit.required' => 'Vui lòng chọn số lượng đóng gói',
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

                $sum_number_parkaging =  DB::table('plan_master')->where('active', 1)->where('main_parkaging_id', $mainPlanMaster->main_parkaging_id)->where('only_parkaging',1)->sum('number_parkaging');
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
                        
                        "after_parkaging_date" => $mainPlanMaster->after_parkaging_date,
                       
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
                        
                        $max_number_parkaging =  DB::table('plan_master')->where('active', 1)->where('main_parkaging_id', $main_parkaging_id)->sum('number_parkaging');
                        
                        DB::table('plan_master')->where('id', $request->id)->update($updatesql);


                        $sum_number_parkaging =  DB::table('plan_master')->where('active', 1)->where('main_parkaging_id', $main_parkaging_id)->where('only_parkaging',1)->sum('number_parkaging');
                        
          
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
                       
                $exists = DB::table('stage_plan')->where('plan_list_id', $request->plan_list_id)->exists();
                if ($exists){
                        return redirect()->route('pages.plan.production.list');
                
                }

                // Phần 1: Các plan không chỉ đóng gói (only_parkaging = 0)
                $plans_main = DB::table('plan_master')
                ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', '=', 'finished_product_category.id')
                ->leftJoin('intermediate_category', 'intermediate_category.intermediate_code', '=', 'finished_product_category.intermediate_code')
                ->leftJoin('dosage', 'intermediate_category.dosage_id', '=', 'dosage.id')
                ->where('plan_master.plan_list_id', $request->plan_list_id)
                ->where('plan_master.active', 1)
                ->where('plan_master.cancel', 0)
                ->where('plan_master.only_parkaging', 0)
                ->where('finished_product_category.IsHypothesis', 0)
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
                        'finished_product_category.batch_qty',
                        DB::raw("
                                CASE
                                        WHEN dosage.name LIKE '%phim%' THEN 1
                                        WHEN dosage.name LIKE '%nang%' THEN 0
                                        ELSE NULL
                                END AS w2
                                ")
                )
                ->orderBy('expected_date', 'asc')
                ->orderBy('level', 'asc')
                ->orderByRaw('batch + 0 ASC')
                ->get();

              
                
                // Phần 2: Các plan chỉ đóng gói (only_parkaging = 1)
                $plans_packaging = DB::table('plan_master')
                 ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', '=', 'finished_product_category.id')
                ->leftJoin('intermediate_category', 'intermediate_category.intermediate_code', '=', 'finished_product_category.intermediate_code')
                ->where('plan_master.plan_list_id', $request->plan_list_id)
                ->where('plan_master.active', 1)
                ->where('plan_master.cancel', 0)
                ->where('plan_master.only_parkaging', 1)
                ->where('finished_product_category.IsHypothesis', 0)
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
                ->orderByRaw('batch + 0 ASC')
                ->get();


                $stages = ['weight_1','weight_2', 'prepering', 'blending', 'forming', 'coating', 'primary_parkaging'];
                $stage_code = [
                        'weight_1'              => 1,
                        'weight_2'              => 2,
                        'prepering'             => 3,
                        'blending'              => 4,
                        'forming'               => 5,
                        'coating'               => 6,
                        'primary_parkaging'     => 7,
                ];
                
                $dataToInsert = [];
                
                foreach ($plans_main as $plan) {
                        $stageList = [];

                        // Vòng 1: gom các stage có tồn tại cho plan này
                        foreach ($stages as $index => $stage) {
                                if ($plan->$stage) {
                                        $stageList[] = [
                                                'w2'            => $plan->w2,
                                                'code'          => $plan->id . "_" . $stage_code[$stage],
                                                'stage_code'    => $stage_code[$stage],
                                                'order_by'      => $index,
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
                                    
                                        if ($stageItem['stage_code'] >= 3 && $prevItem['stage_code'] == 2) {
                                                $prevCode = collect($stageList)->firstWhere('stage_code', 1)['code'] ?? null;
                                        }elseif ($stageItem['stage_code'] == 2){
                                                $prevCode = null;
                                        }else {
                                                $prevCode = $prevItem['code'];
                                        }
                                }

                                

                                // ✅ set nextCode
                                if ($i < count($stageList) - 1) {
                                        $nextItem = $stageList[$i + 1];
                                        // nếu stage hiện tại = 1 và next là 2 thì bỏ qua, tìm stage_code >= 3
                                        if ($stageItem['stage_code'] == 1 && ($nextItem['stage_code'] == 2)) {
                                                $nextCode = collect($stageList)->first(fn($s) => $s['stage_code'] >= 3)['code'] ?? null;
                                        }elseif ($stageItem['stage_code'] == 2) {
                                                if ($stageItem['w2'] == 1){
                                                        $nextCode = explode ("_",$nextItem['code'])[0] ."_". "6";
                                                }else {
                                                        $nextCode = explode ("_",$nextItem['code'])[0] ."_". "5";
                                                }
                                        }       
                                        else {
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
                                        'Theoretical_yields' => $stageItem['stage_code'] <= 4 ? $plan->batch_size:$plan->batch_qty,
                                        'Theoretical_yields_qty'	=> $plan->batch_qty
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
                                                        'Theoretical_yields' => $stageItem['stage_code'] <= 4 ? $plan_packaging->batch_size:$plan_packaging->batch_qty,
                                                        'Theoretical_yields_qty'	=> $plan->batch_qty
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

                return redirect()->route('pages.plan.production.list');
        }

        public function updateInput(Request $request){
                $now = now();
                $user = session('user')['fullName'];
                $idOrPlanListId = 'id';

                if ($request->name == "selected"){
                        $updateData = ['selected' => !$request->updateValue]; 
                }
                else if ($request->name == "selected_all" && $request->id > 0){
                        $idOrPlanListId = 'plan_list_id';
                        $updateData = ['selected' => $request->updateValue == 1?1:0];  
                }else {
                        $updateData = [$request->name => $request->updateValue];
                }

        
                switch ($request->name) {
                case 'pro_feedback':
                        $updateData['pro_feedback_by']   = $user;
                        $updateData['pro_feedback_date'] = $now;
                        break;

                case 'qc_feedback':
                        $updateData['qc_feedback_by']   = $user;
                        $updateData['qc_feedback_date'] = $now;
                        break;
                case 'actual_CoA_date':
                        $updateData['qc_feedback_by']   = $user;
                        $updateData['qc_feedback_date'] = $now;
                        break;
   
                case 'en_feedback':
                        $updateData['en_feedback_by']   = $user;
                        $updateData['en_feedback_date'] = $now;
                        break;

                case 'has_punch_die_mold':
                        $updateData['en_feedback_by']   = $user;
                        $updateData['en_feedback_date'] = $now;
                        break;

                case 'qa_feedback':
                        $updateData['qa_feedback_by']   = $user;
                        $updateData['qa_feedback_date'] = $now;
                        break;
                case 'has_BMR':
                        $updateData['qa_feedback_by']   = $user;
                        $updateData['qa_feedback_date'] = $now;
                        break;
                case 'actual_record':
                        $updateData['qa_feedback_by']   = $user;
                        $updateData['qa_feedback_date'] = $now;
                        break;

                case 'actual_KCS':
                        $updateData['kcs_record_by']   = $user;
                        $updateData['kcs_record_date'] = $now;
                        break;

                default:
                        // các field khác như has_BMR, actual_record… thì không cần _by và _date
                        break;
                }
                Log::info ($request->all());
               
                if ($request->name  == "selected_all" && $request->id < 0){
                        DB::table('plan_master')
                        ->where('weighed', 0)
                        ->update(['selected' => 1]);
                }else {
                        DB::table('plan_master')
                        ->where($idOrPlanListId, $request->id)
                        ->update($updateData);
                }
                

                return response()->json(['updateValue' => $request->updateValue]);
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

        public function get_last_id (Request $request) {
                ob_clean();
                $last = DB::table($request->table)->latest('id')->value('id');
                return response()->json([
                        'last_id' => $last ?? 0
                ]);
        }

        public function feedback_list (Request $request) {
               
                 $datas = DB::table('plan_list')
                ->where ('active',1)
                ->where ('send',1)
                ->where ('deparment_code',session('user')['production_code'])
                ->where ('type',1)
                ->orderBy('id','desc')
                ->get();
        
                session()->put(['title'=> 'PHẢN HỒI KẾ HOẠCH SẢN XUẤT THÁNG']);
        
                return view('pages.plan.production.feedback_plan_list',[
                        'datas' => $datas 
                ]);
        }

        public function open_feedback(Request $request){
                
               $department = DB::table('user_management')->where('userName', session('user')['userName'])->value('deparment');  

               $datas = DB::table('plan_master')
                ->select(
                        'plan_master.*',
                        'finished_product_category.intermediate_code',
                        'finished_product_category.finished_product_code',
                        'product_name.name',
                        'market.code as market',
                        'specification.name as specification',
                        'finished_product_category.batch_qty',
                        'finished_product_category.unit_batch_qty',
                        'finished_product_category.deparment_code',
                        'source_material.name as source_material_name',
                        'stage_plan.end as end'
                )
                ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', 'finished_product_category.id')
                ->leftJoin('source_material', 'plan_master.material_source_id', 'source_material.id')
                ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
                ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
                ->leftJoin('specification', 'finished_product_category.specification_id', 'specification.id')
                ->leftJoin('stage_plan', function ($join) use ($request) {
                        $join->on('plan_master.id', '=', 'stage_plan.plan_master_id')
                        ->where('stage_plan.stage_code', 7)
                        ->where('stage_plan.active', 1)
                        ;
                })
                ->where('plan_master.plan_list_id', $request->plan_list_id)
                ->where('plan_master.active', 1)
                //->where('only_parkaging', 0)
                ->orderBy('expected_date', 'asc')
                ->orderBy('level', 'asc')
                ->orderBy('batch', 'asc')
                ->get();



               // dd ($datas);


                $production_name  =  session('user')['production_name'];
                $production =  session('user')['production_code'];

                $send_date = DB::table('plan_list')->where ('id',  $request->plan_list_id)-> value('send_date');

                session()->put(['title'=> "Phản Hồi $request->name - $production_name"]);
                
                return view('pages.plan.production.feedback_list',[
                        'datas' => $datas, 
                        'plan_list_id' => $request->plan_list_id,
                        'send'=> $request->send??1,
                        'department' => $department,
                        'production' => $production,
                        'send_date' => $send_date
                ]);
        }

        public function accept_expected_date(Request $request){
               
                DB::table('plan_master')->where('id', $request->id)->update([
                        "expected_date" => $request->new_expected_date,
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

                DB::table('plan_master_history')->insert([
                        'plan_master_id' => $plan->id,
                        'plan_list_id' => $plan->plan_list_id,
                        'product_caterogy_id' => $plan->product_caterogy_id,
                        'version' => $newVersion,

                        'level' => $plan->level,
                        'batch' => $plan->batch,
                        'expected_date' => $request->new_expected_date,
                        'is_val' => $plan->is_val,
                        'after_weigth_date' => $plan->after_weigth_date,
                        'after_parkaging_date' => $plan->after_parkaging_date,
                        'material_source_id' => $plan->material_source_id,
                        'percent_parkaging' => $plan->percent_parkaging,
                        'only_parkaging' => $plan->only_parkaging,
                        "number_parkaging" => $plan->number_parkaging,
                        'note' => $plan->note,
                        'reason' => "Chấp nhận ngày dự kiến KCS mới: $request->new_expected_date",
                        'deparment_code' => $plan->deparment_code,

                        'prepared_by' => session('user')['fullName'],
                        'created_at' => now(),
                        ]);

                return response()->json(['success' => true, 'message' => 'Đã cập nhật thành công!']);
        }

        public function all_feedback(Request $request){

                $dataToUpdate = [];
                if (isset($request->en_feedback)){
                        $dataToUpdate = [
                               'has_punch_die_mold' => $request->has_punch_die_mold == "on" ? 1:0,
                               'en_feedback' => $request->en_feedback ,
                        ];
                }else if (isset($request->qa_feedback)){
                        $dataToUpdate = [       
                                'actual_record' => $request->actual_record == "on" ? 1:0,
                                'has_BMR' => $request->has_BMR == "on" ? 1:0,
                                'en_feedback' => $request->qa_feedback
                        ];
                }else if (isset($request->qc_feedback)){
                       $dataToUpdate = [       
                                'qc_feedback' => $request->qc_feedback
                        ];
                }else if (isset($request->pro_feedback)){
                       $dataToUpdate = [       
                                'pro_feedback' => $request->pro_feedback
                        ];
                }
                
                DB::table('plan_master')
                ->where('plan_list_id', $request->plan_list_id)
                ->update($dataToUpdate);

                return redirect()->back()->with('success', 'Cập nhật trạng thái thành công!');
        }

        public function order (Request $request){
               
                DB::table('plan_master')->where('id', $request->id)->update([
                        "batch" => $request->batch,
                        'order_number' =>  $request->order_number,
                        'order_by' => session('user')['fullName'],
                        'order_date' => now(),
                ]);

                // Lấy dữ liệu gốc từ plan_master
                $plan = DB::table('plan_master')->where('id', $request->id)->first();
                
                // Tìm version cao nhất hiện tại trong history
                $lastVersion = DB::table('plan_master_history')
                        ->where('plan_master_id', $request->id)
                        ->max('version');

                $newVersion = $lastVersion ? $lastVersion + 1 : 1;

                DB::table('plan_master_history')->insert([
                        'plan_master_id' => $plan->id,
                        'plan_list_id' => $plan->plan_list_id,
                        'product_caterogy_id' => $plan->product_caterogy_id,
                        'version' => $newVersion,

                        'level' => $plan->level,
                        'batch' => $plan->batch,
                        'expected_date' => $plan->expected_date,
                        'is_val' => $plan->is_val,
                        'after_weigth_date' => $plan->after_weigth_date,
                        'after_parkaging_date' => $plan->after_parkaging_date,
                        'material_source_id' => $plan->material_source_id,
                        'percent_parkaging' => $plan->percent_parkaging,
                        'only_parkaging' => $plan->only_parkaging,
                        "number_parkaging" => $plan->number_parkaging,
                        'note' => $plan->note,
                        'reason' => "Cập nhật Số lệnh: $request->order_number",
                        'deparment_code' => $plan->deparment_code,

                        'prepared_by' => session('user')['fullName'],
                        'created_at' => now(),
                        ]);

                return response()->json(['success' => true, 'message' => 'Đã cập nhật thành công!']);
        }

        public function open_stock(Request  $request){
                //dd ( $request->all());

        try {        
                $plan_master_materials = DB::table('plan_master_materials as pmm')
                ->leftJoin('plan_master as pm','pmm.plan_master_id', 'pm.id')
                ->when($request->plan_list_id < 0,
                                 function ($q) {
                                        return $q->where('pm.weighed', 0);
                                },
                                function ($q) use ($request) {
                                        return $q->where('pm.plan_list_id', $request->plan_list_id);
                                }
                )
                ->where('pm.cancel', 0)
                ->where('pm.active', 1)
                ->where('pmm.active', 1)
                ->when($request->has('selected'), function ($q) {return $q->where('pm.selected', 1);})
                ->when($request->has('material_packaging_type'), function ($q) use ($request)  {return $q->where('pmm.material_packaging_type', $request->material_packaging_type);})
                ->select(
                        
                        'pmm.MaterialName',
                        'pmm.material_packaging_code',
                        'pmm.material_packaging_type',
                        'pmm.unit_bom',
                        DB::raw('SUM(pmm.qty) as total_qty'),
                        DB::raw('COUNT(DISTINCT pmm.plan_master_id) as NumberOfBatch'),
                        DB::raw('SUM(pmm.qty) * COUNT(DISTINCT pmm.plan_master_id) as TotalMatQty'),
                        DB::raw("GROUP_CONCAT(DISTINCT pmm.plan_master_id SEPARATOR '_') as plan_master_ids")
                )
                ->groupBy(
                        
                        'pmm.MaterialName',
                        'pmm.material_packaging_code',
                        'pmm.material_packaging_type',
                        'pmm.unit_bom'
                )
                ->orderBy('pmm.material_packaging_code')
                ->get();
               //dd ($plan_master_materials);

                $material_packaging_code =  $plan_master_materials->pluck ('material_packaging_code');

                $StockOverview = DB::connection('mms')
                        ->table('yf_RMPMStockOverview_pms as s')
                        ->whereIn('s.MatID', $material_packaging_code)
                        ->select(
                                's.GRNNO',
                                's.Mfgbatchno',
                                's.ARNO',
                                's.Expirydate',
                                's.Retestdate',
                                's.MatUOM',
                                's.MatID',
                                's.GRNSts',
                                's.Mfg',

                                DB::raw('SUM(s.ReceiptQuantity) as ReceiptQuantity'),
                                DB::raw('SUM([Total Qty]) as Total_Qty'),

                                // Gộp warehouse_id
                                        DB::raw("
                                        STUFF((
                                                SELECT DISTINCT ', ' + s2.warehouse_id
                                                FROM yf_RMPMStockOverview_pms s2
                                                WHERE s2.GRNNO = s.GRNNO
                                                FOR XML PATH(''), TYPE
                                        ).value('.', 'NVARCHAR(MAX)'), 1, 2, '') as warehouse_list
                                "),

                                // Gộp IntBatchNo
                                DB::raw("
                                        STUFF((
                                                SELECT DISTINCT ', ' + s3.IntBatchNo
                                                FROM yf_RMPMStockOverview_pms s3
                                                WHERE s3.GRNNO = s.GRNNO
                                                FOR XML PATH(''), TYPE
                                        ).value('.', 'NVARCHAR(MAX)'), 1, 2, '') as coa_list
                                ")
                        )
                        ->groupBy(
                                's.GRNNO',
                                's.Mfgbatchno',
                                's.ARNO',
                                's.Expirydate',
                                's.Retestdate',
                                's.MatUOM',
                                's.MatID',
                                's.GRNSts',
                                's.Mfg'
                        )
                ->get();

                
                $stockByMat = collect($StockOverview)->groupBy('MatID');
                
                $plan_master_materials = collect($plan_master_materials)
                        ->map(function ($item) use ($stockByMat) {

                        $stocks = $stockByMat[$item->material_packaging_code] ?? collect([]);

                        // 👉 Chỉ tính tổng, không thêm dòng
                        $item->totalReceipt = $stocks->sum('ReceiptQuantity');
                        $item->totalQty     = $stocks->sum('Total_Qty');

                        $item->stock = $stocks;

                        return $item;
                        })
                        ->sortBy(fn ($i) => mb_strtolower($i->MaterialName))
                        ->values();


                
                $production  =  session('user')['production_name'];


               
                session()->put(['title'=> "BẢNG DỰ TRÙ NGUYÊN LIỆU CHO $request->name - $production"]);

                if ($request->title){
                         session()->put(['title'=> "$request->title - $production"]);
                }
                
                //dd ($plan_master_materials);
                return view('pages.plan.production.stock_list',[
                        'datas' => $plan_master_materials, 
                        'plan_list_id' => $request->plan_list_id,
                        'month' => $request->month, 
                        'production' => $request->production,
                        'send'=> $request->send??1, 
                        'current_url' => $request->current_url??null
                ]);


        } catch (\Throwable $e) {

                        Log::error('OPEN_STOCK_ERROR', [
                                'message' => $e->getMessage(),
                                'line' => $e->getLine(),
                                'file' => $e->getFile(),
                                'trace' => $e->getTraceAsString()
                        ]);

                        return view('pages.plan.production.stock_list', [
                                'datas' => collect([]),
                                'js_error' => [
                                        'message' => $e->getMessage(),
                                        'line' => $e->getLine(),
                                        'file' => $e->getFile()
                                ]
                        ]);
        }

        }
        
        public function open_bacth_detail(Request  $request){
                

                $maxStageFinished = DB::table('stage_plan')
                ->whereIn('stage_plan.plan_master_id', $request->plan_master_ids)
                ->where('finished', 1)
                ->select(
                        'plan_master_id',
                        DB::raw('MAX(stage_code) as max_stage_code')
                )
                ->groupBy('plan_master_id');
  

                $datas = DB::table('plan_master')
                        ->select(
                                'plan_master.*',
                                'finished_product_category.intermediate_code',
                                'finished_product_category.finished_product_code',
                                DB::raw('fp_name.name AS finished_product_name'),
                                DB::raw('im_name.name AS intermediate_product_name'),
                                'market.name as market',
                                'specification.name as specification',
                                'finished_product_category.batch_qty',
                                'finished_product_category.unit_batch_qty',
                                'finished_product_category.deparment_code',
                                'source_material.name as source_material_name',

                                DB::raw("
                                CASE
                                        WHEN plan_master.cancel = 1 THEN 'Hủy'
                                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 1 THEN 'Đã Cân'
                                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 3 THEN 'Đã Pha chế'
                                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 4 THEN 'Đã THT'
                                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 5 THEN 'Đã định hình'
                                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 6 THEN 'Đã Bao phim'
                                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 7 THEN 'Hoàn Tất ĐG'
                                        ELSE 'Chưa làm'
                                        END AS status
                                ")
                        )
                        ->whereIn('plan_master.id', $request->plan_master_ids)
                        ->where('plan_master.active', 1)

                        ->leftJoin('finished_product_category','plan_master.product_caterogy_id','=','finished_product_category.id')
                        ->leftJoin('intermediate_category','finished_product_category.intermediate_code','=','intermediate_category.intermediate_code')
                        ->leftJoin('source_material', 'plan_master.material_source_id','=','source_material.id')
                        ->leftJoin('product_name as fp_name','finished_product_category.product_name_id','=','fp_name.id')
                        ->leftJoin('product_name as im_name','intermediate_category.product_name_id','=','im_name.id')
                        ->leftJoin('market', 'finished_product_category.market_id', '=', 'market.id')
                        ->leftJoin('specification', 'finished_product_category.specification_id', '=', 'specification.id')
                        ->leftJoinSub($maxStageFinished, 'sp_max', function ($join) {
                                $join->on('plan_master.id', '=', 'sp_max.plan_master_id');
                        })
                        ->leftJoin('stage_plan', function ($join) {
                                $join->on('plan_master.id', '=', 'stage_plan.plan_master_id')
                                ->on('stage_plan.stage_code', '=', 'sp_max.max_stage_code');
                        })
                        ->orderBy('expected_date', 'asc')
                        ->orderBy('level', 'asc')
                        ->orderBy('batch', 'asc')
                ->get();

                
                return response()->json([
                        'datas' => $datas
                ]);

              
        }

        public function open_feedback_API (Request $request){
               
                $deparment_code = $request->deparment_code?? 'PXV1';
                $month = $request->month ?? now()->month;
                $year = $request->year ?? now()->year;

                $plan_list_id =  DB::table('plan_list')->where ('deparment_code',$deparment_code)->where ('year',$year)->where ('month',$month)->pluck('id');
                
                // $maxStageFinished = DB::table('stage_plan')
                // ->whereIn('stage_plan.plan_list_id', $plan_list_id)
                // ->where('finished', 1)
                // ->select(
                //         'plan_master_id',
                //         DB::raw('MAX(stage_code) as max_stage_code')
                // )
                // ->groupBy('plan_master_id');

                $datas = DB::table('plan_master')
                ->select(
            
                        "plan_master.id",
                        "plan_master.plan_list_id",
                        "plan_master.product_caterogy_id",
                        "plan_master.level",
                        "plan_master.batch",
                        "plan_master.actual_batch",
                        "plan_master.order_number",
                        "plan_master.expected_date",
                        "plan_master.responsed_date",
                        "plan_master.actual_KCS",
                        "plan_master.is_val",
                        "plan_master.code_val",
                        "plan_master.after_weigth_date",
                        "plan_master.parkaging_before_date",
                        "plan_master.after_parkaging_date",
                        "plan_master.expired_packing_date",
                        "plan_master.preperation_before_date",
                        "plan_master.blending_before_date",
                        "plan_master.coating_before_date",
                        "plan_master.allow_weight_before_date",
                        "plan_master.expired_material_date",
                        "plan_master.material_source_id",
                        "plan_master.only_parkaging",
                        "plan_master.percent_parkaging",
                        "plan_master.main_parkaging_id",
                        "plan_master.number_parkaging",
                        "plan_master.note",
                        "plan_master.pro_feedback",
                        "plan_master.qc_feedback",
                        

                        DB::raw("IF(plan_master.qa_feedback IS NOT NULL, plan_master.qa_feedback, 'NA') AS qa_feedback_text"),
                        DB::raw("IF(plan_master.has_BMR = 0, 'Chưa sẵn sàng', 'Đã sẵn sàng') AS has_BMR_text"),

                        DB::raw("IF(plan_master.en_feedback IS NOT NULL, plan_master.en_feedback, 'NA') AS en_feedback"),
                        DB::raw("IF(plan_master.has_punch_die_mold = 0, 'Chưa sẵn sàng', 'Đã sẵn sàng') AS has_punch_die_mold"),

      
                        "plan_master.actual_CoA_date",
                        "plan_master.actual_record_date",
                  
                        "plan_master.qa_feedback_by",
                        "plan_master.qa_feedback_date",
                        "plan_master.qc_feedback_by",
                        "plan_master.qc_feedback_date",
                        "plan_master.pro_feedback_by",
                        "plan_master.pro_feedback_date",
                        "plan_master.en_feedback_by",
                        "plan_master.en_feedback_date",
                        "plan_master.kcs_record_by",
                        "plan_master.kcs_record_date",
                        "plan_master.accept_expectedDate_by",
                        "plan_master.accept_expectedDate_date",
                        "plan_master.deparment_code",
                        "plan_master.active",
                        "plan_master.cancel",
                      
                        'finished_product_category.intermediate_code',
                        'finished_product_category.finished_product_code',
                        'product_name.name',
                        'market.code as market',
                        'specification.name as specification',
                        'finished_product_category.batch_qty',
                        'finished_product_category.unit_batch_qty',
                        'finished_product_category.deparment_code',
                        'source_material.name as source_material_name',
                        'stage_plan.end as end'
                )
                ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', 'finished_product_category.id')
                ->leftJoin('source_material', 'plan_master.material_source_id', 'source_material.id')
                ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
                ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
                ->leftJoin('specification', 'finished_product_category.specification_id', 'specification.id')
                ->leftJoin('stage_plan', function ($join) use ($request) {
                        $join->on('plan_master.id', '=', 'stage_plan.plan_master_id')
                        ->where('stage_plan.stage_code', 7)
                        ->where('stage_plan.active', 1)
                        ;
                })
                ->whereIn('plan_master.plan_list_id', $plan_list_id)
                ->where('plan_master.active', 1)
                ->orderBy('id', 'asc')
                ->get();

                return response()->json([
                        'datas' => $datas
                ]);

        }

        public function recipe_show_update(Request $request){
                
                $datas = DB::table('plan_master_materials as pmm')
                ->where('pmm.plan_master_id', $request->plan_master_id)
                ->where('pmm.material_packaging_type', $request->material_packaging_type)
                ->get();
                return response()->json($datas);

        }

        public function update_plan_master_material(Request $request){

                $type_update =   'intermediate_code'; //'finished_product_code';
                $material_packaging_type = 0;
                $insertData = [];

                // 1️⃣ Lấy plan
                $plans = DB::table('plan_master as pm')
                        ->select(
                        'pm.id as plan_master_id',
                        "fpc.$type_update"
                        )
                        ->leftJoin('finished_product_category as fpc',
                        'pm.product_caterogy_id',
                        '=',
                        'fpc.id'
                        )
                        ->where('pm.active', 1)
                        ->where('pm.plan_list_id', '>', 23)
                        //->where('pm.weighed', 0)
                        ->where('pm.cancel', 0)
                        ->get();
                //dd ($plans);
                // 2️⃣ Lấy danh sách PrdID
                $prdIds = $plans->pluck($type_update)
                        ->filter()
                        ->unique()
                        ->values();

                if ($prdIds->isEmpty()) {
                        return response()->json([]);
                }

                // 3️⃣ Lấy BOM từ SQL Server
                $boms = DB::connection('mms')
                        ->table('yfBOM_BOMItemHP')
                        ->whereIn('PrdID', $prdIds)
                        ->get();

                if ($boms->isEmpty()) {
                        return response()->json([]);
                }

                // 4️⃣ Tính Revno max theo từng PrdID (CHỈ TÍNH 1 LẦN)
                $maxRevByPrd = $boms
                        ->groupBy('PrdID')
                        ->map(fn($items) => $items->max('Revno'));

                // 5️⃣ Lọc BOM chỉ giữ Revno max
                $boms = $boms->filter(function ($item) use ($maxRevByPrd) {
                        return $item->Revno == $maxRevByPrd[$item->PrdID];
                });

                // 6️⃣ Group lại theo PrdID cho nhanh
                $bomsGrouped = $boms->groupBy('PrdID');

                // 7️⃣ Map vào từng plan
                foreach ($plans as $plan) {

                        $prdId = $plan->$type_update;

                        if (!isset($bomsGrouped[$prdId])) {
                        continue;
                        }

                        foreach ($bomsGrouped[$prdId] as $item) {

                        $insertData[] = [
                                'plan_master_id'          => $plan->plan_master_id,
                                'material_packaging_code' => (string) $item->MatID,
                                'material_packaging_type' => $material_packaging_type,
                                'Revno'                   => $item->Revno,
                                'qty'                     => (float) $item->MatQty,
                                'unit_bom'                => $item->uom,
                                'MaterialName'            => $item->MaterialName,
                                'created_at'              => now(),
                                'created_by'              => "Auto_generate",
                                'active'                  => 1,
                        ];
                        }
                }

                // 8️⃣ Upsert
                if (!empty($insertData)) {

                foreach (array_chunk($insertData, 1000) as $chunk) {

                        DB::table('plan_master_materials')->upsert(
                        $chunk,
                        ['plan_master_id', 'material_packaging_code', 'material_packaging_type'],
                        ['qty', 'unit_bom', 'active', 'Revno']
                        );

                }
                }

                return response()->json([]);
        }



}
