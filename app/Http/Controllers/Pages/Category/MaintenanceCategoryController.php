<?php

namespace App\Http\Controllers\Pages\Category;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MaintenanceCategoryController extends Controller
{
        
        public function index(){
                
                $rooms = DB::table ('room')->where ('deparment_code', session('user')['production_code'])->select ('id', 'name','code')->get();
                
                $datas = DB::table('maintenance_category')
                ->select('maintenance_category.*', 'room.name as room_name', 'room.code as room_code')
                ->leftJoin('room','maintenance_category.room_id','room.id')
                ->where('maintenance_category.deparment_code', session('user')['production_code'])
                ->orderBy('maintenance_category.name','asc')
                ->get();
                

               
                //dd ($datas);
                
                session()->put(['title'=> 'DANH MỤC BẢO TRÌ - HIỆU CHUẨN']);
                //dd ($datas);
                return view('pages.category.maintenance.list',[
                        'datas' => $datas,
                        'rooms' => $rooms,
                        
                ]);
        }
    

        public function store (Request $request) {
                
                $selectedRooms = $request->input('room_id');

                $validator = Validator::make($request->all(), [
                        'code' => 'required|unique:maintenance_category,code',
                        'name' => 'required',
                        'room_id'   => 'required|array',
                        'room_id.*' => 'integer|exists:room,id',
                ],[
                        'code.required' => 'Vui lòng nhập mã thiết bị',
                        'code.unique' => 'Mã thiết bị đã tồn tại trong hệ thống.',
                        'name.required' => 'Vui lòng nhập tên thiết bị',
                        'room_id.required' => 'Vui lòng chọn phòng sản xuất',
                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
                }
                $dataToInsert = [];
                foreach ($selectedRooms as $selectedRoom) {
                        $dataToInsert[] = [
                                'code_room_id' => $request->code . "_" . $selectedRoom,
                                'code' => $request->code,
                                'name' => $request->name,
                                'room_id'=> $selectedRoom,
                                'quota'=> $request->quota,
                                'note'=> $request->note,
                                'is_HVAC'=> $request->is_HVAC == "on"?1:0,
                                'active' => true,
                                'deparment_code'=> session('user')['production_code'],
                                'created_by' => session('user')['fullName'],
                                'created_at' => now(),
                        ];
                }

                DB::table('maintenance_category')->insert($dataToInsert);

                return redirect()->back()->with('success', 'Đã thêm thành công!');    
        }
        public function check_code_room_id(Request $request){
                $roomId = $request->room_id; 
                $code   = $request->code;

                $codeRoomId = $code . "_" . $roomId;

                $exists = DB::table('maintenance_category')
                        ->where('code_room_id', $codeRoomId)
                        ->exists();

                return response()->json([
                        'exists'       => $exists,
                ]);


        }
        public function update(Request $request){
               
                $validator = Validator::make($request->all(), [
                        'quota' => 'required',   
                ],[
                        'quota.required' => 'Vui lòng nhập thời gian thực hiện',
                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
                } 
                
                DB::table('maintenance_category')->where('id', $request->id)->update([
         
                        'quota' => $request->quota,
                        'note' => $request->note,
                        'created_by' => session('user')['fullName'],
                        'updated_at' => now(),
                ]);
                return redirect()->back()->with('success', 'Cập nhật thành công!');
        }

        public function deActive(Request $request){
              //dd ($request->all());
               DB::table('maintenance_category')->where('id', $request->id)->update([
                        'Active' => !$request->active,
                        'created_by' => session('user')['fullName'],
                        'updated_at' => now(), 
                ]);
                return redirect()->back()->with('success', 'Vô Hiệu Hóa thành công!');
        }
}
