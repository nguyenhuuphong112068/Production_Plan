<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SourceMaterialController extends Controller
{
        public function index(){
                $datas = DB::table('source_material')->orderBy('name','asc')->get();
               
                session()->put(['title'=> 'DỮ LIỆU GỐC - NGUỒN NGUYÊN LIỆU CHÍNH']);
            
                $historyCounts = DB::table('source_material_history')->select('source_material_id', DB::raw('count(*) as total'))->groupBy('source_material_id')->get()->keyBy('source_material_id');
        return view('pages.materData.source_material.list', ['datas' => $datas, 'historyCounts' => $historyCounts]);
        }

        public function store(Request $request){
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'intermediate_code' => 'required',
            ], [
                'name.required' => 'Vui lòng nhập tên sản phẩm',
                'intermediate_code.required' => 'Vui lòng nhập mã bán thành phẩm',
            ]);

            // Check validate cơ bản
            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator, 'createErrors')
                    ->withInput();
            }

            // Check intermediate_code tồn tại & active
            $check_intermediate_code = DB::table('intermediate_category')
                ->where('intermediate_code', $request->intermediate_code)
                ->where('active', 1)
                ->exists();
            

            if (!$check_intermediate_code) {
                $validator->errors()->add(
                    'intermediate_code',
                    'Mã bán thành phẩm không tồn tại hoặc đã bị khóa'
                );

                return redirect()->back()
                    ->withErrors($validator, 'createErrors')
                    ->withInput();
            }

            // Insert dữ liệu
            DB::table('source_material')->insert([
                'name' => $request->name,
                'intermediate_code' => $request->intermediate_code,
                'active' => true,
                'prepared_by' => session('user')['fullName'],
                'created_at' => now(),
            ]);

            return redirect()->back()->with('success', 'Đã thêm thành công!');
        }



        public function update(Request $request){
            $validator = Validator::make($request->all(), [
                'name' => 'required',
               
            ], [
                'name.required' => 'Vui lòng nhập tên sản phẩm',
              
            ]);

            // Check validate cơ bản
            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator, 'updateErrors')
                    ->withInput();
            }


            // Log history
            $this->logHistory($request->id);

            // Insert dữ liệu
            DB::table('source_material')->where ('id',$request->id )->update([
                'name' => $request->name,
                
                'prepared_by' => session('user')['fullName'],
                'updated_at' => now(),
            ]);

            return redirect()->back()->with('success', 'Đã thêm thành công!');
        }


        public function deActive(Request $request){
          
               $this->logHistory($request->id);
        DB::table('source_material')->where('id', $request->id)->update([
                        'Active' => !$request->active,
                        'prepared_by' => session('user')['fullName'],
                        'updated_at' => now(), 
                ]);
                return response()->json([
                        'success' => true,
                        'active' => !$request->active
                ]);
                //return redirect()->back()->with('success', 'Vô Hiệu Hóa thành công!');
        }

    public function logHistory($id)
    {
        $current = DB::table('source_material')->where('id', $id)->first();
        if ($current) {
            $data = (array) $current;
            $data['source_material_id'] = $data['id'];
            unset($data['id']);
            DB::table('source_material_history')->insert($data);
        }
    }

    public function history(Request $request)
    {
        $histories = DB::table('source_material_history')
            ->where('source_material_id', $request->id)
            ->orderBy('id', 'desc')
            ->get();
            
        $current = DB::table('source_material')->where('id', $request->id)->first();

        return response()->json([
            'current' => $current,
            'history' => $histories
        ]);
    }

}