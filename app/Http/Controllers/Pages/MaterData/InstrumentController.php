<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InstrumentController extends Controller
{
     
         public function index(){

                $groups = DB::table('groups')->where('active', true)->get();

                $datas = DB::table('Instrument')
                ->select('Instrument.*', 'groups.name as groupName')
                ->where ('Instrument.Active',1)
                ->leftJoin('groups', 'Instrument.belongGroup_id', '=', 'groups.id')
                ->orderBy('created_at','desc')->get();
                session()->put(['title'=> 'Thiết Bị Kiểm Nghiệm']);
           
                return view('pages.materData.Instrument.list',['datas' => $datas, 'groups' => $groups]);
        }
    

        public function store (Request $request) {
                $validator = Validator::make($request->all(), [
                        'code' => 'required|string|min:5|max:255|unique:instrument,code',
                        'name' => 'required|string|max:255|unique:Instrument,name',
                        'shortName' => 'required|string|max:255|unique:Instrument,shortName',
                        'belongGroup_id' => 'required',
                        'instrument_type' => 'required',
                ],[
                        'code.unique' => 'Mã sản phẩm đã tồn tại trong hệ thống.',
                        'name.required' => 'Vui lòng nhập tên chỉ tiêu kiểm',
                        'shortName.required' => 'Vui lòng nhập tên viết tắt.',
                        'name.unique' => 'Chỉ tiêu kiểm đã tồn tại trong hệ thống.',
                        'shortName.unique' => 'Tên viết tắt đã tồn tại trong hệ thống.',
                        'belongGroup_id.required' => 'Vui lòng chọn tổ quản lý',
                        'instrument_type.required' => 'Vui lòng nhập loại thiết bị'
                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
                }

                DB::table('instrument')->insert([
                        'code' => $request->code,
                        'name' => $request->name,
                        'shortName' => $request->shortName,
                        'belongGroup_id' => $request->belongGroup_id,
                        'instrument_type' => $request->instrument_type,
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
                        'belongGroup_id' => 'required',
                        'instrument_type' => 'required',
                ],[
                        'name.required' => 'Vui lòng nhập tên chỉ tiêu kiểm',
                        'shortName.required' => 'Vui lòng nhập tên viết tắt.',
                        'belongGroup_id.required' => 'Vui lòng chọn tổ quản lý',
                        'instrument_type.required' => 'Vui lòng nhập loại thiết bị'
                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
                } 
                
               DB::table('Instrument')->where('id', $request->id)->update([
                        'code' => $request->code,
                        'name' => $request->name,
                        'shortName' => $request->shortName,
                        'belongGroup_id' => $request->belongGroup_id,
                        'instrument_type' => $request->instrument_type,
                        'active' => true,
                        'prepareBy' => session('user')['fullName'],
                        'updated_at' => now(), 
                ]);
                return redirect()->back()->with('success', 'Cập nhật thành công!');
        }

        public function deActive(string|int $id){
                
               DB::table('Instrument')->where('id', $id)->update([
                        'active' => false,
                        'prepareBy' => session('user')['fullName'],
                        'updated_at' => now(), 
                ]);
                return redirect()->back()->with('success', 'Vô Hiệu Hóa thành công!');
        }
}
