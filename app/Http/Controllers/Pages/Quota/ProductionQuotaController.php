<?php

namespace App\Http\Controllers\Pages\Quota;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Pages\Schedual\SchedualController;


class ProductionQuotaController extends Controller
{
        public function index(Request $request){

                $stage_code = $request->stage_code ?? 1;
                $production = session('user')['production_code'];

                $room = DB::table('room')
                        ->where('deparment_code', $production)
                        ->where('stage_code', $stage_code)
                        ->where('active', true)
                        ->get();
                
                // map stage -> column boolean trong intermediate_category
                if ($stage_code <= 6) {
                        
                        if ($stage_code == 1) { $stage_name = "weight_1"; }
                        elseif ($stage_code == 2) { $stage_name = "weight_2"; }
                        elseif ($stage_code == 3) { $stage_name = "prepering"; }
                        elseif ($stage_code == 4) { $stage_name = "blending"; }
                        elseif ($stage_code == 5) { $stage_name = "forming"; }
                        elseif ($stage_code == 6) { $stage_name = "coating"; }

                        $category = "intermediate_category";
                        $joinField = "intermediate_code";
                         

                        $datas = DB::table($category)
                        ->select(
                                "{$category}.{$joinField} as intermediate_code",
                                "{$category}.product_name_id",
                                "{$category}.batch_size",
                                "{$category}.unit_batch_size",
                                "{$category}.batch_qty",
                                "{$category}.unit_batch_qty",
                                DB::raw("'NA' as finished_product_code"),
                                'product_name.name as product_name',
                                'room.name as room_name',
                                'room.code as room_code',
                                'quota.room_id',
                                'quota.p_time',
                                'quota.m_time',
                                'quota.C1_time',
                                'quota.C2_time',
                                'quota.maxofbatch_campaign',
                                'quota.note',
                                'quota.prepared_by',
                                'quota.created_at',
                                'quota.id',
                                'quota.active',
                                'quota.tank'
                        )
                        ->where("{$category}.{$stage_name}", 1)
                        ->where("{$category}.active", true)
                        ->where("{$category}.deparment_code", $production)
                        // join product_name (sử dụng product_name.id = category.product_name_id)
                        ->leftJoin('product_name', 'product_name.id', '=', "{$category}.product_name_id")
                        // join quota: đảm bảo đồng thời stage_code và deparment_code trên quota khớp
                        ->leftJoin('quota', function ($join) use ($stage_code, $joinField, $production, $category) {
                                $join->on("{$category}.{$joinField}", '=', "quota.{$joinField}")
                                ->where('quota.stage_code', '=', $stage_code)
                                ->where('quota.deparment_code', '=', $production);
                        })
                        ->leftJoin('room', 'quota.room_id', '=', 'room.id')
                        // đặt room NULL lên trước (hoặc đổi DESC/ASC tuỳ muốn)
                        ->orderByRaw('ISNULL(room.name) DESC')
                        ->orderBy('room.name', 'asc')
                        ->get();

                

                } elseif ($stage_code == 7) {
                        $category = "finished_product_category";
                        $joinField = "finished_product_code";

                        $query = DB::table($category)
                        ->select(
                                "{$category}.{$joinField} as finished_product_code",
                                "{$category}.product_name_id",
                                "{$category}.batch_qty",
                                "{$category}.unit_batch_qty",
                                "{$category}.intermediate_code",
                                'product_name.name as product_name',
                                'room.name as room_name',
                                'room.code as room_code',
                                'quota.room_id',
                                'quota.p_time',
                                'quota.m_time',
                                'quota.C1_time',
                                'quota.C2_time',
                                'quota.maxofbatch_campaign',
                                'quota.note',
                                'quota.prepared_by',
                                'quota.created_at',
                                'quota.id',
                                'quota.active',
                                'quota.keep_dry'
                        )
                        ->leftJoin('product_name', 'product_name.id', '=', "{$category}.product_name_id")
                        ->leftJoin('quota', function ($join) use ($stage_code, $production, $category, $joinField) {
                                $join->on("{$category}.{$joinField}", '=', "quota.{$joinField}")
                                ->where('quota.stage_code', '=', $stage_code)
                                ->where('quota.deparment_code', '=', $production);
                        })
                        ->leftJoin('room', 'quota.room_id', '=', 'room.id')
                        ->where("{$category}.active", true)
                        ->where("{$category}.deparment_code", $production)
                        ->orderByRaw('ISNULL(room.name) DESC')
                        ->orderBy('room.name', 'asc');

                        $datas = $query->get();
                } else {
                        $datas = collect(); // an toàn nếu stage_code ngoài dự kiến
                }

                session()->put(['title' => 'Định Mức Sản Xuất']);

                
                return view('pages.quota.production.list', [
                        'datas' => $datas,
                        'stage_code' => $stage_code,
                        'room' => $room
                ]);
        }


        public function check_code_room_id(Request $request){
        
               
                $room_id = $request->room_id; 
                $intermediate_code = $request->intermediate_code; 
                $finished_product_code = $request->finished_product_code;

                $process_code = $intermediate_code . "_" . $finished_product_code . "_" . $room_id;

                $exists = DB::table('quota')
                        ->where('process_code', $process_code) // bỏ khoảng trắng
                        ->exists();
                
                return response()->json([
                        'exists' => $exists,
                ]);
        }

        public function store (Request $request) {
             
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
                                'note'=> $request->note??"NA",
                                'deparment_code'=>  session('user')['production_code'],
                                'prepared_by' => session('user')['fullName'],
                                'created_at' => now(),
                        ];
                }

                DB::table('quota')->insert($dataToInsert);
                
                $SchedualController = new SchedualController();
                return response()->json([
                        'plan' => $SchedualController->getPlanWaiting(session('user')['production_code'])
                ]);

                return redirect()->back()->with('success', 'Đã thêm thành công!');    
        }

        public function update(Request $request){
                //dd ($request->all());
                 $validator = Validator::make($request->all(), [
         
                        'p_time' => 'required|string',
                        'm_time' => 'required|string', 
                        'C1_time' => 'required|string',
                        'C2_time' =>  'required|string',
                        'maxofbatch_campaign' => 'required',
                ], [

                        'p_time.required' => 'Vui lòng nhập thời gian chuẩn bị',
                        'm_time.required' => 'Vui lòng nhập thời gian sản xuất',
                        'C1_time.required' => 'Vui lòng nhập thời gian vệ sinh câp I',
                        'C2_time.required' => 'Vui lòng nhập thời gian vệ sinh câp II',
                        'maxofbatch_campaign.required'=> 'Vui lòng nhập số lô tối đa',

                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
                } 
                
                DB::table('quota')->where('id', $request->id)->update([
         
                        'p_time'=> $request->p_time,
                        'm_time' => $request->m_time,
                        'C1_time' => $request->C1_time,
                        'C2_time'=> $request->C2_time,
                        'maxofbatch_campaign'=> $request->maxofbatch_campaign,
                        'note'=> $request->note,
                        'prepared_by' => session('user')['fullName'],
                        'updated_at' => now(),
                ]);
                return redirect()->back()->with('success', 'Cập nhật thành công!');
        }

        public function deActive(Request $request){
              
                $quota = DB::table('quota')->where('id', $request->id)->first ();
                if ( $quota->stage_code == 7) {
                        $quota_count = DB::table('quota')->where('finished_product_code', $quota->finished_product_code)->where('stage_code', $quota->stage_code)->count();  
                }else {
                        $quota_count = DB::table('quota')->where('intermediate_code', $quota->intermediate_code)->where('stage_code', $quota->stage_code)->count();  
                }
                
                if ($quota_count == 1){
                        return redirect()->back()->with('success', 'Hiện tại chỉ còn một định mức cho sản phẩm này, không đươc vô hiệu hóa!');
                }else {
                        DB::table('quota')->where('id', $request->id)->update([
                        'active' => !$request->active,
                        'prepared_by' => session('user')['fullName'],
                        'updated_at' => now(), 
                        ]);
                }
                return redirect()->back()->with('success', 'Vô Hiệu Hóa thành công!');
        }
        public function tank_keepDry(Request $request){
                DB::table('quota')
                        ->where('id', $request->id)
                        ->when($request->stage_code == 3 || $request->stage_code == 4, function ($query) use ($request) {
                        $query->update(['tank' => $request->checked == "false" ? 0 : 1]);
                        })
                        ->when($request->stage_code == 7, function ($query) use ($request) {
                        $query->update(['keep_dry' => $request->checked == "false" ? 0 : 1]);
                });
                return response()->json(['success' => true]);
        }

        public function updateTime(Request $request){
                DB::table('quota')
                        ->where('id', $request->id)
                        ->update([
                                $request->name => $request->time
                        ]);
                return response()->json(['success' => true]);
        }
}
