<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AnalystController extends Controller
{
     
         public function index(){
                $groups = DB::table('groups')->where('active', true)->get();
                $datas = DB::table('analyst')->where ('Active',1)->orderBy('created_at','desc')->get();
                session()->put(['title'=> 'Danh Sách Kiểm Nghiệm Viên']);
           
                return view('pages.materData.Analyst.list',['datas' => $datas, 'groups' => $groups]);
        }
    

        public function store (Request $request) {
                $validator = Validator::make($request->all(), [
                    'code' => 'required|string|size:5|unique:analyst,code',

                    'fullName' => 'required|string|max:100',

                    'groupName' => 'required'
                    
                ],[
                    'code.unique' => 'Mã số nhân viên đã tồn tại trong hệ thống.',    
                    'code.size' => 'Mã số nhân viên có 5 ký tự',

                    'fullName.required' => 'Vui lòng nhập tên kiểm nghiệm viên.',
                    'fullName.max' => 'Tên kiểm nghiệm viên không quá max ký tự.',

                    'groupName.required' => 'Vui lòng chọn Tổ.',

                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
                }

                DB::table('analyst')->insert([
                        'fullName' => $request->fullName,
                        'code' => $request->code,
                        'groupName' => $request->groupName,
                        'active' => true,
                        'prepareBy' => session('user')['fullName'] ?? 'Admin',
                        'created_at' => now(),
                ]);
                return redirect()->back()->with('success', 'Đã thêm thành công!');    
        }

        public function update(Request $request){
               
                $validator = Validator::make($request->all(), [
                    'groupName' => 'required'
                ],[
                    'groupName.required' => 'Vui lòng chọn Tổ.',
                ]);


                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
                } 

                $oldData = DB::table('analyst')->where('id', $request->id)->first();

                DB::table('analyst')->where('id', $request->id)->update([
                        'groupName' => $request->groupName,
                        'prepareBy' => session('user')['fullName'] ?? 'Admin',
                        'created_at' => now(),
                ]);

                AuditTrialController::log('Update',"analyst" , $request->id ,  $oldData->groupName, $request->groupName);
                
                return redirect()->back()->with('success', 'Cập nhật thành công!');
        }

        public function deActive(string|int $id){
                
               DB::table('analyst')->where('id', $id)->update([
                        'active' => false,
                        'prepareBy' => session('user')['fullName'],
                        'updated_at' => now(), 
                ]);
                return redirect()->back()->with('success', 'Vô Hiệu Hóa thành công!');
        }
}
