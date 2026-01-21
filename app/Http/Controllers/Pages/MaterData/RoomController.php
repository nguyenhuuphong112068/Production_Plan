<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RoomController extends Controller
{
     
         public function index(){
                $datas = DB::table('room')->where('deparment_code', session ('user') ['production_code'])->orderBy('stage_code', 'asc')->orderBy('order_by', 'asc')->get();
                $stages = DB::table('stages')->get();
                $stage_groups = DB::table('stage_groups')->get();
                session()->put(['title'=> 'DỮ LIỆU GỐC - PHÒNG SẢN XUẤT']);
                return view('pages.materData.room.list',[
                        'datas' => $datas,
                        'stages' => $stages,
                        'stage_groups' => $stage_groups
                ]);
        }
    

        public function store (Request $request) {
              

                $validator = Validator::make($request->all(), [
                        'code' => 'required|unique:room,code',
                        'name' => 'required|unique:room,name',
                        'stage_code' => 'required',
                        'production_group' => 'required',
                        'main_equiment_name' => 'required',
                        'capacity' => 'required',
                ],[
                        'code.required' => 'Vui lòng nhập nhập mã phòng sản xuất',
                        'code.unique' => 'Mã sản phẩm đã tồn tại trong hệ thống',
                        'name.required' => 'Vui lòng nhập tên phòng sản xuất',
                        'name.required' => 'Tên Phòng đã tồn tại trong hệ thống',
                        'stage_code.required' => 'Vui lòng chọn công đoạn',
                        'production_group.required' => 'Vui lòng nhập tổ quản lý',
                        'main_equiment_name.required' => 'Vui lòng nhập tên thiết bị chính ',
                        'capacity.required' => 'Vui lòng nhập công suất máy',
                        
                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
                }

                $stage_code = DB::table('stages')->where ('code', $request->stage_code)->pluck('name');
                $order_by =  DB::table('room')->where ('stage_code', $request->stage_code)->count('name');
                
                DB::table('room')->insert([
                        'order_by' =>  $order_by??0 + 1,
                        'code' => $request->code,
                        'name' => $request->name,
                        'stage'=>  $stage_code[0],
                        'stage_code' => $request->stage_code,
                        'production_group' => $request->production_group,
                        'deparment_code' => session('user')['production_code'] ,
                        'active' => true,
                        'prepareBy' => session('user')['fullName'] ,
                        'created_at' => now(),
                ]);
                return redirect()->back()->with('success', 'Đã thêm thành công!');    
        }

        public function update(Request $request){
               
                $validator = Validator::make($request->all(), [
                        //'name' => 'required',
                        'main_equiment_name' => 'required',
                        'capacity' => 'required',
                        //'stage_code' => 'required',
                        //'production_group' => 'required',
                ],[
                        //'code.required' => 'Vui lòng nhập mã phòng sản xuất',
                        //'code.unique' => 'Mã sản phẩm đã tồn tại trong hệ thống',
                        //'name.required' => 'Vui lòng nhập tên phòng sản xuất',
                        // 'name.required' => 'Tên Phòng đã tồn tại trong hệ thống',
                        // 'stage_code.required' => 'Vui lòng chọn công đoạn',
                        // 'production_group.required' => 'Vui lòng nhập tổ quản lý',
                        'main_equiment_name.required' => 'Vui lòng nhập tên thiết bị chính ',
                        'capacity.required' => 'Vui lòng nhập công suất máy',
                        
                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
                }

                //$stage_code = DB::table('stages')->where ('code', $request->stage_code)->pluck('name');
                //$order_by =  DB::table('room')->where ('stage_code', $request->stage_code)->count('name');
                
                DB::table('room')->where('code', $request->code)->update([
                        
                        'main_equiment_name' => $request->main_equiment_name,
                        'capacity' => $request->capacity,
                        
                        'prepareBy' => session('user')['fullName'] ,
                        'updated_at' => now(),
                ]);
                return redirect()->back()->with('success', 'Cập nhật thành công!');
        }

        public function deActive(Request $request){
                
               DB::table('room')->where('id', $request->id)->update([
                        'active' => !$request->active,
                        'prepareBy' => session('user')['fullName'],
                        'updated_at' => now(), 
                ]);
                return redirect()->back()->with('success', 'Vô Hiệu Hóa thành công!');
        }
}
