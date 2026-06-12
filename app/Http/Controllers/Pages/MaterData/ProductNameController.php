<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductNameController extends Controller
{
       public function index(){

                $datas = DB::table('product_name')->orderBy('created_at','desc')->get();
                session()->put(['title'=> 'DỮ LIỆU GỐC - TÊN SẢN PHẨM']);
            
                $historyCounts = DB::table('product_name_history')->select('product_name_id', DB::raw('count(*) as total'))->groupBy('product_name_id')->get()->keyBy('product_name_id');
        return view('pages.materData.productName.list', ['datas' => $datas, 'historyCounts' => $historyCounts]);
        }


        public function store(Request $request){
                $validator = Validator::make($request->all(), [
                        'name' => 'required|string|max:255',
                        'shortName' => 'required|string|max:255',
                        'productType' => 'required|string|max:255',
                ], [
                        'name.required' => 'Vui lòng nhập tên sản phẩm',
                        'shortName.required' => 'Vui lòng nhập tên viết tắt.',
                        'productType.required' => 'Vui lòng nhập loại sản phẩm.',
                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
                }

                DB::table('product_name')->insert([
                        'name' => $request->name,
                        'shortName' => $request->shortName,
                        'productType' => $request->productType,
                        'deparment_code' => session('user')['production_code'],
                        'active' => true,
                        'prepareBy' => session('user')['fullName'],
                        'created_at' => now(),
                ]);

                return redirect()->back()->with('success', 'Đã thêm thành công!');
        }

        public function update(Request $request){
               
                $validator = Validator::make($request->all(), [
                        'name' => 'required|string|max:255',
                        'shortName' => 'required|string|max:255',
                        'productType' => 'required|string|max:255',
                ],[
                        'name.required' => 'Vui lòng nhập tên sản phẩm',
                        'shortName.required' => 'Vui lòng nhập tên viết tắt.',
                        'productType.required' => 'Vui lòng nhập loại sản phẩm.',
                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
                } 

               $this->logHistory($request->id);
        DB::table('product_name')->where('id', $request->id)->update([
                        'name' => $request->name,
                        'shortName' => $request->shortName,
                        'productType' => $request->productType,
                        'active' => true,
                        'prepareBy' => session('user')['fullName'],
                        'updated_at' => now(), 
                ]);
                return redirect()->back()->with('success', 'Cập nhật thành công!');
        }

        public function deActive(Request $request){
                
               $this->logHistory($request->id);
        DB::table('product_name')->where('id', $request->id)->update([
                        'Active' => !$request->active,
                        'prepareBy' => session('user')['fullName'],
                        'updated_at' => now(), 
                ]);
                return redirect()->back()->with('success', 'Vô Hiệu Hóa thành công!');
        }

    public function logHistory($id)
    {
        $current = DB::table('product_name')->where('id', $id)->first();
        if ($current) {
            $data = (array) $current;
            $data['product_name_id'] = $data['id'];
            unset($data['id']);
            DB::table('product_name_history')->insert($data);
        }
    }

    public function history(Request $request)
    {
        $histories = DB::table('product_name_history')
            ->where('product_name_id', $request->id)
            ->orderBy('id', 'desc')
            ->get();
            
        $current = DB::table('product_name')->where('id', $request->id)->first();

        return response()->json([
            'current' => $current,
            'history' => $histories
        ]);
    }

}