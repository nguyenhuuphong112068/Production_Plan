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
                $specifications = DB::table('specification')->get();
                $productNames = DB::table('product_name')->where('active', true)->get();

                $intermediate_category = DB::table('intermediate_category')->select('intermediate_category.*','dosage.name as dosage_name' , 'product_name.name as product_name')
                ->leftJoin('product_name','intermediate_category.product_name_id','product_name.id')
                ->leftJoin('dosage','intermediate_category.dosage_id','dosage.id')
                ->where('intermediate_category.active', true)
                ->where('intermediate_category.deparment_code', session('user')['production_code'])
                ->orderBy('product_name.name','asc')->get();

                $datas = DB::table('finished_product_category')
                ->select('finished_product_category.*', 
                        'product_name.name as product_name', 
                        'intermediate_category.intermediate_code',
                        'intermediate_category.batch_size',
                        'intermediate_category.unit_batch_size',
                        'market.code as market',
                        'specification.name as specification'
                )
                ->where('finished_product_category.deparment_code', session('user')['production_code'])
                ->leftJoin('intermediate_category','finished_product_category.intermediate_code','intermediate_category.intermediate_code')
                ->leftJoin('product_name','intermediate_category.product_name_id','product_name.id')
                ->leftJoin('market','finished_product_category.market_id','market.id')
                ->leftJoin('specification','finished_product_category.specification_id','specification.id')
                ->orderBy('product_name.name','asc')->get();

                session()->put(['title'=> 'DANH MỤC THÀNH PHẨM']);
                return view('pages.category.product.list',[
                        'datas' => $datas,
                        'intermediate_category' => $intermediate_category,                      
                        'productNames' => $productNames,   
                        'markets' => $markets,     
                        'specifications' => $specifications        
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
                        'batch_qty.required' => 'Vui lòng nhâp cở lô',
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
                        'batch_qty.required' => 'Vui lòng nhâp cở lô',
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
              
               DB::table('finished_product_category')->where('id', $request->id)->update([
                        'Active' => !$request->active,
                        'prepared_by' => session('user')['fullName'],
                        'updated_at' => now(), 
                ]);
                return redirect()->back()->with('success', 'Vô Hiệu Hóa thành công!');
        }
}