<?php

namespace App\Http\Controllers\Pages\Category;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
class IntermediateCategoryController extends Controller
{
        
        public function index(){

                $productNames = DB::table('product_name')->where('active', true)->orderBy('name','asc')->get();
                $dosages = DB::table('dosage')->where('active', true)->get();
                $units = DB::table('unit')->where('active', true)->get();
              
                $datas = DB::table('intermediate_category')->select('intermediate_category.*','dosage.name as dosage_name' , 'product_name.name as product_name')
                ->leftJoin('product_name','intermediate_category.product_name_id','product_name.id')
                ->leftJoin('dosage','intermediate_category.dosage_id','dosage.id')
                ->where('intermediate_category.deparment_code', session('user')['production_code'])
                ->when(!user_has_permission(session('user')['userId'], 'view_Hypothesis_category', 'boolean'),
                        function ($q) {
                                return $q->where('intermediate_category.IsHypothesis', 0);
                })
                ->where ('cancel',0)
                ->orderBy('intermediate_category.IsHypothesis','desc')
                ->orderBy('product_name.name','asc')->get();
                
                session()->put(['title'=> 'DANH MỤC BÁN THÀNH PHẨM']);
       
                return view('pages.category.intermediate.list',[
                        'datas' => $datas,
                        'productNames' =>$productNames,
                        'dosages' =>$dosages,
                        'units'=>$units,
                    
                ]);
        }

        public function store (Request $request) {
                //dd ($request->all());

               
                $validator = Validator::make($request->all(), [
                                'intermediate_code' => 'required|unique:intermediate_category,intermediate_code',
                                'product_name_id' => 'required',
                                'dosage_id' => 'required',
                                'batch_size' => 'required',
                                'batch_qty' => 'required',
                                'unit_batch_qty' => 'required',
                ], [
                                'intermediate_code.required' => 'Vui lòng nhập mã bán thành phẩm.',
                                'intermediate_code.unique' => 'Mã bán thành phẩm đã tồn tại.',
                                'product_name_id.required' => 'Vui lòng chọn tên sản phẩm',
                                'dosage_id.required' => 'Vui lòng chọn dạng bào chế',
                                'batch_size.required' => 'Vui lòng nhập cỡ lô',
                                'batch_qty.required' => 'Vui lòng nhập cỡ lô',
                                'unit_batch_qty.required' => 'Vui lòng chọn đơn vị '
                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
                }
                $dosage_name = DB::table('dosage')->where('id', $request->dosage_id )->value('name');

               
                if (Str::contains(Str::lower($dosage_name), ['phim', 'nang'])) {$weight_2 = true;}else {$weight_2 = false;};

                DB::table('intermediate_category')->insert([
                        'intermediate_code' => $request->intermediate_code,
                        'product_name_id' => $request->product_name_id,                       
                        'batch_size' => $request->batch_size,
                        'unit_batch_size'=> $request->unit_batch_size,
                        'batch_qty'=> $request->batch_qty,
                        'unit_batch_qty' => $request->unit_batch_qty,

                        'dosage_id' => $request->dosage_id,
                        'weight_1' => $request->weight_1 === "on" ?true:false,
                        'weight_2'=> $weight_2,
                        'prepering'=> $request->prepering === "on" ?true:false,
                        'blending'=> $request->blending === "on" ?true:false,
                        'forming'=> $request->forming === "on" ?true:false,
                        'coating'=> $request->coating === "on" ?true:false,

                        'quarantine_total'=> $request->quarantine_total??0,
                        'quarantine_weight'=> $request->quarantine_weight??0,
                        'quarantine_preparing'=> $request->quarantine_preparing??0,
                        'quarantine_blending'=> $request->quarantine_blending??0,
                        'quarantine_forming'=> $request->quarantine_forming??0,
                        'quarantine_coating'=> $request->quarantine_coating??0,
                        'quarantine_time_unit' => $request->quarantine_time_unit === "on"?true:false,
                        'IsHypothesis' => $request->is_Hypothesis??0,
                        'deparment_code'=> session('user')['production_code'],
                        'prepared_by' => session('user')['fullName'],
                        'created_at' => now(),
                ]);
                return redirect()->back()->with('success', 'Đã thêm thành công!');    
        }

        public function update(Request $request){
               
                $validator = Validator::make($request->all(), [
                        //'intermediate_code' => 'required|unique:intermediate_category,intermediate_code',
                        'product_name_id' => 'required',
                        'dosage_id' => 'required',
                        'batch_size' => 'required',
                        'batch_qty' => 'required',
                        'unit_batch_qty' => 'required',
                ], [
                        //'intermediate_code.required' => 'Vui lòng nhập mã bán thành phẩm.',
                        'intermediate_code.unique' => 'Mã bán thành phẩm đã tồn tại.',
                        'product_name_id.required' => 'Vui lòng chọn tên sản phẩm',
                        'dosage_id.required' => 'Vui lòng chọn dạng bào chế',
                        'batch_size.required' => 'Vui lòng nhập cỡ lô',
                        'batch_qty.required' => 'Vui lòng nhập cỡ lô',
                        'unit_batch_qty.required' => 'Vui lòng chọn đơn vị '
                ]);
                
                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
                } 
                $dosage_name = DB::table('dosage')->where('id', $request->dosage_id )->value('name');
                if (Str::contains(Str::lower($dosage_name), ['phim', 'nang'])) {$weight_2 = true;}else {$weight_2 = false;}

                DB::table('intermediate_category')->where('id', $request->id)->update([

                        'intermediate_code' => $request->intermediate_code,
                        'product_name_id' => $request->product_name_id,                       
                        'batch_size' => $request->batch_size,
                        'unit_batch_size'=> $request->unit_batch_size,
                        'batch_qty'=> $request->batch_qty,
                        'unit_batch_qty' => $request->unit_batch_qty,

                        'dosage_id' => $request->dosage_id,
                        'weight_1' => $request->weight_1 === "on" ?true:false,
                        'weight_2'=> $weight_2,
                        'prepering'=> $request->prepering === "on" ?true:false,
                        'blending'=> $request->blending === "on" ?true:false,
                        'forming'=> $request->forming === "on" ?true:false,
                        'coating'=> $request->coating === "on" ?true:false,

                        'quarantine_total'=> $request->quarantine_total??0,
                        'quarantine_weight'=> $request->quarantine_weight??0,
                        'quarantine_preparing'=> $request->quarantine_preparing??0,
                        'quarantine_blending'=> $request->quarantine_blending??0,
                        'quarantine_forming'=> $request->quarantine_forming??0,
                        'quarantine_coating'=> $request->quarantine_coating??0,
                        'quarantine_time_unit' => $request->quarantine_time_unit === "on"?true:false,

                        'deparment_code'=> session('user')['production_code'],
                        'prepared_by' => session('user')['fullName'],
                        'updated_at' => now(),
                ]);
                return redirect()->back()->with('success', 'Đã thêm thành công!');   
        }

        public function deActive(Request $request){

                if ($request->IsHypothesis == 1){
                        DB::table('intermediate_category')->where('id', $request->id)->update([
                                'cancel' => 1,
                                'prepared_by' => session('user')['fullName'],
                                'updated_at' => now(), 
                        ]);
                }else{
                        DB::table('intermediate_category')->where('id', $request->id)->update([
                                'Active' => !$request->active,
                                'prepared_by' => session('user')['fullName'],
                                'updated_at' => now(), 
                        ]);
                }
              

                return redirect()->back()->with('success', 'Vô Hiệu Hóa thành công!');
        }

        public function recipe(Request $request){
               
                if ($request->IsHypothesis == 1){
                        $datas = DB::table('bom_item')
                        ->select ([
                             'code as MatID',
                             'name as MaterialName',
                             'qty as MatQty',
                             'uom',
                             'Revno'
                        ])
                        ->where('active', 1)
                        ->where('product_caterogy_id', $request->product_caterogy_id)
                        ->get();
                }else{
                        $datas = DB::connection('mms')
                        ->table('yfBOM_BOMItemHP')
                        ->where('PrdID', $request->intermediate_code)
                        ->where('Revno', function ($q) use ($request) {
                                $q->selectRaw('MAX(Revno)')
                                ->from('yfBOM_BOMItemHP')
                                ->where('PrdID', $request->intermediate_code);
                        })
                        ->distinct()
                        ->orderBy('PrdStage')
                        ->orderBy('MatID')
                        ->get();
                }
               
                return response()->json($datas);
        }

        // public function save_bom(Request $request){
                
        //         $items = $request->items;

        //         if (empty($items)) {
        //                 return response()->json(['success' => false, 'message' => 'No items']);
        //         }

        //         $productCategoryId = $items[0]['product_caterogy_id'];

        //         // 1️⃣ Lấy danh sách code gửi lên
        //         $requestCodes = collect($items)->pluck('code')->toArray();

        //          // 2️⃣ Soft delete những code không có trong request
        //         DB::table('bom_item')
        //                 ->where('product_caterogy_id', $productCategoryId)
        //                 ->whereNotIn('code', $requestCodes)
        //                 ->update([
        //                         'active' => 0,
        //                         'updated_at' => now()
        //                 ]);

        //         // 3️⃣ Insert hoặc update + bật active lại
        //         foreach ($items as $item) {
        //                 DB::table('bom_item')->updateOrInsert(
        //                 [
        //                         'product_caterogy_id' => $item['product_caterogy_id'],
        //                         'code' => $item['code'],
        //                 ],
        //                 [
        //                         'name' => $item['name'],
        //                         'qty' => $item['qty'],
        //                         'uom' => $item['uom'],
        //                         'mat_par_type' => $item['mat_par_type'],
        //                         'Revno' => 0,
        //                         'active' => 1, // đảm bảo nếu thêm lại thì active lại
        //                         'updated_at' => now(),
        //                         'created_by' => session('user')['fullName'],
        //                 ]
        //         );
        //         }

        //         return response()->json(['success' => true]);
        // }

}