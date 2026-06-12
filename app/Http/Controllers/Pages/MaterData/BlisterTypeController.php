<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BlisterTypeController extends Controller
{
        public function index(){
                $datas = DB::table('blister_type')->orderBy('name','asc')->get();
                session()->put(['title'=> 'DỮ LIỆU GỐC - LOẠI MÁY ÉP VỈ']);
                $historyCounts = DB::table('blister_type_history')->select('blister_type_id', DB::raw('count(*) as total'))->groupBy('blister_type_id')->get()->keyBy('blister_type_id');
        return view('pages.materData.BlisterType.list', ['datas' => $datas, 'historyCounts' => $historyCounts]);
        }

        public function store (Request $request) {
        
                $validator = Validator::make($request->all(), [
                    'name' => 'required|unique:blister_type,name',
                    'code' => 'required|integer',
                ],[
                    'name.required' => 'Vui Lòng Nhập Loại Máy Ép Vỉ', 
                    'name.unique' => 'Loại Máy Ép Vỉ đã tồn tại.',
                    'code.required' => 'Vui lòng chọn Mã (Code).',
                ]);

                if ($validator->fails()) {
                    return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
                }
                
                DB::table('blister_type')->insert([
                        'name' => $request->name,
                        'code' => $request->code,
                        'active' => true,
                        'created_by' => session('user')['fullName'] ?? 'Admin',
                        'created_at' => now(),
                ]);
                return redirect()->back()->with('success', 'Đã thêm thành công!');    
        }

        public function update(Request $request){
                
                $validator = Validator::make($request->all(), [
                    'name' => 'required',
                    'code' => 'required|integer',
                ],[
                    'name.required' => 'Vui Lòng Nhập Loại Máy Ép Vỉ',
                    'code.required' => 'Vui lòng chọn Mã (Code).'
                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
                } 

                $this->logHistory($request->id);
        DB::table('blister_type')->where('id', $request->id)->update([
                        'name' => $request->name,
                        'code' => $request->code,
                        'created_by' => session('user')['fullName'] ?? 'Admin',
                        'updated_at' => now(),
                ]);
                
                return redirect()->back()->with('success', 'Cập nhật thành công!');
        }

        public function deActive(Request $request){
            $blister_type = DB::table('blister_type')->where('id', $request->id)->first();
            if ($blister_type) {
                $this->logHistory($request->id);
        DB::table('blister_type')->where('id', $request->id)->update([
                    'active' => !$blister_type->active,
                    'updated_at' => now()
                ]);
            }
            return redirect()->back()->with('success', 'Cập nhật trạng thái thành công!');
        }

    public function logHistory($id)
    {
        $current = DB::table('blister_type')->where('id', $id)->first();
        if ($current) {
            $data = (array) $current;
            $data['blister_type_id'] = $data['id'];
            unset($data['id']);
            DB::table('blister_type_history')->insert($data);
        }
    }

    public function history(Request $request)
    {
        $histories = DB::table('blister_type_history')
            ->where('blister_type_id', $request->id)
            ->orderBy('id', 'desc')
            ->get();
            
        $current = DB::table('blister_type')->where('id', $request->id)->first();

        return response()->json([
            'current' => $current,
            'history' => $histories
        ]);
    }

}