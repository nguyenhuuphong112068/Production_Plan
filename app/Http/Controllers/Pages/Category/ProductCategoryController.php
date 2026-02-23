<?php

namespace App\Http\Controllers\Pages\Category;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ProductCategoryController extends Controller
{
        
        public function index(){

                $markets = DB::table('market')->where('active', true)->orderBy('code','asc')->get();
                $specifications = DB::table('specification')->orderBy('name','asc')->get();
                $productNames = DB::table('product_name')->where('active', true)->orderBy('name','asc')->get();
          
                $intermediate_category = DB::table('intermediate_category')
                ->select('intermediate_category.*',
                        'dosage.name as dosage_name' , 
                        'product_name.name as product_name')
                ->leftJoin('product_name','intermediate_category.product_name_id','product_name.id')
                ->leftJoin('dosage','intermediate_category.dosage_id','dosage.id')
                ->when(!user_has_permission(session('user')['userId'], 'view_Hypothesis_category', 'boolean'),
                        function ($q) { return $q->where('intermediate_category.IsHypothesis', 0);
                })
                ->when(!user_has_permission(session('user')['userId'], 'category_product_create', 'boolean'),
                        function ($q) { return $q->where('intermediate_category.IsHypothesis', 1);
                })
                ->where('intermediate_category.active', true)
                ->where('intermediate_category.cancel', 0)
                ->where('intermediate_category.deparment_code', session('user')['production_code'])
                ->orderBy('product_name.name','asc')->get();

                //dd ($intermediate_category);

                $datas = DB::table('finished_product_category')
                ->select('finished_product_category.*', 
                        'intermediate_category.intermediate_code',
                        'intermediate_category.batch_size',
                        'intermediate_category.unit_batch_size',
                        'market.code as market',
                        'specification.name as specification',
                        DB::raw('fp_name.name AS finished_product_name'),
                        DB::raw('im_name.name AS intermediate_product_name'),
                )
                ->where('finished_product_category.deparment_code', session('user')['production_code'])
                ->when(!user_has_permission(session('user')['userId'], 'view_Hypothesis_category', 'boolean'),
                        function ($q) {
                                return $q->where('finished_product_category.IsHypothesis', 0);
                        }
                )->where ('finished_product_category.cancel',0)
                ->leftJoin('intermediate_category','finished_product_category.intermediate_code','intermediate_category.intermediate_code')
                ->leftJoin('product_name as fp_name','finished_product_category.product_name_id','=','fp_name.id')
                ->leftJoin('product_name as im_name','intermediate_category.product_name_id','=','im_name.id')
                ->leftJoin('market','finished_product_category.market_id','market.id')
                ->leftJoin('specification','finished_product_category.specification_id','specification.id')
                ->orderBy('IsHypothesis','desc')
                ->orderBy('finished_product_name','asc')
                ->get();

                $units = DB::table('unit')->where('active', true)->get();
              
                session()->put(['title'=> 'DANH MỤC THÀNH PHẨM']);
                return view('pages.category.product.list',[
                        'datas' => $datas,
                        'intermediate_category' => $intermediate_category,                      
                        'productNames' => $productNames,   
                        'markets' => $markets,     
                        'specifications' => $specifications,
                        'units' => $units      
                ]);
        }

        public function store (Request $request) {

                //dd ($request->all());
                $validator = Validator::make($request->all(), [
                        'process_code' => 'required|unique:finished_product_category,process_code',
                        'finished_product_code' => 'required',
                        'product_name_id' => 'required',
                        'batch_qty' => 'required',
                        'market_id' => 'required',
                        'specification_id' => 'required'
                ], [
                        'process_code.unique' => 'Mã bán thành phẩm đã được liên kết với mà báng thành phẩm ',       
                        'finished_product_code.required' => 'Vui lòng nhập Mã Thành Phẩm',
                        'product_name_id.required' => 'Vui lòng chọn tên sản phẩm',
                        'batch_qty.required' => 'Vui lòng nhâp cỡ lô',
                        'market_id.required' => 'Vui lòng chọn thị trường',
                        'specification_id.required' => 'Vui lòng chọn qui cách',
                ]);
               
              

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
                }

                DB::table('finished_product_category')->insert([
                        'process_code' => $request->process_code,
                        'finished_product_code' => $request->finished_product_code,                       
                        'intermediate_code' => $request->intermediate_code,
                        'product_name_id'=> $request->product_name_id,
                        'market_id'=> $request->market_id,
                        'specification_id' => $request->specification_id,
                        'batch_qty' => $request->batch_qty,
                        'unit_batch_qty'=> $request->unit_batch_qty,
                        'primary_parkaging'=> $request->primary_parkaging == "on"? true:false,
                        'secondary_parkaging' => false,
                         'IsHypothesis' => $request->is_Hypothesis??0,
                        'deparment_code'=> session('user')['production_code'],
                        'prepared_by' => session('user')['fullName'],
                        'created_at' => now(),
                ]);
                return redirect()->back()->with('success', 'Đã thêm thành công!');    
        }

        public function update(Request $request){
                //dd ($request->all());
                $validator = Validator::make($request->all(), [

                        'product_name_id' => 'required',
                        'batch_qty' => 'required',
                        'market_id' => 'required',
                        'specification_id' => 'required'
                ], [
                       
                        'product_name_id.required' => 'Vui lòng chọn tên sản phẩm',
                        'batch_qty.required' => 'Vui lòng nhâp cỡ lô',
                        'market_id.required' => 'Vui lòng chọn thị trường',
                        'specification_id.required' => 'Vui lòng chọn qui cách',
                ]);
                
                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
                } 
    
                 DB::table('finished_product_category')->where ('id', $request->id)->update([
                        'product_name_id'=> $request->product_name_id,
                        'market_id'=> $request->market_id,
                        'specification_id' => $request->specification_id,
                        'batch_qty' => $request->batch_qty,
                        'primary_parkaging'=> $request->primary_parkaging == "on"? true:false,
                        'prepared_by' => session('user')['fullName'],
                        'updated_at' => now(),
                ]);
                return redirect()->back()->with('success', 'Đã Cập nhật thành công!');   
        }

        public function deActive(Request $request){
        
                if ($request->IsHypothesis == 1){
                        DB::table('finished_product_category')->where('id', $request->id)->update([
                                'cancel' => 1,
                                'prepared_by' => session('user')['fullName'],
                                'updated_at' => now(), 
                        ]);
                }else{
                        DB::table('finished_product_category')->where('id', $request->id)->update([
                                'Active' => !$request->active,
                                'prepared_by' => session('user')['fullName'],
                                'updated_at' => now(), 
                        ]);
                }
                return redirect()->back()->with('success', 'Vô Hiệu Hóa thành công!');
        }

        public function getJsonFPCategory(){

                $datas = DB::table('finished_product_category')
                ->where ('finished_product_category.active', 1)
                ->select(
                        'intermediate_category.intermediate_code',
                        'finished_product_category.deparment_code',

                        DB::raw("
                        GROUP_CONCAT(
                                DISTINCT finished_product_category.finished_product_code
                                SEPARATOR '\n'
                        ) AS finished_product_codes
                        "),

                        DB::raw("
                        GROUP_CONCAT(
                                DISTINCT product_name.name
                                SEPARATOR '\n'
                        ) AS product_names
                        "),

                        DB::raw("
                        GROUP_CONCAT(
                                DISTINCT market.code
                                SEPARATOR '\n'
                        ) AS markets
                        "),

                        DB::raw("
                        GROUP_CONCAT(
                                DISTINCT specification.name
                                SEPARATOR '\n'
                        ) AS specifications
                        "),

                        DB::raw("MAX(intermediate_category.batch_size) AS batch_size"),
                        DB::raw("MAX(intermediate_category.unit_batch_size) AS unit_batch_size")
                )
                ->leftJoin('intermediate_category','finished_product_category.intermediate_code','intermediate_category.intermediate_code')
                ->leftJoin('product_name','finished_product_category.product_name_id','product_name.id')
                ->leftJoin('market','finished_product_category.market_id','market.id')
                ->leftJoin('specification','finished_product_category.specification_id','specification.id')
                ->groupBy('intermediate_category.intermediate_code', 'finished_product_category.deparment_code')
                ->get();




                return response()->json([
                                'datas' => $datas
                ]);

        }

        public function save_bom(Request $request){
                Log::info ($request->all());
                $items = $request->items;

                if (empty($items)) {
                        return response()->json(['success' => false, 'message' => 'No items']);
                }

                $productCategoryId = $items[0]['product_caterogy_id'];

                // 1️⃣ Lấy danh sách code gửi lên
                $requestCodes = collect($items)->pluck('code')->toArray();

                 // 2️⃣ Soft delete những code không có trong request
                DB::table('bom_item')
                        ->where('product_caterogy_id', $productCategoryId)
                        ->whereNotIn('code', $requestCodes)
                        ->update([
                                'active' => 0,
                                'updated_at' => now()
                        ]);

                // 3️⃣ Insert hoặc update + bật active lại
                foreach ($items as $item) {
                        DB::table('bom_item')->updateOrInsert(
                        [
                                'product_caterogy_id' => $item['product_caterogy_id'],
                                'code' => $item['code'],
                        ],
                        [
                                'name' => $item['name'],
                                'qty' => $item['qty'],
                                'uom' => $item['uom'],
                                'mat_par_type' => $item['mat_par_type'],
                                'Revno' => 0,
                                'active' => 1, // đảm bảo nếu thêm lại thì active lại
                                'updated_at' => now(),
                                'created_by' => session('user')['fullName'],
                        ]
                );
                }

                return response()->json(['success' => true]);
        }
    
}