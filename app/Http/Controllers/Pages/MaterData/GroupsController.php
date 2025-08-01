<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
class GroupsController extends Controller
{
        public function index(){

                $datas = DB::table('groups')->where ('Active',1)->orderBy('created_at','desc')->get();
                session()->put(['title'=> 'Tổ Kiểm Nghiệm']);
           
                return view('pages.materData.Groups.list',['datas' => $datas]);
        }
    

        public function store (Request $request) {
                $validator = Validator::make($request->all(), [
                        'name' => 'required|string|max:255|unique:groups,name',
                        'shortName' => 'required|string|max:255|unique:groups,shortName',
                    
                ],[
                        'name.required' => 'Vui lòng nhập tên chỉ tiêu kiểm',
                        'shortName.required' => 'Vui lòng nhập tên viết tắt.',
                        'name.unique' => 'Chỉ tiêu kiểm đã tồn tại trong hệ thống.',
                        'shortName.unique' => 'Tên viết tắt đã tồn tại trong hệ thống.',
                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
                }

                DB::table('groups')->insert([
                        'name' => $request->name,
                        'shortName' => $request->shortName,
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
                ],[
                        'name.required' => 'Vui lòng nhập tên chỉ tiêu kiểm',
                        'shortName.required' => 'Vui lòng nhập tên viết tắt.',
                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
                } 

               DB::table('groups')->where('id', $request->id)->update([
                        'name' => $request->name,
                        'shortName' => $request->shortName,
                        'active' => true,
                        'prepareBy' => session('user')['fullName'],
                        'updated_at' => now(), 
                ]);
                return redirect()->back()->with('success', 'Cập nhật thành công!');
        }

        public function deActive(string|int $id){
          

               DB::table('groups')->where('id', $id)->update([
                        'active' => false,
                        'prepareBy' => session('user')['fullName'],
                        'updated_at' => now(), 
                ]);
                return redirect()->back()->with('success', 'Vô Hiệu Hóa thành công!');
        }
}
