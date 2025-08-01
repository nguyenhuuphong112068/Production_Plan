<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductNameController extends Controller
{
       public function index(){

                $datas = DB::table('product_name')->where ('Active',1)->orderBy('created_at','desc')->get();
                session()->put(['title'=> 'Tên Sản Phẩm']);
            
                return view('pages.materData.productName.list',['datas' => $datas]);
        }


        public function store(Request $request){
                $validator = Validator::make($request->all(), [
                        'name' => 'required|string|max:255',
                        'shortName' => 'required|string|max:255',
                        'productType' => 'required|string|max:255',
                        //'code' => 'required|string|min:5|max:255|unique:product_name,code',
                ], [
                        'name.required' => 'Vui lòng nhập tên sản phẩm',
                        'shortName.required' => 'Vui lòng nhập tên viết tắt.',
                        'productType.required' => 'Vui lòng nhập loại sản phẩm.',
                        //'code.required' => 'Vui lòng nhập mã sản phẩm.',
                        //'code.min' => 'Mã sản phẩm phải có ít nhất :min ký tự.',
                        //'code.unique' => 'Mã sản phẩm đã tồn tại trong hệ thống.',
                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
                }

                DB::table('product_name')->insert([
                        'name' => $request->name,
                        'shortName' => $request->shortName,
                        'productType' => $request->productType,
                        // 'code' => $request->code,
                        'active' => true,
                        'prepareBy' => session('user')['fullName'] ?? 'Admin',
                        'created_at' => now(),
                ]);

                return redirect()->back()->with('success', 'Đã thêm thành công!');
        }

        public function update(Request $request){
               
                $validator = Validator::make($request->all(), [
                        'name' => 'required|string|max:255',
                        'shortName' => 'required|string|max:255',
                        'productType' => 'required|string|max:255',
                        // 'code' => 'required|string|min:5|max:255',
                ],[
                        'name.required' => 'Vui lòng nhập tên sản phẩm',
                        'shortName.required' => 'Vui lòng nhập tên viết tắt.',
                        'productType.required' => 'Vui lòng nhập loại sản phẩm.',
                        // 'code.required' => 'Vui lòng nhập mã sản phẩm.',
                        // 'code.min' => 'Mã sản phẩm phải có ít nhất :min ký tự.',
                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
                } 

               DB::table('product_name')->where('id', $request->id)->update([
                        'name' => $request->name,
                        'shortName' => $request->shortName,
                        'productType' => $request->productType,
                        //'code' => $request->code,
                        'active' => true,
                        'prepareBy' => session('user')['fullName'],
                        'updated_at' => now(), 
                ]);
                return redirect()->back()->with('success', 'Cập nhật thành công!');
        }

        public function deActive(string|int $id){
                
               DB::table('product_name')->where('id', $id)->update([
                        'active' => false,
                        'prepareBy' => session('user')['fullName'],
                        'updated_at' => now(), 
                ]);
                return redirect()->back()->with('success', 'Vô Hiệu Hóa thành công!');
        }
}
