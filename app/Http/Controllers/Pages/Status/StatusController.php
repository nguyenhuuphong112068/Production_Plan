<?php

namespace App\Http\Controllers\Pages\Status;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StatusController extends Controller
{
        protected array $stage = [
                        'Cân Nguyên Liệu' => 'Cân',
                        'Pha Chế' => 'PC',
                        'Trộn Hoàn Tất'=> 'THT',
                        'Định Hình' => "ĐH",
                        'Bao Phim' => 'BP',
                        'ĐGSC-ĐGTC' =>'ĐGSC-ĐGTC'
        ];

        public function show(){
                $production =  session('user')['production_code']??"PXV1";

                $now = Carbon::now();

                $general_notication = DB::table('room_status_notification')
                        ->where ('deparment_code', $production)
                        ->where ('durability', '>=' , $now)
                        ->orderBy('id')->first();

                $datas = DB::table('room')
                        ->leftJoin('stage_plan', function ($join) use ($now) {
                                $join->on('room.id', '=', 'stage_plan.resourceId')
                                ->where('stage_plan.active', true)
                                ->where('stage_plan.finished', false)
                                ->where(function ($q) use ($now) {
                                        $q->whereRaw('? BETWEEN stage_plan.start AND stage_plan.end', [$now])
                                        ->orWhereRaw('? BETWEEN stage_plan.start_clearning AND stage_plan.end_clearning', [$now]);
                                });
                        })
                        ->leftJoin('plan_master', 'stage_plan.plan_master_id', '=', 'plan_master.id')
                        ->leftJoinSub(
                                DB::table('room_status as rs1')
                                ->select('rs1.room_id', 'rs1.sheet', 'rs1.step_batch',  'rs1.status', 'rs1.in_production', 'rs1.notification')
                                ->whereRaw('rs1.id = (SELECT MAX(rs2.id) FROM room_status rs2 WHERE rs2.room_id = rs1.room_id)'),
                                'rs', function ($join) {
                                $join->on('room.id', '=', 'rs.room_id');
                                }
                        )
                        ->where('room.deparment_code', $production)
                        ->select(
                                'room.stage_code',
                                'room.stage',
                                'room.production_group',	
                                'stage_plan.title',
                                'stage_plan.start',
                                'stage_plan.end',
                                'stage_plan.end',	
                                'stage_plan.title_clearning',
                                'stage_plan.start_clearning',
                                'stage_plan.end_clearning',
                                DB::raw("CONCAT(room.code,'-', room.name) as room_name"),
                                DB::raw("COALESCE(rs.status, 0) as status"),
                                DB::raw("COALESCE(rs.in_production, 'Không sản xuất') as in_production"),
                                DB::raw("COALESCE(rs.notification, 'NA') as notification"),
                                DB::raw("COALESCE(rs.sheet, '') as sheet"),
                                DB::raw("COALESCE(rs.step_batch, '') as step_batch")
                        )
                        ->orderBy('room.group_code')
                        ->orderBy('room.order_by')
                ->get();


                session()->put(['title'=> "TRANG THÁI PHÒNG SẢN XUẤT $production"]);
              
                return view('pages.status.dataTableShow',[
                        'datas' =>  $datas,
                        'production' =>  $production,
                        'stage' => $this->stage,
                        'general_notication' =>  $general_notication
                ]);
        }

        public function next(Request $request){
              
                if ($request->production == "PXV1"){
                     $production_code = "PXV2";
                }elseif ($request->production == "PXV2"){
                     $production_code = "PXVH";
                }elseif ($request->production == "PXVH"){
                     $production_code = "PXTN";
                }elseif ($request->production == "PXTN"){
                     $production_code = "PXDN";
                }else {
                        $production_code = "PXV1";
                }

                $request->session()->put('user', [
                        'production_code' => $production_code
                ]);
                                
                session()->put(['title'=> "TRANG THÁI PHÒNG SẢN XUẤT $production_code"]);
                // Nếu có redirect URL thì quay lại đó
                return redirect()->back();
        }

        public function index(){
               
                $production =  session('user')['production_code']??"PXV1";
                $now = Carbon::now();

                //dd ($datas);
                $general_notication = DB::table('room_status_notification')
                        ->where ('deparment_code', $production)
                        ->where ('durability', '>=' , now())
                        ->orderBy('id')->first();

                $datas = DB::table('room')
                        ->leftJoin('stage_plan', function ($join) use ($now) {
                                $join->on('room.id', '=', 'stage_plan.resourceId')
                                ->where('stage_plan.active', true)
                                ->where('stage_plan.finished', false)
                                ->where(function ($q) use ($now) {
                                        $q->whereRaw('? BETWEEN stage_plan.start AND stage_plan.end', [$now])
                                        ->orWhereRaw('? BETWEEN stage_plan.start_clearning AND stage_plan.end_clearning', [$now]);
                                });
                        })
                        ->leftJoin('plan_master', 'stage_plan.plan_master_id', '=', 'plan_master.id')
                        ->leftJoinSub(
                                DB::table('room_status as rs1')
                                ->select('rs1.room_id', 'rs1.sheet', 'rs1.step_batch',  'rs1.status', 'rs1.in_production', 'rs1.notification')
                                ->whereRaw('rs1.id = (SELECT MAX(rs2.id) FROM room_status rs2 WHERE rs2.room_id = rs1.room_id)'),
                                'rs', function ($join) {
                                $join->on('room.id', '=', 'rs.room_id');
                                }
                        )
                        ->where('room.deparment_code', $production)
                        ->select(
                                'room.stage_code',
                                'room.stage',
                                'room.production_group',	
                                'room.order_by',
                                'room.group_code',
                                'stage_plan.title',
                                'stage_plan.start',
                                'stage_plan.end',
                                'stage_plan.end',	
                                'stage_plan.title_clearning',
                                'stage_plan.start_clearning',
                                'stage_plan.end_clearning',
                                DB::raw("CONCAT(room.code,'-', room.name) as room_name"),
                                DB::raw("COALESCE(rs.status, 0) as status"),
                                DB::raw("COALESCE(rs.in_production, 'Không sản xuất') as in_production"),
                                DB::raw("COALESCE(rs.notification, 'NA') as notification"),
                                DB::raw("COALESCE(rs.sheet, '') as sheet"),
                                DB::raw("COALESCE(rs.step_batch, '') as step_batch"),
                                DB::raw("COALESCE(rs.room_id, '') as room_id")
                        )
                        ->orderBy('room.group_code')
                        ->orderBy('room.order_by')
                ->get();

                $planWaitings = $this->getPlanWaiting ($production, 5);
                
                //dd ($planWaiting);

                session()->put(['title'=> "TRANG THÁI PHÒNG SẢN XUẤT $production"]);
              
                return view('pages.status.list',[
                        'datas' =>  $datas,
                        'production' =>  $production,
                        'planWaitings' =>  $planWaitings,
                        'stage' => $this->stage,
                        'general_notication' =>  $general_notication
                        
                ]);
        }


        public function getPlanWaiting($production, $stage_code){
                // 1️⃣ Xác định bảng stage_plan hoặc stage_plan_temp
                $stage_plan_table = session('fullCalender')['mode'] === 'offical'
                        ? 'stage_plan'
                        : 'stage_plan_temp';

                // 2️⃣ Lấy danh sách plan_waiting (chỉ 1 query)
                $plan_waiting = DB::table("$stage_plan_table as sp")
                        ->whereNotNull('sp.start')
                        ->where('sp.active', 1)
                        ->where('sp.finished', 0)
                        ->where('sp.stage_code', $stage_code)
                        ->where('sp.deparment_code', $production)
                        ->when(session('fullCalender')['mode'] === 'temp', function ($query) {
                        return $query->where('sp.stage_plan_temp_list_id', session('fullCalender')['stage_plan_temp_list_id']);
                        })
                        ->leftJoin('plan_master', 'sp.plan_master_id', '=', 'plan_master.id')
                        ->leftJoin('finished_product_category', 'sp.product_caterogy_id', '=', 'finished_product_category.id')
                        ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
                        ->select( 
                        'sp.order_by',         
                        'plan_master.batch',
                        'product_name.name'
                        )
                        ->distinct()
                        ->orderBy('sp.order_by', 'asc')
                        ->get();
                      
                return $plan_waiting;
        }// đã có temp

        public function store (Request $request) {
               //dd ($request->all());
                $sheet = [
                        '0' => 'NA',
                        '1' => 'Đầu Ca',
                        '2' => 'Giữa Ca',
                        '3' => 'Cuối Ca',
                ];

                $step_batch = [
                        '0' => 'NA',
                        '1' => 'Đầu Lô',
                        '2' => 'Giữa Lô',
                        '3' => 'Cuối Lô',
                ];

                $validator = Validator::make($request->all(), [
                    'room_name' => 'required',
                    'in_production' => 'required',
                    'status' => 'required',
                    'notification'=> 'required',
                ],[
                    'room_name.required' => 'Chọn phòng sản xuất', 
                    'in_production.required' => 'Chọn sản phẩm đang sản xuất', 
                    'status.required' => 'Chọn trạng thái phòng sản xuất hiện tại.',  
                    'notification.required'=> 'Vui lòng nhập thông báo, Nếu không có nhập NA',   
                ]);

                if ($validator->fails()) {
                    return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
                }

                $room_code = explode ("-", $request->room_name)[0];
                $room_id =  DB::table('room')->where ('code', $room_code)->value ('id');

                
                DB::table('room_status')->insert([
                        'room_id' => $room_id,
                        'status' => $request->status,
                        'sheet' => $sheet[$request->status],
                        'step_batch' => $step_batch[$request->status],
                        'start' => $request->start,
                        'end' => $request->end,
                        'in_production' => $request->in_production,
                        'notification' => $request->notification,
                        'created_by' => session('user')['fullName'] ?? 'Admin',
                        'created_at' => now(),
                ]);
                return redirect()->back()->with('success', 'Đã thêm thành công!');    
        }

        public function getLastStatusRoom (Request $request){
                ob_clean();
                $result = DB::table('room_status')
                ->where('room_id', $request->room_id)
                ->orderByDesc('id')
                ->first();

                return response()->json([
                        'last_row' => $result
                ]);                
        }

        public function store_general_notification (Request $request){
                
                DB::table('room_status_notification')->insert([
                        'notification' => $request->notification,
                        'group_code' => 0,
                        'durability' => $request->durability??now(),
                        'deparment_code' => session('user')['production_code'],
                        'created_by' => session('user')['fullName'],
                        'created_at' => now(),
                ]);
                return redirect()->back()->with('success', 'Đã thêm thành công!');      
        }

}
