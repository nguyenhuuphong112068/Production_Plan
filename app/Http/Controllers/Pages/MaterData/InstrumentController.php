<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InstrumentController extends Controller
{
     
         public function index(){
                
                $rooms = DB::table ('room')->where ('deparment_code', session('user')['production_code'])->select ('id', 'name','code')->get();
                
                $datas = DB::table('instrument')
                ->where ('instrument.Active',1)
                ->orderBy('created_at','desc')->get();

                session()->put(['title'=> 'Dữ Liệu Gốc Thiết Bị Sản Xuất']);
           
                return view('pages.materData.Instrument.list',[
                        'datas' => $datas,
                        'rooms' => $rooms
                ]);
        }
    

        public function store (Request $request) {

               
                $validator = Validator::make($request->all(), [
                        'code' => 'required|unique:instrument,code',
                        'name' => 'required',
                        //'room_id' => 'required', 
                ],[
                        'code.required' => 'Vui lòng nhập mã thiết bị',
                        'code.unique' => 'Mã thiết bị đã tồn tại trong hệ thống.',
                        'name.required' => 'Vui lòng nhập tên thiết bị',
                        //'room_id.required' => 'Vui lòng chọn phòng sản xuất',
                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
                }

                DB::table('instrument')->insert([
                        'code' => $request->code,
                        'name' => $request->name,
                        //'room_id' => $request->shortName,
                        'active' => true,
                        'created_by' => session('user')['fullName'],
                        'created_at' => now(),
                ]);
                return redirect()->back()->with('success', 'Đã thêm thành công!');    
        }

        public function update(Request $request){
               
                $validator = Validator::make($request->all(), [
                        'name' => 'required',
                        //'room_id' => 'required', 
                ],[
                        'name.required' => 'Vui lòng nhập tên thiết bị',
                        //'room_id.required' => 'Vui lòng chọn phòng sản xuất',
                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
                } 
                
                DB::table('instrument')->where('id', $request->id)->update([
         
                        'name' => $request->name,
                        //'room_id' => $request->shortName,
                        'active' => true,
                        'created_by' => session('user')['fullName'],
                        'created_at' => now(),
                ]);
                return redirect()->back()->with('success', 'Cập nhật thành công!');
        }

        public function deActive(string|int $id){
                
               DB::table('Instrument')->where('id', $id)->update([
                        'active' => false,
                        'created_by' => session('user')['fullName'],
                        'updated_at' => now(), 
                ]);
                return redirect()->back()->with('success', 'Vô Hiệu Hóa thành công!');
        }
}
