<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UnitController extends Controller
{
        public function index(){

                $datas = DB::table('Unit')->orderBy('name','asc')->get();
                session()->put(['title'=> 'DỮ LIỆU GỐC - ĐƠN VỊ']);
                return view('pages.materData.Unit.list',['datas' => $datas]);
        }

        public function store (Request $request) {
               
                $validator = Validator::make($request->all(), [
                        'code' => 'required|unique:Unit,code',
                        'name' => 'required|unique:Unit,name',
                ],[
                        'name.required' => 'Vui Lòng Nhập Đơn Vị Tính', 
                        'name.unique' => 'Đơn Vị Tính Đã Tồn Tại.',
                        'code.required' => 'Vui Lòng Nhập Đơn Vị Tính Tắt', 
                        'code.unique' => 'Đơn Vị Tính Đã Tồn Tại.',        
                ]);

                if ($validator->fails()) {
                    return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
                }
                
                DB::table('unit')->insert([
                        'code' => $request->code,
                        'name' => $request->name,
                        'active' => true,
                        'created_by' => session('user')['fullName'] ?? 'Admin',
                        'created_at' => now(),
                ]);
                return redirect()->back()->with('success', 'Đã thêm thành công!');    
        }

        public function update(Request $request){
                
                $validator = Validator::make($request->all(), [
                        'code' => 'required|unique:Unit,code',
                        'name' => 'required|unique:Unit,name',
                ],[
                        'name.required' => 'Vui Lòng Nhập Đơn Vị Tính', 
                        'name.unique' => 'Đơn Vị Tính Đã Tồn Tại.',
                        'code.required' => 'Vui Lòng Nhập Đơn Vị Tính Tắt', 
                        'code.unique' => 'Đơn Vị Tính Đã Tồn Tại.',        
                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
                } 

                //$oldData = DB::table('Unit')->where('id', $request->id)->first();

                DB::table('Unit')->where('id', $request->id)->update([
                        'code' => $request->code,
                        'name' => $request->name,
                        'active' => true,
                        'created_by' => session('user')['fullName'] ,
                        'updated_at' => now(),
                ]);

                //AuditTrialController::log('Update',"analyst" , $request->id ,  $oldData->groupName, $request->groupName);
                
                return redirect()->back()->with('success', 'Cập nhật thành công!');
        }
}
