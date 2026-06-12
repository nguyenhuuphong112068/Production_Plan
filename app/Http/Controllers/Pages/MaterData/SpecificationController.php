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

                session()->put(['title'=> 'DỮ LIỆU GỐC - QUI CÁCH ĐÓNG GÓI']);
                $historyCounts = DB::table('specification_history')->select('specification_id', DB::raw('count(*) as total'))->groupBy('specification_id')->get()->keyBy('specification_id');
        return view('pages.materData.Specification.list', ['datas' => $datas, 'historyCounts' => $historyCounts]);
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

                $this->logHistory($request->id);
        DB::table('specification')->where('id', $request->id)->update([
                        
                        'name' => $request->name,
                        'created_by' => session('user')['fullName'] ,
                        'updated_at' => now(),
                ]);

                //AuditTrialController::log('Update',"analyst" , $request->id ,  $oldData->groupName, $request->groupName);
                
                return redirect()->back()->with('success', 'Cập nhật thành công!');
        }


    public function logHistory($id)
    {
        $current = DB::table('specification')->where('id', $id)->first();
        if ($current) {
            $data = (array) $current;
            $data['specification_id'] = $data['id'];
            unset($data['id']);
            DB::table('specification_history')->insert($data);
        }
    }

    public function history(Request $request)
    {
        $histories = DB::table('specification_history')
            ->where('specification_id', $request->id)
            ->orderBy('id', 'desc')
            ->get();
            
        $current = DB::table('specification')->where('id', $request->id)->first();

        return response()->json([
            'current' => $current,
            'history' => $histories
        ]);
    }

}