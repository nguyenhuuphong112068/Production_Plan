<?php

namespace App\Http\Controllers\Pages\Import;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ImportController extends Controller
{
        public function index(){

                $category = DB::table('product_category')->where ('active',1)->orderBy('created_at','desc')->get();

                $datas = DB::table('import')
                ->select('import.*', 'product_category.name', 'product_category.code', 'product_category.testing','product_category.testing_code', 'product_category.sample_Amout', 'product_category.unit')
                ->where ('import.Active',1)
                ->leftJoin('product_category', 'import.testing_code', 'product_category.testing_code')
                ->orderBy('created_at','desc')->get();
        
                session()->put(['title'=> 'Danh Sách Mẫu Chờ Kiểm']);

            
        
                return view('pages.Import.list',['datas' => $datas,'category' => $category ]);
        }
    

        public function store (Request $request) {
                $validator = Validator::make($request->all(), [
                        'imoported_amount' => 'required|numeric|gt:0', 
                        'batch_no' => 'required|string|min:5',
                        'experted_date' => 'required',
                        'stage' => 'required',
                ], [
                        'imoported_amount.required' => 'Vui lòng nhập số lượng mẫu',
                        'imoported_amount.numeric' => 'Số lượng mẫu là kiểu số',
                        'imoported_amount.gt' => 'Số lượng mẫu phải lớn hơn 0',

                        'batch_no.min' => 'Số lô phải có ít nhất min ký tự',
                        'batch_no.required' => 'Vui lòng nhập số lô',

                        'experted_date.required' => 'Vui lòng chọn ngày trả kết quả',

                        'stage.required' => 'Vui lòng nhập công đoạn',
                ]);
               

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
                }


                DB::table('import')->insert([
                        'testing_code' => $request->testing_code,
                        'imoported_amount' => $request->imoported_amount,
                        'stage'  => $request->stage,
                        'experted_date'  => $request->experted_date,
                        'batch_no' => $request->batch_no,
                        'prepareBy' => session('user')['fullName'] ?? 'Admin',
                        'created_at' => now(),
                ]);
                return redirect()->back()->with('success', 'Đã thêm thành công!');    
        }

        public function update(Request $request){
               
                $validator = Validator::make($request->all(), [
                        'imoported_amount' => 'required|numeric|gt:0', 
                        'batch_no' => 'required|string|min:5',
                        'experted_date' => 'required',
                        'stage' => 'required',
                ], [
                        'imoported_amount.required' => 'Vui lòng nhập số lượng mẫu',
                        'imoported_amount.numeric' => 'Số lượng mẫu là kiểu số',
                        'imoported_amount.gt' => 'Số lượng mẫu phải lớn hơn 0',
                        'batch_no.min' => 'Số lô phải có ít nhất min ký tự',
                        'batch_no.required' => 'Vui lòng nhập số lô',
                        'experted_date.required' => 'Vui lòng chọn ngày trả kết quả',
                        'stage.required' => 'Vui lòng nhập công đoạn',
                ]);
                
                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
                } 
                
                 DB::table('import')->where('id', $request->id)->update([
                        
                       
                        'imoported_amount' => $request->imoported_amount,
                        'stage'  => $request->stage,
                        'experted_date'  => $request->experted_date,
                        'batch_no' => $request->batch_no,
                        'prepareBy' => session('user')['fullName'] ?? 'Admin',
                        'updated_at' => now(),
                ]);
                return redirect()->back()->with('success', 'Đã thêm thành công!');   
        }

        public function deActive(string|int $id){
                
               DB::table('import')->where('id', $id)->update([
                        'isActive' => 0,
                        'prepareBy' => session('user')['fullName'],
                        'updated_at' => now(), 
                ]);
                return redirect()->back()->with('success', 'Vô Hiệu Hóa thành công!');
        }
}
