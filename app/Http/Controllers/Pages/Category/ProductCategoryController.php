<?php

namespace App\Http\Controllers\Pages\Category;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductCategoryController extends Controller
{
        
        public function index(){

                $markets = DB::table('market')->where('active', true)->get();
                $specifications = DB::table('specification')->where('active', true)->get();
                $units = DB::table('unit')->where('active', true)->get();
                $productNames = DB::table('product_name')->where('active', true)->get();

                $intermediate_category = DB::table('intermediate_category')->select('intermediate_category.*','dosage.name as dosage_name' , 'product_name.name as product_name')
                ->leftJoin('product_name','intermediate_category.product_name_id','product_name.id')
                ->leftJoin('dosage','intermediate_category.dosage_id','dosage.id')
                ->where('intermediate_category.active', true)
                ->orderBy('product_name.name','asc')->get();

                $datas = DB::table('finished_product_category')
                ->select('finished_product_category.*', 'product_name.name as product_name', 'intermediate_category.intermediate_code',
                        'intermediate_category.batch_size','intermediate_category.unit_batch_size'
                )
                ->leftJoin('intermediate_category','finished_product_category.intermediate_code','intermediate_category.intermediate_code')
                ->leftJoin('product_name','intermediate_category.product_name_id','product_name.id')
                ->orderBy('product_name.name','asc')->get();

                session()->put(['title'=> 'DANH MỤC THÀNH PHẨM']);
                return view('pages.category.product.list',[
                        'datas' => $datas,
                        'intermediate_category' => $intermediate_category,                      
                        'units' => $units,
                        'productNames' => $productNames,   
                        'markets' => $markets,     
                        'specifications' => $specifications        
                ]);
        }
    

        public function store (Request $request) {

      
                $validator = Validator::make($request->all(), [
                'code' => 'required|string|min:5',
                'productName' => 'required|string|min:5',
                'testing' => 'required|string',
                'sample_Amout' => 'required|numeric|gt:0', 
                'unit' => 'required|string',
                'excution_time' => 'required|numeric|gt:0', 
                'instrument_type' => 'required|string',
                'testing_code' =>  'required|unique:product_category,testing_code',

                ], [

                'code.required' => 'Vui lòng nhập Số Qui Trình.',
                'code.unique' => 'Số qui trình đã tồn tại.',
                'code.min' => 'Số qui trình phải có ít nhất :min ký tự.',
                
                'productName.required' => 'Vui lòng chọn chỉ tiêu kiểm',

                'testing.required' => 'Vui lòng chọn chỉ tiêu kiểm',

                'sample_Amout.required' => 'Vui lòng nhập số lượng mẫu',
                'sample_Amout.numeric' => 'Số lượng mẫu là kiểu số',
                'sample_Amout.gt' => 'Số lượng mẫu phải lớn hơn 0',

                'testing.unit' => 'Vui lòng chọn đơn vị tính',

                'excution_time.required' => 'Vui lòng nhập số lượng mẫu',
                'excution_time.numeric' => 'Số lượng mẫu là kiểu số',
                'excution_time.gt' => 'Số lượng mẫu phải lớn hơn 0',
                
                'instrument_type.required' => 'Vui lòng chọn thiết bị kiểm',

                'testing_code.unique' => 'Danh mục sản phẩm Đã Tồn Tại đã tồn tại.',
                ]);
               
              

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
                }

                DB::table('product_category')->insert([
                        'code' => $request->code,
                        'name' => $request->productName,                       
                        'testing' => $request->testing,
                        'sample_Amout'=> $request->sample_Amout,
                        'unit'=> $request->unit,
                        'testing_code' => $request->testing_code,
                        'excution_time' => $request->excution_time,
                        'instrument_type'=> $request->instrument_type,
                        'prepareBy' => session('user')['fullName'] ?? 'Admin',
                        'created_at' => now(),
                ]);
                return redirect()->back()->with('success', 'Đã thêm thành công!');    
        }

        public function update(Request $request){
               
                $validator = Validator::make($request->all(), [
              
                'sample_Amout' => 'required|numeric|gt:0', 
                'unit' => 'required|string',
                'excution_time' => 'required|numeric|gt:0', 
                'instrument_type' => 'required|string',
                
                ], [

                'testing.required' => 'Vui lòng chọn chỉ tiêu kiểm',

                'sample_Amout.required' => 'Vui lòng nhập số lượng mẫu',
                'sample_Amout.numeric' => 'Số lượng mẫu là kiểu số',
                'sample_Amout.numeric' => 'Số lượng mẫu phải lớn hơn 0',

                'testing.unit' => 'Vui lòng chọn đơn vị tính',

                'excution_time.required' => 'Vui lòng nhập số lượng mẫu',
                'excution_time.numeric' => 'Số lượng mẫu là kiểu số',
                'excution_time.numeric' => 'Số lượng mẫu phải lớn hơn 0',
                
                'instrument_type.unit' => 'Vui lòng chọn loại thiết bị',
                ]);
                
                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
                } 
    
                 DB::table('product_category')->where('id', $request->id)->update([

                        'sample_Amout'=> $request->sample_Amout,
                        'unit'=> $request->unit,
                        'excution_time' => $request->excution_time,
                        'instrument_type'=> $request->instrument_type,
                        'prepareBy' => session('user')['fullName'] ?? 'Admin',
                        'updated_at' => now(),
                ]);
                return redirect()->back()->with('success', 'Đã thêm thành công!');   
        }

        public function deActive(string|int $id){
                
               DB::table('product_category')->where('id', $id)->update([
                        'Active' => 0,
                        'prepareBy' => session('user')['fullName'],
                        'updated_at' => now(), 
                ]);
                return redirect()->back()->with('success', 'Vô Hiệu Hóa thành công!');
        }
}