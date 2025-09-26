<?php

namespace App\Http\Controllers\Pages\Schedual;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SchedualTempController extends Controller
{
        public function index(){    
                $datas = DB::table('stage_plan_temp_list')
                ->where ('deparment_code',session('user')['production_code'])
                ->orderBy('id','desc')->get();
        
                session()->put(['title'=> 'LỊCH SẢN XUẤT TẠM']);
        
                return view('pages.Schedual.temp.list',[
                        'datas' => $datas   
                ]);    
        } 

        public function store(Request $request){
            $production = session('user')['production_code'];

            $validator = Validator::make($request->all(), [
                'name' => 'required|unique:stage_plan_temp_list,name',
            ], [
                'name.required' => 'Vui Lòng Nhập Tên',
                'name.unique' => 'Tên Lịch đã tồn tại.',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
            }

            try {
                DB::beginTransaction();

                $stage_plan_temp_list = DB::table('stage_plan_temp_list')->insertGetId([
                    'name' => $request->name,
                    'deparment_code' => session('user')['production_code'],
                    'prepared_by' => session('user')['fullName'],
                    'created_at' => now(),
                ]);

                $plan_waiting = DB::table('stage_plan')
                    ->whereNull('stage_plan.start')
                    ->where('stage_plan.active', 1)
                    ->where('stage_plan.finished', 0)
                    ->where('stage_plan.deparment_code', $production)
                    ->get();

                $dataToInsert = [];

                foreach ($plan_waiting as $plan) {
                    $dataToInsert[] = [
                        'stage_plan_id' => $plan->id,
                        'stage_plan_temp_list_id' => $stage_plan_temp_list,
                        'plan_list_id' => $plan->plan_list_id,
                        'plan_master_id' => $plan->plan_master_id,
                        'product_caterogy_id' => $plan->product_caterogy_id,
                        'predecessor_code' => $plan->predecessor_code,
                        'code' => $plan->code,
                        'order_by' => $plan->order_by,
                        'stage_code' => $plan->stage_code,
                        'deparment_code' => $plan->deparment_code,
                        'created_date' => now(),
                    ];
                }

                if (!empty($dataToInsert)) {
                    DB::table('stage_plan_temp')->insert($dataToInsert);
                }

                DB::commit();

                return redirect()->back()->with('success', "Tạo Mới $request->name Thành Công!");
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Đã xảy ra lỗi: ' . $e->getMessage())->withInput();
            }
        }


        public function open(Request $request){
              
                session()->put('fullCalender', [
                    'mode' => "temp",
                    'stage_plan_temp_list_id' => $request->stage_plan_temp_list_id,
                ]);
                session()->put(['title'=> "LỊCH SẢN XUẤT TẠM THỜI - $request->name"]);
                return view('app');
        }



}
