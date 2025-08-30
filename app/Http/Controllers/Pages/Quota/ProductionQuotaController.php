<?php

namespace App\Http\Controllers\Pages\Quota;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductionQuotaController extends Controller
{
        public function index(Request $request ){
               
                $stage_code = $request->stage_code?? 1;
                $production = session('user')['production'];
      
                $datas = DB::table('quota')
                ->select(
                        'quota.id',
                        'quota.intermediate_code',
                        'quota.finished_product_code',
                        'quota.room_id',
                        'quota.p_time',
                        'quota.m_time',
                        'quota.C1_time',
                        'quota.C2_time',
                        'quota.stage_code',
                        'quota.maxofbatch_campaign',
                        'quota.deparment_code',
                        'quota.note',
                        'quota.active',
                        'quota.created_at',
                        'quota.prepared_by',
                        'room.name as room_name',
                        'room.code as room_code',
                        'finished_product_category.name as finished_product_name',
                        'intermediate_category.name as intermediate_name',
                        'intermediate_category.name as batch_qty',
                        'intermediate_category.name as unit_batch_qty'
                )
                ->where('quota.active', 1)->where('quota.stage_code', $stage_code)->where('quota.deparment_code', $production)
                ->leftJoin('room', 'quota.room_id', 'room.id')
                ->leftJoin('finished_product_category', 'quota.finished_product_code', '=', 'finished_product_category.finished_product_code')
                ->leftJoin('intermediate_category', 'quota.intermediate_code', '=', 'intermediate_category.intermediate_code')
                ->orderBy('quota.created_at', 'desc')
                ->get();

                $intermediate_category = DB::table('intermediate_category')->where ('active',1)->orderBy('name','asc')->get();
                $finished_product_category = DB::table('finished_product_category')->where ('active',1)->orderBy('name','asc')->get();
               
                $room = DB::table('room')->where('stage_code', $stage_code)->where('active', true)->get();
               
                session()->put(['title'=> 'Định Mức Sản Xuất']);
                return view('pages.quota.production.list',[

                        'datas' => $datas, 
                        'stage_code' => $stage_code,
                        'intermediate_category' => $intermediate_category,
                        'finished_product_category' => $finished_product_category,
                        'room' =>  $room
                ]);
        }

        public function store (Request $request) {
               ///dd ($request->all());
                $selectedRooms = $request->input('room_id');
              
                $validator = Validator::make($request->all(), [
                'intermediate_code' => 'required|string',
                'room_id'   => 'required|array',
                'room_id.*' => 'integer|exists:room,id',
                'p_time' => 'required|string',
                'm_time' => 'required|string', 
                'C1_time' => 'required|string',
                'C2_time' =>  'required|string',
                'maxofbatch_campaign' => 'required',

                ], [

                'intermediate_code.required' => 'Vui lòng chọn sản phẩm.',
                'room_id.required' => 'Vui lòng chọn phòng sản xuất',
                'p_time.required' => 'Vui lòng nhập thời gian chuẩn bị',
                'm_time.required' => 'Vui lòng nhập thời gian sản xuất',
                'C1_time.required' => 'Vui lòng nhập thời gian vệ sinh câp I',
                'C2_time.required' => 'Vui lòng nhập thời gian vệ sinh câp II',
                'maxofbatch_campaign.required'=> 'Vui lòng nhập số lô tối đa',

                ]);
        

                if ($validator->fails() && $request->stage_code <=6) {
                        return redirect()->back()->withErrors($validator, 'create_inter_Errors')->withInput();
                }
                if ($validator->fails() && $request->stage_code > 6) {
                        return redirect()->back()->withErrors($validator, 'create_finished_Errors')->withInput();
                }

                if ($request->stage_code <=6){
                        $process_code = $request->intermediate_code ."_NA";
                        $finished_product_code = "NA";
                }else{
                        $process_code = $request->intermediate_code ."_". $request->finished_product_code;
                        $finished_product_code = $request->finished_product_code;                
                }
                $dataToInsert = [];
                 foreach ($selectedRooms as $selectedRoom) {
                        $dataToInsert[] = [
                                'process_code' => $process_code ."_". $selectedRoom,
                                'intermediate_code' => $request->intermediate_code,                       
                                'finished_product_code' => $finished_product_code,
                                'room_id'=> $selectedRoom,
                                'p_time'=> $request->p_time,
                                'm_time' => $request->m_time,
                                'C1_time' => $request->C1_time,
                                'C2_time'=> $request->C2_time,
                                'stage_code'=> $request->stage_code,
                                'maxofbatch_campaign'=> $request->maxofbatch_campaign,
                                'note'=> $request->note,
                                'deparment_code'=>  session('user')['production'],
                                'prepared_by' => session('user')['fullName'],
                                'created_at' => now(),
                        ];
                }

                DB::table('quota')->insert($dataToInsert);


                return redirect()->back()->with('success', 'Đã thêm thành công!');    
        }
}
