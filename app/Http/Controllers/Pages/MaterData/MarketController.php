<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MarketController extends Controller
{
        public function index(){
               
                $datas = DB::table('market')->orderBy('name','asc')->get();
                session()->put(['title'=> 'DỮ LIỆU GỐC - THỊ TRƯỜNG']);
                $historyCounts = DB::table('market_history')->select('market_id', DB::raw('count(*) as total'))->groupBy('market_id')->get()->keyBy('market_id');
        return view('pages.materData.Market.list', ['datas' => $datas, 'historyCounts' => $historyCounts]);
        }
        
        public function store (Request $request) {
               
                $validator = Validator::make($request->all(), [
                        'code' => 'required|unique:Unit,code',
                        'name' => 'required|unique:Unit,name',
                ],[
                        'name.required' => 'Vui Lòng Nhập Thị Trường', 
                        'name.unique' => 'Thị Trường Đã Tồn Tại.',
                        'code.required' => 'Vui Lòng Nhập Thị Trường Tắt', 
                        'code.unique' => 'Thị Trường Đã Tồn Tại.',        
                ]);

                if ($validator->fails()) {
                    return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
                }
                
                DB::table('market')->insert([
                        'code' => $request->code,
                        'name' => $request->name,
                        'active' => true,
                        'created_by' => session('user')['fullName'],
                        'created_at' => now(),
                ]);
                return redirect()->back()->with('success', 'Đã thêm thành công!');    
        }

        public function update(Request $request){
                
                $validator = Validator::make($request->all(), [
                        'code' => 'required|unique:Unit,code',
                        'name' => 'required|unique:Unit,name',
                ],[
                        'name.required' => 'Vui Lòng Nhập Thị Trường', 
                        'name.unique' => 'Thị Trường Đã Tồn Tại.',
                        'code.required' => 'Vui Lòng Nhập Thị Trường Tắt', 
                        'code.unique' => 'Thị Trường Đã Tồn Tại.',        
                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
                } 

                //$oldData = DB::table('Unit')->where('id', $request->id)->first();

                $this->logHistory($request->id);
        DB::table('market')->where('id', $request->id)->update([
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
        $current = DB::table('market')->where('id', $id)->first();
        if ($current) {
            $data = (array) $current;
            $data['market_id'] = $data['id'];
            unset($data['id']);
            DB::table('market_history')->insert($data);
        }
    }

    public function history(Request $request)
    {
        $histories = DB::table('market_history')
            ->where('market_id', $request->id)
            ->orderBy('id', 'desc')
            ->get();
            
        $current = DB::table('market')->where('id', $request->id)->first();

        return response()->json([
            'current' => $current,
            'history' => $histories
        ]);
    }

}