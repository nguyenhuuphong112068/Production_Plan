<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SpecificationController extends Controller
{
            public function index(){
               
                $datas = DB::table('specification')->orderBy('name','asc')->get();

                session()->put(['title'=> 'Dữ Liệu Gốc Qui Cách']);
                return view('pages.materData.Specification.list',['datas' => $datas]);
        }

                public function store (Request $request) {
              
                $validator = Validator::make($request->all(), [
                        'name' => 'required|unique:specification,name',
                ],[
                        'name.required' => 'Vui Lòng Nhập Qui Cách', 
                        'name.unique' => 'Qui Cách Đã Tồn Tại',
                            
                ]);

                if ($validator->fails()) {
                    return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
                }
                
                DB::table('specification')->insert([
                        'name' => $request->name,
                        'created_by' => session('user')['fullName'],
                        'created_at' => now(),
                ]);
                return redirect()->back()->with('success', 'Đã thêm thành công!');    
        }

        public function update(Request $request){
                
                $validator = Validator::make($request->all(), [
                        'name' => 'required|unique:specification,name',
                ],[
                        'name.required' => 'Vui Lòng Nhập Đơn Vị Tính', 
                        'name.unique' => 'Đơn Vị Tính Đã Tồn Tại.',
                            
                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
                } 

                //$oldData = DB::table('specification')->where('id', $request->id)->first();

                DB::table('specification')->where('id', $request->id)->update([
                        
                        'name' => $request->name,
                        'created_by' => session('user')['fullName'] ,
                        'updated_at' => now(),
                ]);

                //AuditTrialController::log('Update',"analyst" , $request->id ,  $oldData->groupName, $request->groupName);
                
                return redirect()->back()->with('success', 'Cập nhật thành công!');
        }

}
