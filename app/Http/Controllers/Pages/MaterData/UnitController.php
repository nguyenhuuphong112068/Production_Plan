<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UnitController extends Controller
{
        public function index(){

                $datas = DB::table('unit')->orderBy('name','asc')->get();
                session()->put(['title'=> 'DỮ LIỆU GỐC - ĐƠN VỊ']);
                $historyCounts = DB::table('unit_history')->select('unit_id', DB::raw('count(*) as total'))->groupBy('unit_id')->get()->keyBy('unit_id');
        return view('pages.materData.Unit.list', ['datas' => $datas, 'historyCounts' => $historyCounts]);
        }

        public function store (Request $request) {
               
                $validator = Validator::make($request->all(), [
                        'code' => 'required|unique:unit,code',
                        'name' => 'required|unique:unit,name',
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
                        'code' => 'required|unique:unit,code',
                        'name' => 'required|unique:unit,name',
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

                $this->logHistory($request->id);
        DB::table('unit')->where('id', $request->id)->update([
                        'code' => $request->code,
                        'name' => $request->name,
                        'active' => true,
                        'created_by' => session('user')['fullName'] ,
                        'updated_at' => now(),
                ]);

                //AuditTrialController::log('Update',"analyst" , $request->id ,  $oldData->groupName, $request->groupName);
                
                return redirect()->back()->with('success', 'Cập nhật thành công!');
        }

    public function logHistory($id)
    {
        $current = DB::table('unit')->where('id', $id)->first();
        if ($current) {
            $data = (array) $current;
            $data['unit_id'] = $data['id'];
            unset($data['id']);
            DB::table('unit_history')->insert($data);
        }
    }

    public function history(Request $request)
    {
        $histories = DB::table('unit_history')
            ->where('unit_id', $request->id)
            ->orderBy('id', 'desc')
            ->get();
            
        $current = DB::table('unit')->where('id', $request->id)->first();

        return response()->json([
            'current' => $current,
            'history' => $histories
        ]);
    }

}