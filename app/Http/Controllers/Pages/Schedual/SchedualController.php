<?php

namespace App\Http\Controllers\Pages\Schedual;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Carbon\Carbon;

class SchedualController extends Controller
{       
        public function __construct() {
                $this->loadRoomAvailability();
        } 

        public function index (){
                session()->put(['title'=> 'LỊCH SẢN XUẤT']);
                return view('app');
        }

        protected function getEvents($production){

                $event_plans = DB::table('stage_plan')
                        ->leftJoin('plan_master','stage_plan.plan_master_id','=','plan_master.id')
                        ->leftJoin('finished_product_category','plan_master.product_caterogy_id','=','finished_product_category.id')
                        ->leftJoin('intermediate_category','finished_product_category.intermediate_code','=','intermediate_category.intermediate_code')
                        ->where('stage_plan.active', 1)
                        ->whereNotNull('stage_plan.start')
                        ->where('stage_plan.deparment_code', $production)
                        ->select(
                        'stage_plan.id',
                        'stage_plan.title',
                        'stage_plan.start',
                        'stage_plan.end',
                        'stage_plan.start_clearning',
                        'stage_plan.end_clearning',
                        'stage_plan.title_clearning',
                        'stage_plan.resourceId',
                        'stage_plan.plan_master_id',
                        'stage_plan.stage_code',
                        'stage_plan.finished',
                        'stage_plan.quarantine_time',
                        'intermediate_category.intermediate_code',
                        'plan_master.expected_date',
                        'plan_master.after_weigth_date',
                        'plan_master.before_weigth_date',
                        'plan_master.after_parkaging_date',
                        'plan_master.before_parkaging_date',
                        'plan_master.is_val'
                        )
                        ->selectRaw("
                        CASE 
                        WHEN stage_plan.stage_code IN (1,2) 
                                THEN CASE 
                                        WHEN intermediate_category.quarantine_time_unit = 1 
                                        THEN intermediate_category.quarantine_weight * 24
                                        ELSE intermediate_category.quarantine_weight
                                END
                        WHEN stage_plan.stage_code = 3 
                                THEN CASE 
                                        WHEN intermediate_category.quarantine_time_unit = 1 
                                        THEN intermediate_category.quarantine_preparing * 24
                                        ELSE intermediate_category.quarantine_preparing
                                END
                        WHEN stage_plan.stage_code = 4 
                                THEN CASE 
                                        WHEN intermediate_category.quarantine_time_unit = 1 
                                        THEN intermediate_category.quarantine_blending * 24
                                        ELSE intermediate_category.quarantine_blending
                                END
                        WHEN stage_plan.stage_code = 5 
                                THEN CASE 
                                        WHEN intermediate_category.quarantine_time_unit = 1 
                                        THEN intermediate_category.quarantine_forming * 24
                                        ELSE intermediate_category.quarantine_forming
                                END
                        WHEN stage_plan.stage_code = 6 
                                THEN CASE 
                                        WHEN intermediate_category.quarantine_time_unit = 1 
                                        THEN intermediate_category.quarantine_coating * 24
                                        ELSE intermediate_category.quarantine_coating
                                END
                        ELSE 0
                        END as quarantine_time_limit
                ")
                ->get();
                
                $events = collect();
                $groupedPlans = $event_plans->groupBy('plan_master_id');

                foreach ($groupedPlans as $plan_master_id => $plans) {
                $plans = $plans->sortBy('stage_code')->values();
                $material_source_id = DB::table('plan_master')
                        ->where('id', $plan_master_id)
                        ->pluck('material_source_id');

                for ($i = 0; $i < $plans->count(); $i++) {
                        $plan = $plans[$i];
                        $subtitle = null;
                       
                                if ($plan->stage_code <= 7){
                                        $color_event = '#46f905ff';
                                }elseif ($plan->stage_code == 8){
                                        $color_event = '#003A4F';
                                }else {
                                        $color_event = '#eb0cb3ff';
                                }
                                
                                // Lấy công đoạn trước (nếu có)
                                $prevPlan = $i > 0 ? $plans[$i-1] : null;

                                if ($plan->finished === 1) {
                                        $color_event = '#002af9ff';
                                } elseif ($plan->is_val === 1) {
                                        $color_event = '#40E0D0'; 
                                }
                                
                                // Nếu có công đoạn trước thì check biệt trữ
                                if ($prevPlan && $plan->stage_code >2 && $plan->stage_code < 7){
                                        $diffSeconds = (strtotime($plan->start) - strtotime($prevPlan->end))/ 3600; 
                                        if ($diffSeconds > $prevPlan->quarantine_time_limit) {
                                                $color_event = '#bda124ff';
                                                $subtitle = 'Quá Hạn Biệt Trữ: ' . $diffSeconds . "h/" . $prevPlan->quarantine_time_limit . "h";
                                        }
                                } 
                               
                                if($plan->stage_code === 1 && $plan->after_weigth_date > $plan->start && $plan->before_weigth_date < $plan->start){
                                        $color_event = '#f99e02ff';
                                        $subtitle = 'Nguyên Liệu Không Đáp Ứng: '. $plan->after_weigth_date . " - " . $plan->before_weigth_date;
                                } elseif($plan->stage_code === 7 && $plan->after_parkaging_date > $plan->start && $plan->before_parkaging_date < $plan->start){
                                        $color_event = '#f99e02ff';
                                        $subtitle = 'Bao Bì Không Đáp Ứng: '. $plan->after_parkaging_date . " - " . $plan->before_parkaging_date;
                                }
                                $room_source = null;
                                if ( $plan->stage_code >2 && $plan->stage_code < 7){
                                        $room_source = DB::table('room_source')->where('intermediate_code', $plan->intermediate_code)->where('source_id', $material_source_id)->where ('room_id', $plan->resourceId)->exists();
                                        if (!$room_source){
                                                $color_event = '#dc02f9ff'; 
                                                $subtitle = 'Nguồn NL Chưa Được Khai Báo Tại Phòng Sản Xuất';
                                }}

                                if ($plan->expected_date < $plan->end && $plan->stage_code < 9){
                                        $color_event = '#f90202ff';
                                        $subtitle = 'Không Đáp Ứng Ngày Cần Hàng: '. $plan->expected_date;
                                        if ($plan->stage_code == 8 ){
                                                $subtitle = 'Không Đáp Ứng Hạn Bảo Trì: '. $plan->expected_date;
                                        }                                      
                                }
                        
             
                         // Event chính
                        if ($plan->start_clearning && $plan->end_clearning && $plan->end_clearning !== "Pass") {
                        $events->push([
                               'plan_id' => $plan->id,
                                        'id' => "{$plan->id}-main",
                                        'title' => $plan->title . " " . $subtitle,
                                        'name' => $name ?? null,
                                        'batch' => $batch ?? null,
                                        'market'=> $market ?? null,
                                        'start' => $plan->start,
                                        'end' => $plan->end,
                                        'resourceId' => $plan->resourceId,
                                        'color' =>  $color_event,
                                        'plan_master_id'=> $plan->plan_master_id,
                                        'stage_code'=> $plan->stage_code,
                                        'is_clearning' => false,
                                        'finished' => $plan->finished,
                                        'room_source' => $room_source,
                        ]);
                        }
                        // Event vệ sinh
                        if ($plan->start_clearning && $plan->end_clearning && $plan->end_clearning !== "Pass") {
                                $events->push([
                                        'plan_id' => $plan->id,
                                        'id' => "{$plan->id}-cleaning",
                                        'title' => $plan->title_clearning ?? 'Vệ sinh',
                                        'start' => $plan->start_clearning,
                                        'end' => $plan->end_clearning,
                                        'resourceId' => $plan->resourceId,
                                        'color' => '#a1a2a2ff',
                                        'plan_master_id'=> $plan->plan_master_id,
                                        'stage_code'=> $plan->stage_code,
                                        'is_clearning' => true,
                                        'finished' => $plan->finished
                                        ]);
                        }
                }
                }
                
                
                return $events;
                
        }
                // Hàm lấy quota
        protected function getQuota($production){
                return DB::table('quota')
                ->leftJoin('room', 'quota.room_id', '=', 'room.id')
                ->where('quota.active', 1)
                ->where('quota.deparment_code', $production)
                ->get()
                ->map(function ($item) {
                        $toSeconds = fn($time) => (($h = (int)explode(':',$time)[0]) * 3600) + ((int)explode(':',$time)[1] * 60);
                        $toTime = fn($seconds) => sprintf('%02d:%02d', floor($seconds / 3600), floor(($seconds % 3600) / 60));
                        $item->PM = $toTime($toSeconds($item->p_time) + $toSeconds($item->m_time));
                        return $item;
                });
        }

        protected function getPlanWaiting($production){
                $plan_waiting = DB::table('stage_plan')
                        ->whereNull('stage_plan.start')
                        ->where('stage_plan.active', 1)
                        ->where('stage_plan.deparment_code', $production)
                        ->leftJoin('plan_master', 'stage_plan.plan_master_id', '=', 'plan_master.id')
                        ->leftJoin('source_material', 'plan_master.material_source_id', '=', 'source_material.id')
                        ->leftJoin('finished_product_category', function($join) {
                                $join->on('stage_plan.product_caterogy_id', '=', 'finished_product_category.id')
                                ->where('stage_plan.stage_code', '<=', 7);
                        })
                        ->leftJoin('product_name', function($join) {
                                $join->on('finished_product_category.product_name_id', '=', 'product_name.id')
                                ->where('stage_plan.stage_code', '<=', 7);
                        })
                        ->leftJoin('maintenance_category', function($join) {
                                $join->on('stage_plan.product_caterogy_id', '=', 'maintenance_category.id')
                                ->where('stage_plan.stage_code', '=', 8);
                        })
                        ->leftJoin('market', 'finished_product_category.market_id', '=', 'market.id')
                        ->select(
                                'stage_plan.*',
                                'plan_master.batch',
                                'plan_master.expected_date',
                                'plan_master.is_val',
                                'plan_master.note',
                                'plan_master.level',
                                'plan_master.after_weigth_date',
                                'plan_master.before_weigth_date',
                                'plan_master.after_parkaging_date',
                                'plan_master.before_parkaging_date',
                                'plan_master.material_source_id',
                                'plan_master.only_parkaging',
                                'plan_master.percent_parkaging',
                                'market.code as market',
                                'source_material.name as source_material_name',
                                'finished_product_category.intermediate_code',
                                'finished_product_category.finished_product_code',
                                DB::raw("CASE 
                                        WHEN stage_plan.stage_code <= 7 THEN product_name.name
                                        ELSE maintenance_category.name END as name"),
                                DB::raw("CASE 
                                        WHEN stage_plan.stage_code = 8 THEN maintenance_category.code  END as instrument_code"),
                                DB::raw("CASE 
                                        WHEN stage_plan.stage_code = 8 THEN maintenance_category.is_HVAC END as is_HVAC")
                        )
                        ->orderBy('stage_plan.order_by', 'asc')
                ->get();

                // Lấy quota & maintenance_category
               
                $maintenance_category = DB::table('maintenance_category')
                ->where('active', 1)
                ->where('deparment_code', $production)
                ->get();

                $quota = $this->getQuota($production);

                // Ánh xạ room permission
                $plan_waiting = $plan_waiting->map(function ($plan) use ($quota, $maintenance_category) {
                if ($plan->stage_code <= 6) {
                        $matched = $quota->where('intermediate_code', $plan->intermediate_code)
                                        ->where('stage_code', $plan->stage_code);
                } elseif ($plan->stage_code == 7) {
                        $matched = $quota->where('finished_product_code', $plan->finished_product_code)
                                        ->where('stage_code', $plan->stage_code);
                } elseif ($plan->stage_code == 8) {
                        $room_id = $maintenance_category->where('code', $plan->instrument_code)->pluck('room_id');
                        $matched = $quota->whereIn('room_id', $room_id);
                } else {
                        $matched = collect();
                }

                $plan->permisson_room = $matched->pluck('code', "room_id")->unique();
                return $plan;
                });

                return $plan_waiting;
        }
        // Hàm lấy resources
        protected function getResources($production, $startDate, $endDate){
                $roomStatus = $this->getRoomStatistics($startDate, $endDate);
                $sumBatchQtyResourceId = $this->yield($startDate, $endDate, "resourceId");

                $statsMap = $roomStatus->keyBy('room_id');                
                $yieldMap = $sumBatchQtyResourceId->keyBy('resourceId');

                return DB::table('room')
                ->select('id', 'code', DB::raw("CONCAT(name, '-', code) as title"), 'stage','stage_code', 'production_group')
                ->where('active', 1)
                ->where('room.deparment_code', $production)
                ->orderBy('order_by', 'asc')
                ->get()
                ->map(function ($room) use ($statsMap, $yieldMap) {
                        $stat = $statsMap->get($room->id);
                        $yield = $yieldMap->get($room->id);
                        $room->busy_hours = $stat->busy_hours ?? 0;
                        $room->free_hours = $stat->free_hours ?? 0;
                        $room->total_hours = $stat->total_hours ?? 0;
                        $room->yield    = $yield->total_qty ?? 0;
                        $room->unit  = $yield->unit ?? '';
                        return $room;
                });
        }

        // Hàm view gọn hơn
        public function view(){
                //dd ("sa");
                $production = session('user')['production_code'];
                $events = $this->getEvents($production);
                $plan_waiting = $this->getPlanWaiting($production);
                $quota = $this->getQuota($production);
                $sumBatchByStage = $this->yield(now()->startOfWeek(), now()->endOfWeek(), "stage_code");
                $stageMap = DB::table('room')->pluck('stage_code','stage')->toArray();
                $resources = $this->getResources($production, now()->startOfWeek(), now()->endOfWeek());


                 return response()->json([
                        'title' => 'LỊCH SẢN XUẤT',
                        'user' => session('user'),
                        'events' => $events,
                        'resources' => $resources,
                        'plan' => $plan_waiting,
                        'quota' => $quota,
                        'sumBatchByStage' => $sumBatchByStage,
                        'stageMap' => $stageMap
                 ]);
        }

        public function getSumaryData(Request $request){

                $sumBatchByStage = $this->yield($request->start, $request->end, "stage_code");

                return response()->json([
                        'sumBatchByStage' => $sumBatchByStage,
                ]);
                

        }

        ////
        public function getInforSoure (Request $request) {

                $plan_master = DB::table('plan_master')
                        ->select('finished_product_category.intermediate_code', 'product_name.name as product_name', 'plan_master.material_source_id', 'source_material.name')
                        ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', '=', 'finished_product_category.id')
                        ->leftJoin('source_material','plan_master.material_source_id','source_material.id')
                        ->leftJoin('product_name','finished_product_category.product_name_id','product_name.id')
                        ->where('plan_master.id',$request->plan_master_id)
                ->first();
                
                return response()->json([
                        'sourceInfo' => $plan_master,
                ]);
        }

        public function confirm_source (Request $request) {
                try {
                        DB::table('room_source')->insert ([
                        'intermediate_code' =>  $request->intermediate_code,
                        'room_id' =>  $request->room_id,
                        'source_id' =>  $request->source_id,
                        'prepared_by' => session('user')['fullName'],
                        'created_at' => now()
                        ]);

                        $production = session('user')['production_code'];
                        $events = $this->getEvents($production);
                        return response()->json([
                                'events' => $events,
                        ]);
                 } catch (\Exception $e) {
                        DB::rollBack();
                        Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);
                        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
                }

        }
        
        public function store(Request $request) {
                
                DB::beginTransaction();
                try {
                        $products = collect($request->products);
                        $current_start = Carbon::parse($request->start);       
                        foreach ($products as $index => $product) {
                                if ($index === 0 && $product['stage_code'] !== 9) {
                                        if ($product['stage_code'] < 7) {
                                        $process_code = $product['intermediate_code'] . "_NA_" . $request->room_id;    
                                        } else if ($product['stage_code'] === 7) {
                                        $process_code = $product['intermediate_code'] . "_" . $product['finished_product_code'] . "_" . $request->room_id;                                            
                                        }
                                
                                        $quota = DB::table('quota')
                                        ->select(
                                                'room_id', 'p_time', 'm_time', 'C1_time', 'C2_time', 
                                                DB::raw('(TIME_TO_SEC(p_time)/3600) as p_time_hours'),
                                                DB::raw('(TIME_TO_SEC(m_time)/3600) as m_time_hours'),
                                                DB::raw('(TIME_TO_SEC(C1_time)/3600) as C1_time_hours'),
                                                DB::raw('(TIME_TO_SEC(C2_time)/3600) as C2_time_hours')
                                        )
                                        ->where('process_code', $process_code)
                                        ->first(); 
                                        
                                        $p_time_minutes  = toMinutes($quota->p_time);
                                        $m_time_minutes  = toMinutes($quota->m_time);
                                        $C1_time_minutes = toMinutes($quota->C1_time);
                                        $C2_time_minutes = toMinutes($quota->C2_time);    
                                }elseif ($index === 0 && $product['stage_code'] === 9) {
                                        $p_time_minutes  = 30;
                                        $m_time_minutes  = 60;
                                        $C1_time_minutes = 30;
                                        $C2_time_minutes = 60;  
                                }
                                if ($product['stage_code'] === 1) {
                                        $end_man = $current_start->copy()->addMinutes($p_time_minutes + $m_time_minutes);
                                        $end_clearning = $end_man->copy()->addMinutes($C2_time_minutes);
                                        $clearning_type = "VS-II";
                                }else {
                                        if ($products->count() === 1) {
                                                $end_man = $current_start->copy()->addMinutes($p_time_minutes + $m_time_minutes);
                                                $end_clearning = $end_man->copy()->addMinutes($C2_time_minutes);
                                                $clearning_type = "VS-II"; 
                                        } else {
                                                if ($index === 0) {
                                                $end_man = $current_start->copy()->addMinutes($p_time_minutes + $m_time_minutes);
                                                $end_clearning = $end_man->copy()->addMinutes($C1_time_minutes);
                                                $clearning_type = "VS-I";
                                                } else if ($index === $products->count() - 1) {
                                                $end_man = $current_start->copy()->addMinutes($p_time_minutes + $m_time_minutes);
                                                $end_clearning = $end_man->copy()->addMinutes($C2_time_minutes);
                                                $clearning_type = "VS-II";
                                                } else {
                                                $end_man = $current_start->copy()->addMinutes($m_time_minutes);
                                                $end_clearning = $end_man->copy()->addMinutes($C1_time_minutes);
                                                $clearning_type = "VS-I";
                                                }
                                        }
                                }

                                DB::table('stage_plan')
                                        ->where('id', $product['id'])
                                        ->update([
                                        'start'           => $current_start,
                                        'end'             => $end_man,
                                        'start_clearning' => $end_man,
                                        'end_clearning'   => $end_clearning,
                                        'resourceId'      => $request->room_id,
                                        'title'           => $product['stage_code'] ===9? ($product['title']. "-" . $product['batch'] ): ($product['name'] . "-" . $product['batch'] . "-" . $product['market']),
                                        'title_clearning' => $clearning_type,
                                        'schedualed'      => 1,
                                        'schedualed_by'   => session('user')['fullName'],
                                        'schedualed_at'   => now(),
                                        ]);

                                if ($product['stage_code'] === 1){
                                        $current_start = $current_start;
                                }else{
                                        $current_start = $end_clearning;
                                }
                        }
                        DB::commit();

                        $production = session('user')['production_code'];
                        $events = $this->getEvents($production);
                        $plan_waiting = $this->getPlanWaiting($production);
                        $sumBatchByStage = $this->yield($request->startDate, $request->endDate, "stage_code");

                        return response()->json([
                                'events' => $events,
                                'plan' => $plan_waiting,
                                'sumBatchByStage' => $sumBatchByStage,
                        ]); 
                        
                } catch (\Exception $e) {
                        DB::rollBack();
                        Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);
                        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
                }
                
        }

        public function store_maintenance (Request $request){
              
                DB::beginTransaction();
                try {
                $products = collect($request->products);
                $current_start = Carbon::parse($request->start);
                        if ($request->is_HVAC == true){
                                foreach ($products as $index => $product) {
                                if ($index === 0) {
                                        $quota = DB::table('maintenance_category')
                                                ->where('code', $product['instrument_code'])
                                                ->selectRaw('TIME_TO_SEC(quota) / 60 as quota_minutes')
                                                ->first();

                                        $execute_time_minutes = (int) ($quota->quota_minutes ?? 0);
                                        $end_man = $current_start->copy()->addMinutes($execute_time_minutes);
                                        $room_id = array_keys($product['permisson_room']);
                                }
                        
                                DB::table('stage_plan')
                                        ->where('id', $product['id'])
                                        ->update([
                                                'start'           => $current_start,
                                                'end'             => $end_man,                      
                                                'resourceId'      => $room_id[$index],
                                                'title'           => $product['name'] ,
                                                'schedualed'      => 1,
                                                'schedualed_by'   => session('user')['fullName'],
                                                'schedualed_at'   => now(),
                                        ]);
                                }

                        }else{

                                foreach ($products as $index => $product) {

                                        $quota = DB::table('maintenance_category')
                                                ->where('code', $product['instrument_code'])
                                                ->selectRaw('TIME_TO_SEC(quota) / 60 as quota_minutes')
                                                ->first();

                                        $execute_time_minutes = (int) ($quota->quota_minutes ?? 0);
                                        $end_man = $current_start->copy()->addMinutes($execute_time_minutes);
                                        $room_id = array_keys($product['permisson_room']);

                                        DB::table('stage_plan')
                                        ->where('id', $product['id'])
                                        ->update([
                                                'start'           => $current_start,
                                                'end'             => $end_man,                      
                                                'resourceId'      => $room_id[0], 
                                                'title'           => $product['name'] ,
                                                'schedualed'      => 1,
                                                'schedualed_by'   => session('user')['fullName'],
                                                'schedualed_at'   => now(),
                                        ]);
                                        $current_start = $end_man;
                                }  
                        }

                        DB::commit();
                } catch (\Exception $e) {
                        DB::rollBack();
                        Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);
                        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
                }
        }

        public function update(Request $request){
                

                $changes = $request->input('changes', []);

                try {
                        foreach ($changes as $change) {
                        // Tách id: "102-main" -> 102
                        $idParts = explode('-', $change['id']);
                        $realId = $idParts[0] ?? null;

                        if (!$realId) {
                                continue; // bỏ qua nếu id không hợp lệ
                        }

                        // Nếu là sự kiện vệ sinh (title chứa "VS-")
                        if (strpos($change['title'], "VS-") !== false) {
                                DB::table('stage_plan')
                                ->where('id', $realId)
                                ->update([
                                        'start_clearning' => $change['start'],
                                        'end_clearning'   => $change['end'],
                                        'resourceId'      => $change['resourceId'],
                                        'schedualed_by'   => session('user')['fullName'],
                                        'schedualed_at'   => now(),
                                ]);
                        } else {
                                DB::table('stage_plan')
                                ->where('id', $realId)
                                ->update([
                                        'start'           => $change['start'],
                                        'end'             => $change['end'],
                                        'resourceId'      => $change['resourceId'],
                                        'schedualed_by'   => session('user')['fullName'],
                                        'schedualed_at'   => now(),
                                ]);
                        }
                        }

                        $production = session('user')['production_code'];
                        $events = $this->getEvents($production);
                        $plan_waiting = $this->getPlanWaiting($production);
                        $sumBatchByStage = $this->yield($request->startDate, $request->endDate, "stage_code");

                        return response()->json([
                                'events' => $events,
                                'plan' => $plan_waiting,
                                'sumBatchByStage' => $sumBatchByStage,
                        ]); 

                

                } catch (\Exception $e) {
                        Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);
                        return response()->json(['error' => 'Lỗi hệ thống'], 500);
                }
        }

        public function deActive(Request $request){
                
                $items = collect($request->input('ids'));
                try {
                        
                        foreach ($items as $item) {
                        $rowId = explode('-', $item['id'])[0];   // lấy id trước dấu -
                        $stageCode = $item['stage_code'];
                        if ($stageCode <= 2) {
                                        // chỉ cóa cân k xóa các công đoạn khác
                                        DB::table('stage_plan')
                                        ->where('id', $rowId)
                                        ->where('stage_code', '=', $stageCode)
                                        ->update([
                                                'start'            => null,
                                                'end'              => null,
                                                'start_clearning'  => null,
                                                'end_clearning'    => null,
                                                'resourceId'       => null,
                                                'title'            => null,
                                                'title_clearning'  => null,
                                                'schedualed'       => 0,
                                                'schedualed_by'    => session('user')['fullName'],
                                                'schedualed_at'    => now(),
                                        ]);
                                        DB::table('room_status')
                                        ->where('stage_plan_id', $rowId)
                                        ->delete();

                        }else {

                                        $plan = DB::table('stage_plan')->where('id', $rowId)->first();
                                        // Update tất cả stage_plan theo rule
                                        DB::table('stage_plan')
                                        ->where('plan_master_id', $plan->plan_master_id)->where('stage_code', '>=', $stageCode)
                                        ->update([
                                                'start'            => null,
                                                'end'              => null,
                                                'start_clearning'  => null,
                                                'end_clearning'    => null,
                                                'resourceId'       => null,
                                                'title'            => null,
                                                'title_clearning'  => null,
                                                'schedualed'       => 0,
                                                'schedualed_by'    => session('user')['fullName'],
                                                'schedualed_at'    => now(),
                                        ]);

                                        // Xóa room_status theo các row này
                                        $affectedIds = DB::table('stage_plan')
                                        ->where('plan_master_id', $plan->plan_master_id)
                                        ->where('stage_code', '>=', $stageCode)
                                        ->pluck('id')
                                        ->toArray();

                                        DB::table('room_status')
                                        ->whereIn('stage_plan_id', $affectedIds)
                                        ->delete();
                        }

                        }

                
                } catch (\Exception $e) {
                        Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);
                        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
                }

                $production = session('user')['production_code'];
                $events = $this->getEvents($production);
                $plan_waiting = $this->getPlanWaiting($production);
                $sumBatchByStage = $this->yield($request->start, $request->end, "stage_code");

                return response()->json([
                                'events' => $events,
                                'plan' => $plan_waiting,
                                'sumBatchByStage' => $sumBatchByStage,
                ]);

        
        }

        public function deActiveAll(Request $request){
                try {

                        $ids = DB::table('stage_plan')
                        ->whereNotNull('start')
                        ->where('active', 1)
                        ->where('finished', 0)
                        ->pluck('id'); // chỉ lấy cột id

                if ($ids->isEmpty()) {
                        return null;
                }       
                        DB::table('stage_plan')
                                ->whereIn('id',  $ids)
                                ->update([
                                        'start' => null,
                                        'end' => null,
                                        'start_clearning' => null,
                                        'end_clearning' => null,
                                        'resourceId' => null,
                                        //'title' => null,
                                        'title_clearning' => null,
                                        'schedualed' => 0,
                                        'schedualed_by' =>  session('user')['fullName'],
                                        'schedualed_at' => now(),
                        ]);

                        DB::table('room_status')
                                ->whereIn('stage_plan_id',  $ids)
                                ->delete(); 
                        
                        $production = session('user')['production_code'];
                        $events = $this->getEvents($production);
                        $plan_waiting = $this->getPlanWaiting($production);
                        $sumBatchByStage = $this->yield($request->startDate, $request->endDate, "stage_code");

                        return response()->json([
                                'events' => $events,
                                'plan' => $plan_waiting,
                                'sumBatchByStage' => $sumBatchByStage,
                        ]);

                } catch (\Exception $e) {
                        Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]); 
                        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);      
                }
        }

        public function finished(Request $request){
                
                $id = explode('-', $request->input('id'))[0];
              
                try {
                        DB::table('stage_plan')
                                ->where('id', $id)
                                ->update([
                                        'yields' => $request->input('yields'),
                                        'finished'  => 1  
                        ]);  
                        DB::table('room_status')
                                ->where('stage_plan_id', $id)
                                ->delete();

                } catch (\Exception $e) {
                        Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]); 
                        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);      
                }

                $production = session('user')['production_code'];
                $events = $this->getEvents($production);
                
                return response()->json([
                        'events' => $events,
                ]);
        }

        public function addEventContent(int|string $id, Request $request){

                $oldData = DB::table('stage_plan')->where('id', $id)->first();
               
                try {
                        DB::table('stage_plan')
                        ->where('id', $request->id)
                        ->update([
                                'title' => $oldData->title . " - " .$request->note,
                        ]);


                } catch (\Exception $e) {
                        Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);
                        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);       
                }
        }

        public function updateOrder(Request $request) {
                $data = $request->input('updateOrderData'); // lấy đúng mảng

                $cases = [];
                $codes = [];

                foreach ($data as $item) {
                        $code = $item['code'];       // vì $item bây giờ là array thực sự
                        $orderBy = $item['order_by'];

                        $cases[$code] = $orderBy;    // dùng cho CASE WHEN
                        $codes[] = $code;            // dùng cho WHERE IN
                }
        

                $updateQuery = "UPDATE stage_plan SET order_by = CASE code ";
                foreach ($cases as $code => $orderBy) {
                        $updateQuery .= "WHEN '{$code}' THEN {$orderBy} ";
                }
                $updateQuery .= "END WHERE code IN ('" . implode("','", $codes) . "')";
        
                DB::statement($updateQuery);

        }

        public function createManualCampain(Request $request){
                $datas = $request->input ('data');
                $modeCreate = true;
                $firstCode = null;
               
                try {
                if ($datas && count($datas) > 0) {
                      
                        foreach ($datas as $data){ 
                                if ($data['campaign_code'] !== null){
                                        $modeCreate = false;
                                        $firstCode =  $data['campaign_code'];
                                 break; 
                        }}

                        if ($modeCreate === true && count($datas) > 1){
                                $firstCode = $datas[0]['predecessor_code'];
                                if ($firstCode === null) {$firstCode = "0_".$datas[0]['code'];}
                                $ids = collect($datas)->pluck('id')->toArray();
                                DB::table('stage_plan')
                                        ->whereIn('id', $ids)
                                        ->update([
                                        'campaign_code' => $firstCode
                                        ]);
                        }else { 
                              
                                DB::table('stage_plan')
                                        ->where('campaign_code', $firstCode)
                                        ->update([
                                        'campaign_code' => null
                                ]);
                        }
                        

                       
                }}  catch (\Exception $e) {
                        Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);
                        return response()->json(['error' => 'Lỗi hệ thống'], 500);
                }
        }

        public function createAutoCampain(){
              
                try {
                // Lấy toàn bộ stage_plan chưa hoàn thành và active
                DB::table('stage_plan') 
                        ->where('finished', 0)
                        ->where('start', null)
                        ->where('active', 1)
                        ->where('stage_code',">=", 3)
                ->update(['campaign_code' => null]);
                
                $stage_plans = DB::table('stage_plan') 
                        ->select(
                                'stage_plan.id',
                                'stage_plan.stage_code',
                                'stage_plan.predecessor_code',
                                'stage_plan.campaign_code',
                                'stage_plan.code',
                                'plan_master.expected_date',
                                'finished_product_category.intermediate_code',
                                'finished_product_category.finished_product_code'      
                        )
                        ->join('plan_master', 'stage_plan.plan_master_id' , '=', 'plan_master.id')
                        ->join('finished_product_category', 'stage_plan.product_caterogy_id', '=', 'finished_product_category.id')
                        ->where('stage_plan.finished', 0)
                        ->where('stage_plan.start', null)
                        ->where('stage_plan.active', 1)
                        ->where('stage_plan.stage_code',">=", 3)
                        ->orderBy('order_by', 'asc')
                ->get();
                
                
                for ($i=3; $i<=7; $i++){
                        $stage_plans_stage =  $stage_plans->where ('stage_code',$i);
                        if ($stage_plans_stage->isEmpty()) {continue;}
                        if ($i <=6) {$product_code = "intermediate_code";} else {$product_code = "finished_product_code";}

                        $updates = [];
                        
                        // Nhóm theo expected_date + intermediate_code
                        $groups = $stage_plans_stage
                        ->groupBy(function ($item) use ($product_code) {
                                return $item->expected_date . '|' . $item->$product_code;
                        })
                        ->filter(function ($group) {
                                return $group->count() > 1; // chỉ giữ group có > 1 phần tử
                        });
   
                foreach ($groups as $groupKey => $items) {

                        [$expected_date, $code] = explode('|', $groupKey);
                        $quota = DB::table('quota')->where($product_code, $code)->first();
                        $maxBatch = $quota->maxofbatch_campaign ?? 0;

                        // 👉 Bỏ qua nhóm nếu quota <= 1
                        if ($maxBatch <= 1) {continue;}

                        $items = $items->values(); // reset index

                        $countInBatch = 1;
                        $first = $items[0];
                        $campaignCode = $first->predecessor_code ?? ("0_" . $first->code);

                        foreach ($items as $item) {
                                if ($countInBatch >= $maxBatch) {
                                        $campaignCode = $item->predecessor_code ?? ("0_" . $item->code);
                                        $countInBatch = 1;
                                }

                                $updates[] = [
                                        'id' => $item->id,
                                        'campaign_code' => $campaignCode,
                                ];

                                $countInBatch++;
                        }
                }
                        // Bulk update (tách ra cho hiệu năng)
                        if (!empty($updates)) {
                                $ids = collect($updates)->pluck('id')->implode(',');

                                $caseSql = "CASE id ";
                                foreach ($updates as $row) {
                                        $caseSql .= "WHEN {$row['id']} THEN '{$row['campaign_code']}' ";
                                }
                                $caseSql .= "END";
                               
                                DB::update("UPDATE stage_plan SET campaign_code = $caseSql WHERE id IN ($ids)");
                        }
                }

                } catch (\Exception $e) {
                        Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);
                        return response()->json(['error' => 'Lỗi hệ thống'], 500);
                }
        }

        public function createOrderPlan (Request $request) {          
                //dd ($request->all());
                try {
                DB::transaction(function () use ($request) {
                        $planMasterId = DB::table('plan_master')->insertGetId([
                                'plan_list_id'        => 0,
                                'product_caterogy_id' => 0,
                                'level'               => 4,
                                'batch'               => $request->batch,
                                'expected_date'       => '2025-01-01',
                                'is_val'              => false,
                                'only_parkaging'      => false,
                                'percent_parkaging'   => 1,
                                'note'                => $request->note ?? "NA",
                                'deparment_code'      => session('user')['production_code'],
                                'created_at'          => now(),
                                'prepared_by'         => session('user')['fullName'],
                        ]);
                      
                        for ($i = 1; $i  <= $request->number_of_batch; $i++) {
                                // Insert stage_plan và gán plan_master_id
                                DB::table('stage_plan')->insert([
                                        'plan_list_id'        => 0,
                                        'product_caterogy_id' => 0,
                                        'plan_master_id'      => $planMasterId,
                                        'schedualed'          => 0,
                                        'finished'            => 0,
                                        'active'              => 1,
                                        'stage_code'          => 9,
                                        'deparment_code'      => session('user')['production_code'],
                                        'title'               => $request->title,
                                        'yields'              => $request->checkedClearning ? 0 : -1,
                                        'created_by'          => session('user')['fullName'],
                                        'created_date'        => now(),
                                ]);
                        }
                       
                
                });  

                } catch (\Exception $e) {
                        Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);       
                }

        }

        public function DeActiveOrderPlan (Request $request) {          
      
                try {
                        $ids = collect($request->all())->pluck('id'); // lấy ra danh sách id

                        DB::table('stage_plan')
                        ->whereIn('id', $ids)
                        ->update([
                                'active'        => 0,
                                'finished_by'   => session('user')['fullName'] ?? 'System',
                                'finished_date' => now(),
                        ]);
                } catch (\Exception $e) {
                        Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);
                }

        }

        ///////// Các hàm liên Auto Schedualer
        protected $roomAvailability = [];
   
        /**Load room_status để lấy các slot đã bận*/
        protected function loadRoomAvailability() {

                // $schedules = DB::table('room_status')->orderBy('start')->get();
                // foreach ($schedules as $row) {
                // $this->roomAvailability[$row->room_id][] = [
                //         'start' => Carbon::parse($row->start),
                //         'end'   => Carbon::parse($row->end)];
                // }

                $schedules = DB::table('stage_plan')
                ->whereNotNull('start')
                ->where('start',">=",now())
                ->orderBy('start')
                ->select('resourceId', 'start', 'end_clearning')->get();
                foreach ($schedules as $row) {
                        $this->roomAvailability[$row->resourceId][] = [
                        'start' => Carbon::parse($row->start),
                        'end'   => Carbon::parse($row->end_clearning)];
                }

        }

        /**Tìm slot trống sớm nhất trong phòng*/
        protected function findEarliestSlot($roomId, Carbon $earliestStart, $durationHours, $cleaningHours){
                if (!isset($this->roomAvailability[$roomId])) {
                        $this->roomAvailability[$roomId] = [];
                }

                $busyList = $this->roomAvailability[$roomId];
                $current = $earliestStart->copy();

                // Đổi duration & cleaning sang phút
                $durationMinutes = (int) round($durationHours * 60);
                $cleaningMinutes = (int) round($cleaningHours * 60);

                foreach ($busyList as $busy) {
                        if ($current->lt($busy['start'])) {
                                $gap = $busy['start']->diffInMinutes($current);
                                if ($gap >= ($durationMinutes + $cleaningMinutes)) {
                                        return $current;
                                }
                        }
                        // Nếu current vẫn nằm trong khoảng bận thì nhảy tới cuối khoảng đó
                        if ($current->lt($busy['end'])) {
                                $current = $busy['end']->copy();
                        }
                }
                return $current;
        }

        /** Ghi kết quả vào stage_plan + log vào room_status*/
        protected function saveSchedule($title, $stageId, $roomId, Carbon $start, Carbon $end,  ?Carbon $endCleaning = null, ?string $cleaningType = null) {
                
                DB::transaction(function() use ($title, $stageId, $roomId, $start, $end,  $endCleaning, $cleaningType) {

                        if ($cleaningType == 2){$titleCleaning = "VS-II";} else {$titleCleaning = "VS-I";}

                        DB::table('stage_plan')->where('id', $stageId)->update([
                                'title'           => $title,    
                                'resourceId'      => $roomId,
                                'start'           => $start,
                                'end'             => $end,
                                'start_clearning' => $end,
                                'end_clearning'   => $endCleaning,
                                'title_clearning' => $titleCleaning,
                                'schedualed_at'      => now(),
                        ]);
                        // nếu muốn log cả cleaning vào room_schedule thì thêm block này:
                        DB::table('room_status')->insert([
                                'room_id'       => $roomId,
                                'stage_plan_id' => $stageId,
                                'start'         => $start,
                                'end'           => $endCleaning,
                                'created_at'    => now(),
                                'updated_at'    => now(),
                        ]);
                
                });

                // cập nhật cache roomAvailability
                $this->roomAvailability[$roomId][] = ['start'=>$start,'end'=>$endCleaning];

                if ($start && $endCleaning) {$this->roomAvailability[$roomId][] = ['start'=>$start,'end'=>$endCleaning];}

                usort($this->roomAvailability[$roomId], fn($a,$b)=>$a['start']->lt($b['start']) ? -1 : 1);
        }

        /** Scheduler cho tất cả stage*/
        public function scheduleAll(Request $request) {
                               
                $start_date = Carbon::createFromFormat('Y-m-d', $request->input('start_date'))->setTime(6, 0, 0);

                $stageCodes = DB::table('stage_plan')
                ->distinct()
                ->where('stage_code',">=",3)
                ->orderBy('stage_code')
                ->pluck('stage_code');
                
                foreach ($stageCodes as $stageCode) {
                        $waite_time_nomal_batch = 0;
                        $waite_time_val_batch   = 0;
                        switch ($stageCode) {
                                case 3: 
                                        $waite_time_nomal_batch = 0;
                                        $waite_time_val_batch   = 0;
                                        break;

                                case 4: 
                                        $waite_time_nomal_batch = $request->input('wt_bleding') * 24 ?? 0;
                                        $waite_time_val_batch   = $request->input('wt_bleding_val')* 24 ?? 0;
                                        break;

                                case 5: 
                                        $waite_time_nomal_batch = $request->input('wt_forming') * 24 ?? 0;
                                        $waite_time_val_batch   = $request->input('wt_forming_val')* 24 ?? 0;
                                        break;

                                case 6: 
                                        $waite_time_nomal_batch = $request->input('wt_coating') * 24 ?? 0;
                                        $waite_time_val_batch   = $request->input('wt_coating_val')* 24 ?? 0;
                                        break;

                                case 7: // Đóng gói
                                        $waite_time_nomal_batch = $request->input('wt_blitering') * 24 ?? 0;
                                        $waite_time_val_batch   = $request->input('wt_blitering_val') * 24 ?? 0;
                                        break;

                                default:
                                        $waite_time_nomal_batch = 0;
                                        $waite_time_val_batch   = 0;
                                        break;
                        }
    
                        $this->scheduleStage($stageCode, $waite_time_nomal_batch , $waite_time_val_batch, $start_date, $request->work_sunday);
                }

                $production = session('user')['production_code'];
                $events = $this->getEvents($production);
                $plan_waiting = $this->getPlanWaiting($production);
                $sumBatchByStage = $this->yield($request->start, $request->end, "stage_code");

                return response()->json([
                        'events' => $events,
                        'plan' => $plan_waiting,
                        'sumBatchByStage' => $sumBatchByStage,
                ]);
        }

        /** Scheduler cho 1 stage*/
        public function scheduleStage(int $stageCode, int $waite_time_nomal_batch = 0, 
                int $waite_time_val_batch = 0,  ?Carbon $start_date = null , bool $working_sunday = false) {
                $tasks = DB::table('stage_plan')
                ->select('stage_plan.id',
                        'stage_plan.code', 
                        'stage_plan.predecessor_code', 
                        'stage_plan.campaign_code', 
                        'plan_master.batch',
                        'plan_master.is_val',
                        'plan_master.after_weigth_date',
                        'plan_master.before_weigth_date',
                        'plan_master.after_parkaging_date',
                        'plan_master.before_parkaging_date',
                        'finished_product_category.product_name_id',
                        'finished_product_category.market_id',
                        'finished_product_category.finished_product_code',
                        'finished_product_category.intermediate_code',
                        'product_name.name',
                        'market.code as market'
                )
                ->leftJoin('plan_master', 'stage_plan.plan_master_id', 'plan_master.id')
                ->leftJoin('finished_product_category', 'stage_plan.product_caterogy_id', 'finished_product_category.id')
                ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
                ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
                ->where('stage_code', $stageCode)
                ->whereNull('start')
                ->orderBy('order_by','asc')
                ->get();

                $processedCampaigns = []; // campaign đã xử lý

                foreach ($tasks as $task) {
                        if ($task->is_val === 1) { $waite_time = $waite_time_val_batch; }else {$waite_time = $waite_time_nomal_batch;}
      
                        if ($task->campaign_code === null) {
                              
                                $this->sheduleNotCampaing ($task, $stageCode, $waite_time, $start_date );
                        }else {
                                if (in_array($task->campaign_code, $processedCampaigns)) {continue;}
                                // Gom nhóm campaign
                                $campaignTasks = $tasks->where('campaign_code', $task->campaign_code);
                                

                                $this->scheduleCampaign( $campaignTasks, $stageCode, $waite_time,  $start_date, $working_sunday );
                                // Đánh dấu campaign đã xử lý
                                $processedCampaigns[] = $task->campaign_code;
                        }
                }
                
        }
         /** Scheduler lô thường*/
        
         protected function sheduleNotCampaing ($task, $stageCode,  int $waite_time = 0,  ?Carbon $start_date = null){
                        $title = $task->name ."- ". $task->batch ."- ". $task->market;
                        
                        $now = Carbon::now();
                        $minute = $now->minute;
                        $roundedMinute = ceil($minute / 15) * 15;
                        if ($roundedMinute == 60) {
                                $now->addHour();
                                $roundedMinute = 0;
                        }
                        $now->minute($roundedMinute)->second(0)->microsecond(0);

                        // Gom tất cả candidate time vào 1 mảng
                        $candidates = [$now];
                        
                        $candidates[] = $start_date;

                        // Nếu có after_weigth_date
                        if ($stageCode <=6){
                                if ($task->after_weigth_date) {$candidates[] = Carbon::parse($task->after_weigth_date);}
                        }else {
                                if ($task->after_parkaging_date) {$candidates[] = Carbon::parse($task->after_parkaging_date);}                        
                        }
                        // Gom dependency
                        $dependencyCodes = array_filter([
                                $task->predecessor_code ?? null,
                        ]);

                        foreach ($dependencyCodes as $depCode) {
                                $pred = DB::table('stage_plan')->where('code', $depCode)->first();
                                if ($pred && $pred->end) {
                                        $candidates[] = Carbon::parse($pred->end);
                                }
                                if  ($waite_time > 0 && $pred->end){
                                        $predEnd = Carbon::parse($pred->end);
                                                // Giờ bắt đầu ban đêm
                                        $nightStart = $predEnd->copy()->setTime(18, 0, 0);
                                                // Giờ kết thúc ban đêm (6h sáng hôm sau)
                                        $nightEnd = $predEnd->copy()->addDay()->setTime(6, 0, 0);

                                        // Nếu predEnd nằm trong khoảng 18h - 6h hôm sau
                                        if ($predEnd->between($nightStart, $nightEnd)) {
                                                $extraHours = $predEnd->diffInHours($nightEnd);
                                                $waite_time += $extraHours;
                                        }
                                }
                        }
                        // Lấy max
                        $earliestStart = collect($candidates)->max();
                        $earliestStart = $earliestStart->addHours($waite_time);
                        // phòng phù hợp (quota)
                        if ($stageCode <= 6){
                                $product_category_code = $task->intermediate_code;
                                $product_category_type = "intermediate_code";
                        }
                        else { 
                                $product_category_code = $task->finished_product_code;
                                $product_category_type = "finished_product_code";
                        }
                               
                        // phòng phù hợp (quota)
                        $rooms = DB::table('quota')
                                ->select ('room_id', 'p_time', 'm_time', 'C1_time', 'C2_time', 
                                                DB::raw('(TIME_TO_SEC(p_time)/3600) as p_time_hours'),
                                                DB::raw('(TIME_TO_SEC(m_time)/3600) as m_time_hours'),
                                                DB::raw('(TIME_TO_SEC(C1_time)/3600) as C1_time_hours'),
                                                DB::raw('(TIME_TO_SEC(C2_time)/3600) as C2_time_hours'),
                                                DB::raw('(TIME_TO_SEC(p_time)/3600 + TIME_TO_SEC(m_time)/3600) as execution_time'))
                                ->where($product_category_type,$product_category_code)->where ('stage_code', $stageCode)->get();
                        
                        $bestRoom = null;
                        $bestStart = null;
                        $bestEnd = null;
                        

                        foreach ($rooms as $room) {
                                $candidateStart = $this->findEarliestSlot(
                                        $room->room_id,
                                        $earliestStart->copy(),
                                        $room->execution_time,
                                        $room->C2_time_hours // them trương hợp chiến dịch
                                );
                                $executionMinutes = (int) round($room->execution_time * 60);
                                $candidateEnd = $candidateStart->copy()->addMinutes($executionMinutes);

                                if ($bestStart === null || $candidateStart->lt($bestStart)) {
                                        $bestRoom = $room->room_id;
                                        $bestStart = $candidateStart;
                                        $bestEnd = $candidateEnd;
                                }
                                $endCleaning = $candidateEnd->copy()->addHours((int) $room->C2_time_hours);
                                $this->saveSchedule(
                                        $title,
                                        $task->id,
                                        $bestRoom,
                                        $bestStart,
                                        $bestEnd,
                                        $endCleaning, 
                                        2,
                                
                                );
                        }
        }

        /** Scheduler lô chiến dịch*/
        protected function scheduleCampaign( $campaignTasks, $stageCode, int $waite_time = 0, ?Carbon $start_date = null, bool $working_sunday = false){
                $firstTask = $campaignTasks->first();
               
                $now = Carbon::now();
                $minute = $now->minute;
                $roundedMinute = ceil($minute / 15) * 15;
                if ($roundedMinute == 60) {
                        $now->addHour();
                        $roundedMinute = 0;
                }
                $now->minute($roundedMinute)->second(0)->microsecond(0);

                // Gom tất cả candidate time vào 1 mảng
                $candidates = [$now];
                $candidates[] = $start_date;

                // Nếu có after_weigth_date
                if ($stageCode <=6){
                        if ($firstTask->after_weigth_date) {$candidates[] = Carbon::parse($firstTask->after_weigth_date);}
                }else {
                        if ($firstTask->after_parkaging_date) {$candidates[] = Carbon::parse($firstTask->after_parkaging_date);}                        
                }

                // Gom dependency
                $dependencyCodes = array_filter([
                        $firstTask->predecessor_code ?? null,
                        $firstTask->campaign_code ?? null,
                ]);
                
                foreach ($dependencyCodes as $depCode) {
                        $pred = DB::table('stage_plan')->where('code', $depCode)->first();
                        if ($pred && $pred->end) {
                                $candidates[] = Carbon::parse($pred->end);
                        }
                }
                // Lấy max
                $earliestStart = collect($candidates)->max();


                // phòng phù hợp (quota)
                if ($stageCode <= 6){
                        $product_category_code = $firstTask->intermediate_code;
                        $product_category_type = "intermediate_code";
                }
                else { 
                        $product_category_code = $firstTask->finished_product_code;
                        $product_category_type = "finished_product_code";
                }
                         
                // phòng phù hợp (quota)
                $rooms = DB::table('quota')
                        ->select ('room_id', 'p_time', 'm_time', 'C1_time', 'C2_time', 
                                DB::raw('(TIME_TO_SEC(p_time)/3600) as p_time_hours'),
                                DB::raw('(TIME_TO_SEC(m_time)/3600) as m_time_hours'),
                                DB::raw('(TIME_TO_SEC(C1_time)/3600) as C1_time_hours'),
                                DB::raw('(TIME_TO_SEC(C2_time)/3600) as C2_time_hours')) 
                ->where($product_category_type,$product_category_code)->where ('stage_code', $stageCode)->get();
                  
                $bestRoom = null;
                $bestStart = null;
                $bestEnd   = null;

                foreach ($rooms as $room) {
                        $totalHours = $room->p_time_hours + ($campaignTasks->count() * $room->m_time_hours)
                                + ($campaignTasks->count()-1) * ($room->C1_time_hours)
                                + $room->C2_time_hours;
                        $candidateStart = $this->findEarliestSlot(
                                $room->room_id,
                                $earliestStart->copy(),
                                $totalHours,
                                0
                        );
                        
                        $candidateEnd = $candidateStart->copy()->addHours($totalHours);
                        
                        if ($bestStart === null || $candidateStart->lt($bestStart)) {
                                $bestRoom = $room->room_id;
                                $bestStart = $candidateStart;
                                $bestEnd   = $candidateEnd;
                                $bestQuota = $room;
                        }
                }

                // Lưu từng batch
                $currentStart = $bestStart;
                $counter = 0;
                foreach ($campaignTasks as  $task) {
                      
                        $pred = DB::table('stage_plan')->where('code', $task->predecessor_code)->first(); 
                        

                        if ($pred && $pred->end) { 
                                $predEnd = Carbon::parse($pred->end); 

                                if ($predEnd->gt($currentStart)) { 
                                        $currentStart = $predEnd; }
                                $pre_room = DB::table('quota')
                                        ->select ('room_id', 'p_time', 'm_time', 'C1_time', 'C2_time', 
                                                DB::raw('(TIME_TO_SEC(p_time)/3600) as p_time_hours'),
                                                DB::raw('(TIME_TO_SEC(m_time)/3600) as m_time_hours'),
                                                DB::raw('(TIME_TO_SEC(C1_time)/3600) as C1_time_hours'),
                                                DB::raw('(TIME_TO_SEC(C2_time)/3600) as C2_time_hours'))
                                        ->where('room_id',$pred->resourceId)->first();

                                // Công thêm giờ nếu thời gian kết thúc của công đoạn trước gơi vào thời gian đêm        
                                if  ($waite_time > 0 && $pred->end ){
                                        $predEnd = Carbon::parse($pred->end);
                                        // Giờ bắt đầu ban đêm
                                        $nightStart = $predEnd->copy()->setTime(18, 0, 0);
                                        // Giờ kết thúc ban đêm (6h sáng hôm sau)
                                        $nightEnd = $predEnd->copy()->addDay()->setTime(6, 0, 0);
                                        // Nếu predEnd nằm trong khoảng 18h - 6h hôm sau
                                        if ($predEnd->between($nightStart, $nightEnd)) {
                                                $extraHours = $predEnd->diffInHours($nightEnd);
                                                $waite_time += $extraHours;
                                        }
                                }
                        }
                        if ($task->predecessor_code){
                                $prevCycle = ($pre_room->m_time_hours ?? 0) + ($pre_room->C1_time_hours ?? 0);
                                $currCycle = ($bestQuota->m_time_hours ?? 0) + ($bestQuota->C1_time_hours ?? 0); 
                                if ($counter == 0 && $currCycle < $prevCycle) {
                                        //$delay_time =  (($pre_room->m_time_hours - $bestQuota->m_time_hours) + ($pre_room->C1_time_hours - $bestQuota->C1_time_hours))*$campaignTasks->count()-5;
                                        $delay_time = ($pre_room->m_time_hours*($campaignTasks->count() - 1) + $pre_room->C1_time_hours*($campaignTasks->count() - 2)) -
                                                        (($bestQuota->m_time_hours + $bestQuota->C1_time_hours )* ($campaignTasks->count() - 1));
                                        if ($waite_time > $delay_time) {$delay_time = $waite_time;}
                                        $currentStart = $currentStart->addHours($delay_time);
                                        
                                }elseif ($counter == 0 && $currCycle >= $prevCycle) {
                                        $currentStart = $currentStart->addHours($waite_time);}
                        }
                        // kiêm tra ngay chủ nhật
                        if ($working_sunday === false){
                                if (($currentStart->dayOfWeek === Carbon::SUNDAY) || 
                                        ($currentStart->dayOfWeek === Carbon::MONDAY && ($currentStart->hour < 6 || ($currentStart->hour === 5 && $currentStart->minute <= 45)))) {
                                        $currentStart = $currentStart->copy()->next(Carbon::MONDAY)->setTime(6, 0, 0);
                        }}

                        if ($counter == 0) {
                                $taskEnd = $currentStart->copy()->addHours((float) $bestQuota->p_time_hours + $bestQuota->m_time_hours);
                                $endCleaning = $taskEnd->copy()->addHours((float)$bestQuota->C1_time_hours); //Lô đâu tiên chiến dịch
                                $clearningType = 1;
                        }elseif ($counter == $campaignTasks->count()-1){
                                 
                                $taskEnd = $currentStart->copy()->addHours((float) $bestQuota->m_time_hours);
                                $endCleaning = $taskEnd->copy()->addHours((float)$bestQuota->C2_time_hours); //Lô cuối chiến dịch
                                $clearningType = 2;
                        }else {
                                $taskEnd = $currentStart->copy()->addHours((float) $bestQuota->m_time_hours);
                                $endCleaning = $taskEnd->copy()->addHours((float)$bestQuota->C1_time_hours); //Lô giữa chiến dịch
                                $clearningType = 1;
                        }

                        $this->saveSchedule(
                                $task->name."-".$task->batch ."-".$task->market,
                                $task->id,
                                $bestRoom,
                                $currentStart,
                                $taskEnd,
                                $endCleaning,
                                $clearningType
                        );
                        $counter++;
                        $currentStart = $endCleaning->copy();
                }
        }

        public function getRoomStatistics(Carbon $startDate, Carbon $endDate){
                // Tổng số giây trong khoảng
                $totalSeconds =  $startDate->diffInSeconds($endDate);

                // Query tính busy_hours
                $data = DB::table('stage_plan as r')
                        ->select(
                        'r.resourceId',
                        DB::raw("{$totalSeconds} / 3600 as total_hours"),
                        DB::raw("SUM(
                                TIMESTAMPDIFF(
                                SECOND,
                                GREATEST(r.start, '{$startDate}'),
                                LEAST(r.end, '{$endDate}')
                                )
                        ) / 3600 as busy_hours")
                        )
                        ->where('r.end', '>', $startDate)
                        ->where('r.start', '<', $endDate)
                        ->where('r.deparment_code', session('user')['production_code'])
                        ->groupBy('r.resourceId')
                        ->get();
                               
                // Bổ sung free_hours = total - busy
                $result = $data->map(function ($item) {
                        $item->busy_hours = $item->busy_hours ?? 0; // tránh null
                        $item->free_hours = $item->total_hours - $item->busy_hours;
                        return $item;
                });

                return $result; // 👉 QUAN TRỌNG
        }

        public function yield($startDate, $endDate, $group_By){
                $result =  DB::table('stage_plan as sp')
                        ->leftJoin('finished_product_category as fc', 'sp.product_caterogy_id', '=', 'fc.id')
                        ->leftJoin('intermediate_category as ic', 'fc.intermediate_code', '=', 'ic.intermediate_code')
                        ->whereBetween('sp.start', [$startDate, $endDate])
                        ->whereNotNull('sp.start')
                        ->where('sp.deparment_code', session('user')['production_code'])
                        ->select(
                        "sp.$group_By",
                        DB::raw('
                                SUM(
                                CASE 
                                        WHEN sp.stage_code <= 4 THEN ic.batch_size
                                        WHEN sp.stage_code <= 6 THEN fc.batch_qty
                                        ELSE fc.batch_qty
                                END
                                ) as total_qty
                        '),
                        DB::raw('
                                CASE 
                                WHEN sp.stage_code <= 4 THEN "Kg"
                                ELSE "ĐVL"
                                END as unit
                        ')
                        )
                        ->groupBy("sp.$group_By", "unit")
                        ->get();
                return $result;
                
        }

        
}

      function toMinutes($time) {
                [$hours, $minutes] = explode(':', $time);
                return ((int)$hours) * 60 + (int)$minutes;
        }



