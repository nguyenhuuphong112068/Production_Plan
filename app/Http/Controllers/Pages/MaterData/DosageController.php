<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DosageController extends Controller
{
        public function index(){

                $datas = DB::table('dosage')->orderBy('name','asc')->get();
                session()->put(['title'=> 'DỮ LIỆU GỐC - DẠNG BÀO CHẾ']);
                return view('pages.materData.Dosage.list',['datas' => $datas]);
        }

        public function store (Request $request) {
        
                $validator = Validator::make($request->all(), [
                    'name' => 'required|unique:dosage,name',
                ],[
                    'name.required' => 'Vui Lòng Nhập Dạng Bào Chế', 
                    'name.unique' => 'Dạng Bào Chế đã tồn tại.',       
                ]);

                if ($validator->fails()) {
                    return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
                }
                
                DB::table('dosage')->insert([
                        'name' => $request->name,
                        'active' => true,
                        'created_by' => session('user')['fullName'] ?? 'Admin',
                        'created_at' => now(),
                ]);
                return redirect()->back()->with('success', 'Đã thêm thành công!');    
        }

        public function update(Request $request){
                
                $validator = Validator::make($request->all(), [
                    'name' => 'required',
                ],[
                    'name.required' => 'Vui Lòng Nhập Dạng Bào Chế'    
                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
                } 

                //$oldData = DB::table('dosage')->where('id', $request->id)->first();

                DB::table('dosage')->where('id', $request->id)->update([
                        'name' => $request->name,
                        'created_by' => session('user')['fullName'] ?? 'Admin',
                        'updated_at' => now(),
                ]);

                //AuditTrialController::log('Update',"analyst" , $request->id ,  $oldData->groupName, $request->groupName);
                
                return redirect()->back()->with('success', 'Cập nhật thành công!');
        }

        
}
