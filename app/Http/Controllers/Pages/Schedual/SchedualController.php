<?php

namespace App\Http\Controllers\Pages\Schedual;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SchedualController extends Controller
{
        public function __construct() {
                $this->loadRoomAvailability();
        }

        public function index (){
                session()->put('fullCalender', [
                        'mode' => "offical",
                        'stage_plan_temp_list_id' => null
                ]);
                session()->put(['title'=> 'LỊCH SẢN XUẤT']);
                return view('app');
        }

        protected function getEvents($production, $startDate, $endDate, $clearning){

                if (session('fullCalender')['mode'] === 'offical'){$stage_plan_table = 'stage_plan';}else{$stage_plan_table = 'stage_plan_temp';}

                $startDate = Carbon::parse($startDate)->toDateTimeString();
                $endDate = Carbon::parse($endDate)->toDateTimeString();



                $event_plans = DB::table("$stage_plan_table as sp")
                        ->leftJoin('plan_master','sp.plan_master_id','=','plan_master.id')
                        ->leftJoin('finished_product_category','plan_master.product_caterogy_id','=','finished_product_category.id')
                        ->leftJoin('intermediate_category','finished_product_category.intermediate_code','=','intermediate_category.intermediate_code')
                        ->where('sp.active', 1)
                        ->when(session('fullCalender')['mode'] === 'temp',function ($query)
                                {return $query->where('sp.stage_plan_temp_list_id',session('fullCalender')['stage_plan_temp_list_id']);})
                        ->whereNotNull('sp.start')
                        ->where('sp.deparment_code', $production)
                        ->whereRaw('((sp.start <= ? AND sp.end >= ?) OR (sp.start_clearning <= ? AND sp.end_clearning >= ?))', [$endDate, $startDate, $endDate, $startDate])
                        ->select(
                        'sp.id',
                        'sp.predecessor_code',
                        'sp.nextcessor_code',
                        'sp.code',
                        'sp.title',
                        'sp.start',
                        'sp.end',
                        'sp.start_clearning',
                        'sp.end_clearning',
                        'sp.title_clearning',
                        'sp.resourceId',
                        'sp.plan_master_id',
                        'sp.stage_code',
                        'sp.finished',
                        'sp.quarantine_time',
                        'sp.tank',
                        'sp.keep_dry',
                        'sp.yields',
                        'sp.scheduling_direction',
                        'finished_product_category.intermediate_code',
                        'plan_master.expected_date',
                        'plan_master.after_weigth_date',
                        'plan_master.before_weigth_date',
                        'plan_master.after_parkaging_date',
                        'plan_master.before_parkaging_date',
                        'plan_master.is_val',
                        'plan_master.level'
                        )
                        ->selectRaw("
                        CASE
                        WHEN sp.stage_code IN (1,2)
                                THEN CASE
                                        WHEN intermediate_category.quarantine_time_unit = 1
                                        THEN intermediate_category.quarantine_weight * 24
                                        ELSE intermediate_category.quarantine_weight
                                END
                        WHEN sp.stage_code = 3
                                THEN CASE
                                        WHEN intermediate_category.quarantine_time_unit = 1
                                        THEN intermediate_category.quarantine_preparing * 24
                                        ELSE intermediate_category.quarantine_preparing
                                END
                        WHEN sp.stage_code = 4
                                THEN CASE
                                        WHEN intermediate_category.quarantine_time_unit = 1
                                        THEN intermediate_category.quarantine_blending * 24
                                        ELSE intermediate_category.quarantine_blending
                                END
                        WHEN sp.stage_code = 5
                                THEN CASE
                                        WHEN intermediate_category.quarantine_time_unit = 1
                                        THEN intermediate_category.quarantine_forming * 24
                                        ELSE intermediate_category.quarantine_forming
                                END
                        WHEN sp.stage_code = 6
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

                // $material_source_id = DB::table('plan_master')
                //         ->where('id', $plan_master_id)
                //         ->pluck('material_source_id');

                $historyCounts = DB::table('stage_plan_history')
                                ->select('stage_plan_id', DB::raw('COUNT(*) as count'))
                                ->groupBy('stage_plan_id')
                                ->pluck('count', 'stage_plan_id');

                for ($i = 0; $i < $plans->count(); $i++) {
                        $plan = $plans[$i];
                        $subtitle = null;

                                // Kiêm tra vi pham
                                if ($plan->stage_code <= 7){
                                        $color_event = '#4CAF50';

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

                                // $room_source = null;
                                // if ( $plan->stage_code >2 && $plan->stage_code < 7){
                                //         $room_source = DB::table('room_source')->where('intermediate_code', $plan->intermediate_code)->where('source_id', $material_source_id)->where ('room_id', $plan->resourceId)->exists();
                                //         if (!$room_source){
                                //                 //$color_event = '#dc02f9ff';
                                //                 //$subtitle = 'Nguồn NL Chưa Được Khai Báo Tại Phòng Sản Xuất';
                                // }}

                                if ($plan->expected_date < $plan->end && $plan->stage_code < 9){
                                        $color_event = '#f90202ff';
                                        $subtitle = 'Không Đáp Ứng Ngày Cần Hàng: '. $plan->expected_date;
                                        if ($plan->stage_code == 8 ){
                                                $subtitle = 'Không Đáp Ứng Hạn Bảo Trì: '. $plan->expected_date;
                                        }
                                }

                                                                // Kiểm tra vi phạm predecessor / successor
                                if ($plan->predecessor_code) {
                                        $prePlan = $plans->firstWhere('code', $plan->predecessor_code);
                                        if ($prePlan && $plan->start < $prePlan->end) {
                                                $color_event = '#4d4b4bff'; // đỏ
                                                $subtitle = 'Vi phạm: Start < End của công đoạn trước';
                                        }
                                }

                                if ($plan->nextcessor_code) {
                                        $nextPlan = $plans->firstWhere('code', $plan->nextcessor_code);
                                        if ($nextPlan && $plan->end > $nextPlan->start) {
                                                $color_event = '#4d4b4bff'; // đỏ
                                                $subtitle = 'Vi phạm: End > Start của công đoạn sau';
                                        }
                                }


                         // Event chính
                        if ($plan->start && $plan->end ) {
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
                                        'level' => $plan->level,
                                        //'room_source' => $room_source,
                                        'direction' => $plan->scheduling_direction,
                                        'keep_dry' => $plan->keep_dry,
                                        'tank' => $plan->tank,
                                        'experted_date' => Carbon::parse($plan->expected_date)->format('d/m/y'),
                                        'number_of_history' =>  $historyCounts[$plan->id] ?? 0 //DB::table('stage_plan_history')->where('stage_plan_id', $plan->id)->count()??0,
                                        ]);
                        }
                        // Event vệ sinh
                        if ($plan->start_clearning && $plan->end_clearning && $plan->yields >= 0 && $clearning == true) {
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

        }  // đã có temp

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

        public function getPlanWaiting($production){

                if (session('fullCalender')['mode'] === 'offical'){$stage_plan_table = 'stage_plan';}else{$stage_plan_table = 'stage_plan_temp';}

                $plan_waiting = DB::table("$stage_plan_table as sp")
                        ->whereNull('sp.start')
                        ->where('sp.active', 1)
                        ->where('sp.finished', 0)
                        ->where('sp.deparment_code', $production)
                        ->when(session('fullCalender')['mode'] === 'temp',function ($query)
                                {return $query->where('sp.stage_plan_temp_list_id',session('fullCalender')['stage_plan_temp_list_id']);})
                        ->leftJoin('plan_master', 'sp.plan_master_id', '=', 'plan_master.id')
                        ->leftJoin('source_material', 'plan_master.material_source_id', '=', 'source_material.id')
                        ->leftJoin('finished_product_category', function($join) {
                                $join->on('sp.product_caterogy_id', '=', 'finished_product_category.id')
                                ->where('sp.stage_code', '<=', 7);
                        })
                        ->leftJoin('product_name', function($join) {
                                $join->on('finished_product_category.product_name_id', '=', 'product_name.id')
                                ->where('sp.stage_code', '<=', 7);
                        })
                        ->leftJoin('maintenance_category', function($join) {
                                $join->on('sp.product_caterogy_id', '=', 'maintenance_category.id')
                                ->where('sp.stage_code', '=', 8);
                        })
                        ->leftJoin('market', 'finished_product_category.market_id', '=', 'market.id')
                        ->select(
                                'sp.*',
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
                                        WHEN sp.stage_code <= 7 THEN product_name.name
                                        ELSE maintenance_category.name END as name"),
                                DB::raw("CASE
                                        WHEN sp.stage_code = 8 THEN maintenance_category.code  END as instrument_code"),
                                DB::raw("CASE
                                        WHEN sp.stage_code = 8 THEN maintenance_category.is_HVAC END as is_HVAC")
                        )
                        ->orderBy('sp.order_by', 'asc')
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
        } // đã có temp

        // Hàm lấy resources
        protected function getResources($production, $startDate, $endDate){

                $roomStatus = $this->getRoomStatistics($startDate, $endDate);
                $sumBatchQtyResourceId = $this->yield($startDate, $endDate, "resourceId");

                $statsMap = $roomStatus->keyBy('resourceId');
                $yieldMap = $sumBatchQtyResourceId->keyBy('resourceId');



                $result = DB::table('room')
                ->select('id', 'code',  DB::raw("CONCAT(code,'-', name) as title"), 'main_equiment_name', 'stage','stage_code', 'production_group')
                ->where('active', 1)
                ->where('room.deparment_code', $production)
                ->orderBy('stage_code', 'asc')->orderBy('order_by', 'asc')
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
                //dd ($roomStatus, $sumBatchQtyResourceId ,$statsMap, $yieldMap, $result);
                return $result;

        } // đã có temp

        // Hàm view gọn hơn Request
        public function view(Request $request){

                $startDate = $request->startDate ;
                $endDate = $request->endDate;
                $viewtype = $request->viewtype;

                try {
                        $production = session('user')['production_code'];
                        
                        $clearing = true;
                        if ($viewtype == "resourceTimelineMonth" || $viewtype == "resourceTimelineYear" || $viewtype == "resourceTimelineQuarter") {
                                $clearing = false;
                        }
                       
                        if (user_has_permission(session('user')['userId'], 'loading_plan_waiting', 'boolean')){
                                $quota = $this->getQuota($production);
                                $plan_waiting = $this->getPlanWaiting($production);
                        }

                        $stageMap = DB::table('room')->where('deparment_code', $production)->pluck('stage_code', 'stage')->toArray();
                        $events = $this->getEvents($production, $startDate, $endDate, $clearing);
                        $sumBatchByStage = $this->yield($startDate, $endDate, "stage_code");
                        $resources = $this->getResources($production, $startDate, $endDate);

                        if (session('fullCalender')['mode'] === 'offical') {
                                $title = 'LỊCH SẢN XUẤT';
                                $type = true;
                        } else {
                                $title = 'LỊCH SẢN XUẤT TẠM THỜI';
                                $type = false;
                        }
                        $authorization = session('user')['userGroup'];

                        Log::info('resources', [
                                'resources' => $resources,
                        ]);

                        return response()->json([
                                'title' => $title,
                                'events' => $events,
                                'plan' => $plan_waiting ?? [], // [phân quyền]
                                'quota' => $quota ?? [],
                                'stageMap' => $stageMap ?? [],
                                'resources' => $resources,
                                'sumBatchByStage' => $sumBatchByStage,
                                'type' => $type,
                                'authorization' => $authorization,
                        ]);

                } catch (\Throwable $e) {
                        // Ghi log chi tiết lỗi
                        Log::error('Error in view(): ' . $e->getMessage(), [
                        'line' => $e->getLine(),
                        'file' => $e->getFile(),
                        'trace' => $e->getTraceAsString()
                        ]);

                        return response()->json([
                        'error' => true,
                        'message' => $e->getMessage(),
                        ], 500);
                }

        }// đã có temp

        public function getSumaryData(Request $request){
                $sumBatchByStage = $this->yield($request->startDate, $request->endDate, "stage_code");
                return response()->json([
                        'sumBatchByStage' => $sumBatchByStage,
                ]);
        }  // đã có temp

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
                        $events = $this->getEvents($production, $request->startDate, $request->endDate, true);
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


                                if (session('fullCalender')['mode'] === 'offical'){
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

                                        //DB::table('stage_plan_temp')->where('stage_plan_id', $product['id'])->update(['active'=> 0]);
                                        $last_version = DB::table('stage_plan_history')->where('stage_plan_id', $product['id'])->max('version') ?? 0;
                                        DB::table('stage_plan_history')
                                                ->insert([
                                                'stage_plan_id'   => $product['id'],
                                                'version'         => $last_version + 1,
                                                'start'           => $current_start,
                                                'end'             => $end_man,
                                                'resourceId'      => $request->room_id,
                                                'schedualed_by'   => session('user')['fullName'],
                                                'schedualed_at'   => now(),
                                                'deparment_code'  => session('user')['production_code'],
                                                'type_of_change'  => "Lập Lịch Thủ Công"
                                        ]);

                                }else{
                                        DB::table('stage_plan_temp')
                                                ->where('id', $product['id'])
                                                ->where('stage_plan_temp_list_id',session('fullCalender')['stage_plan_temp_list_id'])
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
                                }


                                if ($product['stage_code'] === 1){
                                        $current_start = $current_start;
                                }else{
                                        $current_start = $end_clearning;
                                }
                        }
                        DB::commit();
                } catch (\Exception $e) {
                        DB::rollBack();
                        Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);
                        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
                }


                $production = session('user')['production_code'];
                $events = $this->getEvents($production, $request->startDate, $request->endDate , true);
                $plan_waiting = $this->getPlanWaiting($production);
                $sumBatchByStage = $this->yield($request->startDate, $request->endDate, "stage_code");

                return response()->json([
                        'events' => $events,
                        'plan' => $plan_waiting,
                        'sumBatchByStage' => $sumBatchByStage,
                ]);
        } // đã có temp

        public function history(Request $request){
                try {
                // Lấy dữ liệu lịch sử theo stage_plan_id
                $history_data = DB::table('stage_plan_history')
                ->leftJoin('stage_plan','stage_plan_history.stage_plan_id','stage_plan.id')
                ->leftJoin('room','stage_plan_history.resourceId','room.id')
                ->where('stage_plan_id', $request->stage_code_id)
                ->select(
                        'stage_plan_history.*',
                        'stage_plan.title',
                        DB::raw("CONCAT(room.name, ' ', room.code) as room_name"))
                ->orderBy('version', 'desc')
                ->get();

                // Nếu không có dữ liệu thì trả về version = 0
                if ($history_data->isEmpty()) {
                        $history_data = collect([
                                [
                                'version' => 0,
                                'start' => null,
                                'end' => null,
                                'start_clearning' => null,
                                'end_clearning' => null,
                                'schedualed_at' => null,
                                ]
                        ]);
                }

                // Ghi log số lượng + dữ liệu chi tiết
                // Log::info('History data count: ' . $history_data->count());
                // Log::debug('History data details:', $history_data->toArray());

                // Trả dữ liệu về frontend
                return response()->json([
                        'history_data' => $history_data,
                ]);

                } catch (\Exception $e) {
                Log::error('Lỗi lấy history:', ['error' => $e->getMessage()]);
                return response()->json([
                'message' => 'Không thể lấy dữ liệu history',
                ], 500);
                }
        }

        public function store_maintenance (Request $request){

                if (session('fullCalender')['mode'] === 'offical'){$stage_plan_table = 'stage_plan';}else{$stage_plan_table = 'stage_plan_temp';}
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

                                DB::table($stage_plan_table)
                                        ->where('id', $product['id'])
                                        ->when(session('fullCalender')['mode'] === 'temp',function ($query)
                                        {return $query->where('stage_plan_temp_list_id',session('fullCalender')['stage_plan_temp_list_id']);})
                                        ->update([
                                                'start'           => $current_start,
                                                'end'             => $end_man,
                                                'resourceId'      => $room_id[$index],
                                                'title'           => $product['name'] ,
                                                'schedualed'      => 1,
                                                'schedualed_by'   => session('user')['fullName'],
                                                'schedualed_at'   => now(),
                                        ]);

                                        if (session('fullCalender')['mode'] === 'offical'){
                                                $last_version = DB::table('stage_plan_history')->where('stage_plan_id', $product['id'])->max('version') ?? 0;
                                                DB::table('stage_plan_history')
                                                        ->insert([
                                                        'stage_plan_id'   => $product['id'],
                                                        'version'         => $last_version + 1,
                                                        'start'           => $current_start,
                                                        'end'             => $end_man,
                                                        'resourceId'      => $request->room_id,
                                                        'schedualed_by'   => session('user')['fullName'],
                                                        'schedualed_at'   => now(),
                                                        'deparment_code'  => session('user')['production_code'],
                                                        'type_of_change'  => "Lập Lịch Thủ Công"
                                                ]);
                                        }

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

                                        DB::table($stage_plan_table)
                                        ->where('id', $product['id'])
                                        ->when(session('fullCalender')['mode'] === 'temp',function ($query)
                                        {return $query->where('stage_plan_temp_list_id',session('fullCalender')['stage_plan_temp_list_id']);})
                                        ->update([
                                                'start'           => $current_start,
                                                'end'             => $end_man,
                                                'resourceId'      => $room_id[0],
                                                'title'           => $product['name'] ,
                                                'schedualed'      => 1,
                                                'schedualed_by'   => session('user')['fullName'],
                                                'schedualed_at'   => now(),
                                        ]);

                                        if (session('fullCalender')['mode'] === 'offical'){
                                                $last_version = DB::table('stage_plan_history')->where('stage_plan_id', $product['id'])->max('version') ?? 0;
                                                DB::table('stage_plan_history')
                                                        ->insert([
                                                        'stage_plan_id'   => $product['id'],
                                                        'version'         => $last_version + 1,
                                                        'start'           => $current_start,
                                                        'end'             => $end_man,
                                                        'resourceId'      => $request->room_id,
                                                        'schedualed_by'   => session('user')['fullName'],
                                                        'schedualed_at'   => now(),
                                                        'deparment_code'  => session('user')['production_code'],
                                                        'type_of_change'  => "Lập Lịch Thủ Công"
                                                ]);
                                        }
                                        $current_start = $end_man;
                                }
                        }

                        DB::commit();
                } catch (\Exception $e) {
                        DB::rollBack();
                        Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);
                        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
                }

                $production = session('user')['production_code'];
                $events = $this->getEvents($production, $request->startDate, $request->endDate , true);
                $plan_waiting = $this->getPlanWaiting($production);
                $sumBatchByStage = $this->yield($request->startDate, $request->endDate, "stage_code");

                return response()->json([
                        'events' => $events,
                        'plan' => $plan_waiting,
                        'sumBatchByStage' => $sumBatchByStage,
                ]);


        } // đã có temp

        public function update(Request $request){

                if (session('fullCalender')['mode'] === 'offical'){$stage_plan_table = 'stage_plan';}else{$stage_plan_table = 'stage_plan_temp';}
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
                                DB::table($stage_plan_table)
                                ->where('id', $realId)
                                ->when(session('fullCalender')['mode'] === 'temp',function ($query)
                                {return $query->where('stage_plan_temp_list_id',session('fullCalender')['stage_plan_temp_list_id']);})
                                ->update([
                                        'start_clearning' => $change['start'],
                                        'end_clearning'   => $change['end'],
                                        'resourceId'      => $change['resourceId'],
                                        'schedualed_by'   => session('user')['fullName'],
                                        'schedualed_at'   => now(),
                                ]);
                        } else {
                                DB::table($stage_plan_table)
                                ->where('id', $realId)
                                ->when(session('fullCalender')['mode'] === 'temp',function ($query)
                                {return $query->where('stage_plan_temp_list_id',session('fullCalender')['stage_plan_temp_list_id']);})
                                ->update([
                                        'start'           => $change['start'],
                                        'end'             => $change['end'],
                                        'resourceId'      => $change['resourceId'],
                                        'schedualed_by'   => session('user')['fullName'],
                                        'schedualed_at'   => now(),
                                ]);
                                if (session('fullCalender')['mode'] === 'offical'){
                                        DB::table('stage_plan_history')
                                        ->insert([
                                        'stage_plan_id'   => $realId,
                                        'version'         => DB::table('stage_plan_history')->where('stage_plan_id',$realId)->max('version') + 1 ?? 1,
                                        'resourceId'      => $change['resourceId'],
                                        'start'           => $change['start'],
                                        'end'             => $change['end'],
                                        'schedualed_by'   => session('user')['fullName'],
                                        'schedualed_at'   => now(),
                                        'deparment_code'  => session('user')['production_code'],
                                        'type_of_change'  => "Cập Nhật Lịch"
                                        ]);
                                }
                        }
                }

                } catch (\Exception $e) {
                        Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);
                        return response()->json(['error' => 'Lỗi hệ thống'], 500);
                }

                $production = session('user')['production_code'];
                $events = $this->getEvents($production, $request->startDate, $request->endDate , true);
                $plan_waiting = $this->getPlanWaiting($production);
                $sumBatchByStage = $this->yield($request->startDate, $request->endDate, "stage_code");

                return response()->json([
                        'events' => $events,
                        'plan' => $plan_waiting,
                        'sumBatchByStage' => $sumBatchByStage,
                ]);
        } // đã có temp

        public function deActive(Request $request){
                if (session('fullCalender')['mode'] === 'offical'){$stage_plan_table = 'stage_plan';}else{$stage_plan_table = 'stage_plan_temp';}
                $items = collect($request->input('ids'));
                try {

                        foreach ($items as $item) {
                        $rowId = explode('-', $item['id'])[0];   // lấy id trước dấu -
                        $stageCode = $item['stage_code'];
                        if ($stageCode <= 2) {
                                        // chỉ cóa cân k xóa các công đoạn khác
                                        DB::table($stage_plan_table)
                                        ->where('id', $rowId)
                                        ->where('stage_code', '=', $stageCode)
                                        ->when(session('fullCalender')['mode'] === 'temp',function ($query)
                                        {return $query->where('stage_plan_temp_list_id',session('fullCalender')['stage_plan_temp_list_id']);})
                                        ->update([
                                                'start'            => null,
                                                'end'              => null,
                                                'start_clearning'  => null,
                                                'end_clearning'    => null,
                                                'resourceId'       => null,
                                                'title'            => null,
                                                'title_clearning'  => null,
                                                'schedualed'       => 0,
                                                'AHU_group' => 0,
                                                'schedualed_by'    => session('user')['fullName'],
                                                'schedualed_at'    => now(),
                                        ]);

                                        // if (session('fullCalender')['mode'] === 'offical'){
                                        //         DB::table('stage_plan_temp')->where('stage_plan_id', $rowId)->update(['active' => 1]);
                                        // }

                        }else {

                                        $plan = DB::table($stage_plan_table)->where('id', $rowId)->first();
                                        // Update tất cả stage_plan theo rule
                                        DB::table($stage_plan_table)
                                        ->where('plan_master_id', $plan->plan_master_id)->where('stage_code', '>=', $stageCode)
                                        ->when(session('fullCalender')['mode'] === 'temp',function ($query)
                                        {return $query->where('stage_plan_temp_list_id',session('fullCalender')['stage_plan_temp_list_id']);})
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

                                        if (session('fullCalender')['mode'] === 'offical'){
                                                // Xóa room_status theo các row này
                                                $affectedIds = DB::table('stage_plan')
                                                ->where('plan_master_id', $plan->plan_master_id)
                                                ->where('stage_code', '>=', $stageCode)
                                                ->where('stage_plan_temp_list_id',session('fullCalender')['stage_plan_temp_list_id'])
                                                ->pluck('id')
                                                ->toArray();

                                                // DB::table('stage_plan_temp') ->where('plan_master_id', $plan->plan_master_id)
                                                // ->where('stage_plan_temp_list_id',session('fullCalender')['stage_plan_temp_list_id'])
                                                // ->where('stage_code', '>=', $stageCode)->update(['active' => 1]);
                                        }
                        }
                        }
                } catch (\Exception $e) {
                        Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);
                        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
                }

                $production = session('user')['production_code'];
                $events = $this->getEvents($production, $request->startDate, $request->endDate , true);
                $plan_waiting = $this->getPlanWaiting($production);
                $sumBatchByStage = $this->yield($request->start, $request->end, "stage_code");

                return response()->json([
                                'events' => $events,
                                'plan' => $plan_waiting,
                                'sumBatchByStage' => $sumBatchByStage,
                ]);


        }// đã có temp

        public function deActiveAll(Request $request){

                if (session('fullCalender')['mode'] === 'offical'){$stage_plan_table = 'stage_plan';}else{$stage_plan_table = 'stage_plan_temp';}

                try {
                        $ids = DB::table($stage_plan_table)
                        ->whereNotNull('start')
                        ->where('active', 1)
                        ->where('finished', 0)
                        ->when(session('fullCalender')['mode'] === 'temp',function ($query)
                                        {return $query->where('stage_plan_temp_list_id',session('fullCalender')['stage_plan_temp_list_id']);})
                        ->pluck('id');

                        if ($ids->isEmpty()) {
                                $production = session('user')['production_code'];
                                $events = $this->getEvents($production, $request->startDate, $request->endDate , true);
                                $plan_waiting = $this->getPlanWaiting($production);
                                $sumBatchByStage = $this->yield($request->startDate, $request->endDate, "stage_code");
                                return response()->json([
                                        'events' => $events,
                                        'plan' => $plan_waiting,
                                        'sumBatchByStage' => $sumBatchByStage,
                                ]);
                        }

                        DB::table($stage_plan_table)
                                ->whereIn('id',  $ids)
                                ->update([
                                        'start' => null,
                                        'end' => null,
                                        'start_clearning' => null,
                                        'end_clearning' => null,
                                        'resourceId' => null,
                                        'title_clearning' => null,
                                        'schedualed' => 0,
                                        'AHU_group' => 0,
                                        'schedualed_by' =>  session('user')['fullName'],
                                        'schedualed_at' => now(),
                        ]);

                } catch (\Exception $e) {
                        Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);
                        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
                }

                $production = session('user')['production_code'];
                $events = $this->getEvents($production, $request->startDate, $request->endDate , true);
                $plan_waiting = $this->getPlanWaiting($production);
                $sumBatchByStage = $this->yield($request->startDate, $request->endDate, "stage_code");
                return response()->json([
                        'events' => $events,
                        'plan' => $plan_waiting,
                        'sumBatchByStage' => $sumBatchByStage,
                ]);

        }// đã có temp

        // public function finished(Request $request){

        //         $id = explode('-', $request->input('id'))[0];

        //         try {
        //                 DB::table('stage_plan')
        //                         ->where('id', $id)
        //                         ->update([
        //                                 'yields' => $request->input('yields'),
        //                                 'finished'  => 1
        //                 ]);
        //                 DB::table('room_status')
        //                         ->where('stage_plan_id', $id)
        //                         ->delete();

        //         } catch (\Exception $e) {
        //                 Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);
        //                 return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        //         }

        //         $production = session('user')['production_code'];
        //         $events = $this->getEvents($production, $request->startDate, $request->endDate , true);

        //         return response()->json([
        //                 'events' => $events,
        //         ]);
        // }
        public function finished(Request $request){
                $ids = $request->id;
                try {
                        if (isset($request->temp)) {
                                foreach ($ids as $id) {  
                                        DB::table('stage_plan')
                                                ->where('plan_master_id', $id) 
                                                ->where('stage_code','<=', $request->stage_code)
                                                ->update([
                                                'finished' => 1
                                                ]);
                                }
                        }else {
                                DB::table('stage_plan')
                                        ->where('id', $ids)
                                        ->update([
                                        'yields' => $request->input('yields'),
                                        'finished' => 1
                                        ]);
                        }


                } catch (\Exception $e) {
                        Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);
                        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
                }

                $production = session('user')['production_code'];
                
        
                if (isset($request->temp)) {
                        $plan_waiting = $this->getPlanWaiting($production);
                        return response()->json([
                                'plan_waiting' => $plan_waiting
                        ]);      
                }else {
                        $events = $this->getEvents($production, $request->startDate, $request->endDate, true);
                        return response()->json([
                                'events' => $events,
                        ]); 
                }


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

                return response()->json([
                        'plan' => $this->getPlanWaiting(session('user')['production_code'])
                ]);
        }

        public function createManualCampain(Request $request){

                if (session('fullCalender')['mode'] === 'offical'){$stage_plan_table = 'stage_plan';}else{$stage_plan_table = 'stage_plan_temp';}
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
                                DB::table($stage_plan_table)
                                        ->whereIn('id', $ids)
                                        ->when(session('fullCalender')['mode'] === 'temp',function ($query)
                                        {return $query->where('stage_plan_temp_list_id',session('fullCalender')['stage_plan_temp_list_id']);})
                                        ->update([
                                        'campaign_code' => $firstCode
                                        ]);
                        }else {

                                DB::table($stage_plan_table)
                                        ->where('campaign_code', $firstCode)
                                        ->when(session('fullCalender')['mode'] === 'temp',function ($query)
                                        {return $query->where('stage_plan_temp_list_id',session('fullCalender')['stage_plan_temp_list_id']);})
                                        ->update([
                                        'campaign_code' => null
                                ]);
                        }


                }}  catch (\Exception $e) {
                        Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);
                        return response()->json(['error' => 'Lỗi hệ thống'], 500);
                }

                return response()->json([
                        'plan' => $this->getPlanWaiting(session('user')['production_code'])
                ]);
        } // đã có temp

        public function createAutoCampain(){
                if (session('fullCalender')['mode'] === 'offical'){$stage_plan_table = 'stage_plan';}else{$stage_plan_table = 'stage_plan_temp';}

                try {
                // Lấy toàn bộ stage_plan chưa hoàn thành và active
                DB::table($stage_plan_table)
                        ->where('finished', 0)
                        ->where('start', null)
                        ->where('active', 1)
                        ->where('stage_code',">=", 3)
                        ->when(session('fullCalender')['mode'] === 'temp',function ($query)
                                        {return $query->where('stage_plan_temp_list_id',session('fullCalender')['stage_plan_temp_list_id']);})
                ->update(['campaign_code' => null]);

                $stage_plans = DB::table("$stage_plan_table as sp")
                        ->select(
                                'sp.id',
                                'sp.stage_code',
                                'sp.predecessor_code',
                                'sp.campaign_code',
                                'sp.code',
                                'plan_master.expected_date',
                                'plan_master.is_val',
                                'plan_master.code_val',
                                'finished_product_category.intermediate_code',
                                'finished_product_category.finished_product_code'
                        )
                        ->leftJoin('plan_master', 'sp.plan_master_id' , '=', 'plan_master.id')
                        ->leftJoin('finished_product_category', 'sp.product_caterogy_id', '=', 'finished_product_category.id')
                        ->where('sp.finished', 0)
                        ->whereNull('sp.start')
                        ->where('sp.active', 1)
                        ->where('sp.stage_code',">=", 3)
                        ->when(session('fullCalender')['mode'] === 'temp',function ($query)
                                        {return $query->where('stage_plan_temp_list_id',session('fullCalender')['stage_plan_temp_list_id']);})
                        ->orderBy('order_by', 'asc')
                ->get();

                for ($i=3; $i<=7; $i++){
                        $stage_plans_stage = $stage_plans->where('stage_code',$i);
                        if ($stage_plans_stage->isEmpty()) {continue;}
                        if ($i <=6) {$product_code = "intermediate_code";} else {$product_code = "finished_product_code";}
                        $updates = [];

                        // Lọc dữ liệu theo điều kiện code_val
                        if ($i == 3) {

                                $stage_plans_stage = $stage_plans_stage->filter(function($item) {
                                        return $item->code_val === null || explode("_", $item->code_val)[1] > 1;
                                });

                                $groups = $stage_plans_stage
                                ->groupBy(function ($item) use ($product_code) {
                                        // tách code_val
                                        if ($item->code_val === null) {
                                        $cvFlag = 'NULL';
                                        } else {
                                        $parts = explode('_', $item->code_val);
                                        $cvFlag = $parts[0]; // chỉ lấy phần yy (trước dấu "_")
                                        }

                                        return $item->expected_date . '|' . $item->$product_code . '|' . $cvFlag;
                                })
                                ->filter(function ($group) {
                                        return $group->count() > 1; // chỉ giữ group có > 1 phần tử
                                });

                        } else {
                                // i > 3 thì loại bỏ những record có code_val (chỉ giữ code_val == null)
                                $stage_plans_stage = $stage_plans_stage->filter(function($item){
                                        return empty($item->code_val);
                                });
                                // Group theo expected_date + product_code
                                $groups = $stage_plans_stage
                                ->groupBy(function ($item) use ($product_code) {
                                        return $item->expected_date . '|' . $item->$product_code;
                                })
                                ->filter(function ($group) {
                                        return $group->count() > 1;
                                });
                        }

                        foreach ($groups as $groupKey => $items) {
                                [$expected_date, $code] = explode('|', $groupKey);
                                $quota = DB::table('quota')->where($product_code, $code)->where('stage_code',$i)->first();
                                $maxBatch = $quota->maxofbatch_campaign ?? 0;

                                if ($maxBatch <= 1) {continue;}

                                $items = $items->values(); // reset index
                                $countInBatch = 0;
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

                        if (!empty($updates)) {
                                $ids = collect($updates)->pluck('id')->implode(',');

                                $caseSql = "CASE id ";
                                foreach ($updates as $row) {
                                $caseSql .= "WHEN {$row['id']} THEN '{$row['campaign_code']}' ";
                                }
                                $caseSql .= "END";

                                DB::update("UPDATE $stage_plan_table SET campaign_code = $caseSql WHERE id IN ($ids)");
                        }
                }



                } catch (\Exception $e) {
                        Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);
                        return response()->json(['error' => 'Lỗi hệ thống'], 500);
                }
                return response()->json([
                        'plan' => $this->getPlanWaiting(session('user')['production_code'])
                ]);
        } // đã có temp

        public function createOrderPlan (Request $request) {

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
                        $number_of_batch = $request->number_of_batch??1;
                        for ($i = 1; $i  <= $number_of_batch; $i++) {
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
                        return response()->json(['error' => 'Lỗi hệ thống'], 500);
                }

                return response()->json([
                        'plan' => $this->getPlanWaiting(session('user')['production_code'])
                ]);

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
                        return response()->json(['error' => 'Lỗi hệ thống'], 500);
                }

                return response()->json([
                        'plan' => $this->getPlanWaiting(session('user')['production_code'])
                ]);

        }

        ///////// Các hàm liên Auto Schedualer
        protected $roomAvailability = [];

        /**Load room_status để lấy các slot đã bận*/
        protected function loadRoomAvailability() {
                $this->roomAvailability = []; // reset
                $schedules = DB::table("stage_plan")
                        ->whereNotNull('start')
                        ->where('start', ">=", now())
                        ->select('resourceId', 'start', 'end_clearning')
                        ->orderBy('start', 'asc')
                        ->get();

                if (session('fullCalender')['mode'] === 'temp') {
                        $tempSchedules = DB::table("stage_plan_temp")
                        ->whereNotNull('start')
                        ->where('start', ">=", now())
                        ->where('stage_plan_temp_list_id', session('fullCalender')['stage_plan_temp_list_id'])
                        ->select('resourceId', 'start', 'end_clearning')
                        ->orderBy('start')
                        ->get();

                        $schedules = $schedules->merge($tempSchedules)->sortBy('start');
                }

                foreach ($schedules as $row) {
                        $this->roomAvailability[$row->resourceId][] = [
                        'start' => Carbon::parse($row->start),
                        'end'   => Carbon::parse($row->end_clearning),
                        ];
                }
        }

        /**Tìm slot trống sớm nhất trong phòng*/
        protected function findEarliestSlot($roomId, Carbon $earliestStart, $durationHours, $cleaningHours){
                $this->loadRoomAvailability();
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
        protected function saveSchedule($title, $stageId, $roomId, Carbon $start, Carbon $end,  Carbon $endCleaning, string $cleaningType, bool $direction) {

                DB::transaction(function() use ($title, $stageId, $roomId, $start, $end,  $endCleaning, $cleaningType, $direction) {
                        if (session('fullCalender')['mode'] === 'offical'){$stage_plan_table = 'stage_plan';}else{$stage_plan_table = 'stage_plan_temp';}
                        if ($cleaningType == 2){$titleCleaning = "VS-II";} else {$titleCleaning = "VS-I";}
                        $AHU_group  = DB::table ('room')->where ('id',$roomId)->value('AHU_group')?? 0;

                        DB::table($stage_plan_table)
                                ->where('id', $stageId)->whereNull('start')
                                ->when(session('fullCalender')['mode'] === 'temp',function ($query)
                                {return $query->where('stage_plan_temp_list_id',session('fullCalender')['stage_plan_temp_list_id']);})
                                ->update([
                                'title'           => $title,
                                'resourceId'      => $roomId,
                                'start'           => $start,
                                'end'             => $end,
                                'start_clearning' => $end,
                                'end_clearning'   => $endCleaning,
                                'title_clearning' => $titleCleaning,
                                'scheduling_direction' => $direction,
                                'AHU_group' => $AHU_group??null,
                                'schedualed_at'      => now(),

                        ]);

                        // nếu muốn log cả cleaning vào room_schedule thì thêm block này:
                        if (session('fullCalender')['mode'] === 'offical'){
                                // DB::table('stage_plan_temp')
                                //  ->when(session('fullCalender')['mode'] === 'temp',function ($query)
                                // {return $query->where('stage_plan_temp_list_id',session('fullCalender')['stage_plan_temp_list_id']);})
                                // ->where('stage_plan_id', $stageId)->update(['active' => 0]);

                                DB::table('stage_plan_history')
                                        ->insert([
                                        'stage_plan_id'   => $stageId,
                                        'version'         => (DB::table('stage_plan_history')->where('stage_plan_id',$stageId)->max('version')?? 0) + 1,
                                        'start'           => $start,
                                        'end'             => $end,
                                        'resourceId'      => $roomId,
                                        'schedualed_by'   => session('user')['fullName'],
                                        'schedualed_at'   => now(),
                                        'deparment_code'  => session('user')['production_code'],
                                        'type_of_change'  => "Lập Lịch Tự Động",
                                        //'AHU_group'       => $AHU_group??null,
                                ]);
                        }

                });

                // cập nhật cache roomAvailability
                $this->roomAvailability[$roomId][] = ['start'=>$start,'end'=>$endCleaning];

                if ($start && $endCleaning) {$this->roomAvailability[$roomId][] = ['start'=>$start,'end'=>$endCleaning];}

                usort($this->roomAvailability[$roomId], fn($a,$b)=>$a['start']->lt($b['start']) ? -1 : 1);
        }// đã có temp

        /** Scheduler cho tất cả stage Request */
        public function scheduleAll(Request $request) {

                if (session('fullCalender')['mode'] === 'offical'){$stage_plan_table = 'stage_plan';}else{$stage_plan_table = 'stage_plan_temp';}
                $today = Carbon::now()->toDateString();
                $start_date = Carbon::createFromFormat('Y-m-d', $request->start_date?? $today)->setTime(6, 0, 0);


                $stageCodes = DB::table($stage_plan_table)
                        ->distinct()
                        ->where('stage_code',">=",3)
                        ->when(session('fullCalender')['mode'] === 'temp',function ($query)
                                        {return $query->where('stage_plan_temp_list_id',session('fullCalender')['stage_plan_temp_list_id']);})
                        ->orderBy('stage_code')
                        ->pluck('stage_code');
                $waite_time = [];



                foreach ($stageCodes as $stageCode) {
                        $waite_time_nomal_batch = 0;
                        $waite_time_val_batch   = 0;
                        switch ($stageCode) {
                                case 3:
                                        $waite_time_nomal_batch = 0;
                                        $waite_time_val_batch   = 0;
                                        $waite_time[$stageCode] = [
                                                'waite_time_nomal_batch' => 0,
                                                'waite_time_val_batch'   => 0,
                                        ];
                                        break;
                                case 4:
                                        $waite_time_nomal_batch = ($request->wt_bleding ?? 0)  * 24 ;
                                        $waite_time_val_batch   = ($request->wt_bleding_val ?? 0) * 24;
                                        $waite_time[$stageCode] = [
                                                'waite_time_nomal_batch' => (($request->wt_bleding ?? 1) * 24 * 60) ,
                                                'waite_time_val_batch'   => (($request->wt_bleding_val ?? 5) * 24 * 60) ,
                                        ];
                                        break;

                                case 5:
                                        $waite_time_nomal_batch = ($request->wt_forming?? 0) * 24 ;
                                        $waite_time_val_batch   = ($request->wt_forming_val ?? 0) * 24;
                                        $waite_time[$stageCode] = [
                                                'waite_time_nomal_batch' => (($request->wt_forming ?? 1) * 24 * 60) ,
                                                'waite_time_val_batch'   => (($request->wt_forming_val ?? 5) * 24 * 60) ,
                                        ];
                                        break;

                                case 6:
                                        $waite_time_nomal_batch = ($request->wt_coating?? 0) * 24 ;
                                        $waite_time_val_batch   = ($request->wt_coating_val ?? 0) * 24;
                                        $waite_time[$stageCode] = [
                                                'waite_time_nomal_batch' => (($request->wt_coating ?? 1) * 24 * 60)  ,
                                                'waite_time_val_batch'   => (($request->wt_coating_val ?? 5) * 24 * 60) ,
                                        ];
                                        break;

                                case 7: // Đóng gói
                                        $waite_time_nomal_batch = ($request->wt_blitering ?? 24) ;
                                        $waite_time_val_batch   = ($request->wt_blitering_val ?? 24);
                                        $waite_time[$stageCode] = [
                                                'waite_time_nomal_batch' => (($request->wt_blitering ?? 3) * 24 * 60) ,
                                                'waite_time_val_batch'   => (($request->wt_blitering_val ?? 10) * 24 * 60) ,
                                        ];
                                        break;


                        }
                        //$this->scheduleStage($stageCode, $waite_time_nomal_batch , $waite_time_val_batch, $start_date, $request->work_sunday);
                }
                //dd ($waite_time);


                $this->scheduleStartBackward($request->work_sunday?? true, $request->buffer_date ?? 1, $start_date, $waite_time);

                $production = session('user')['production_code'];
                $events = $this->getEvents($production, $request->startDate ?? '2025-09-28T17:00:00.000Z', $request->endDate ??'2025-10-05T17:00:00.000Z' , true);
                $plan_waiting = $this->getPlanWaiting($production);
                $sumBatchByStage = $this->yield($request->start ?? '2025-09-28T17:00:00.000Z', $request->end ??'2025-10-05T17:00:00.000Z', "stage_code");
                return response()->json([
                        'events' => $events,
                        'plan' => $plan_waiting,
                        'sumBatchByStage' => $sumBatchByStage,
                ]);

        }// đã có temp

        /** Scheduler cho 1 stage*/
        public function scheduleStage(int $stageCode, int $waite_time_nomal_batch = 0,
                int $waite_time_val_batch = 0,  ?Carbon $start_date = null , bool $working_sunday = false) {

                if (session('fullCalender')['mode'] === 'offical'){$stage_plan_table = 'stage_plan';}else{$stage_plan_table = 'stage_plan_temp';}

                $tasks = DB::table("$stage_plan_table as sp")
                ->select('sp.id',
                        'sp.code',
                        'sp.predecessor_code',
                        'sp.campaign_code',
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
                ->leftJoin('plan_master', 'sp.plan_master_id', 'plan_master.id')
                ->leftJoin('finished_product_category', 'sp.product_caterogy_id', 'finished_product_category.id')
                ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
                ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
                ->where('stage_code', $stageCode)
                ->whereNull('start')
                ->when(session('fullCalender')['mode'] === 'temp',function ($query)
                                {return $query->where('stage_plan_temp_list_id',session('fullCalender')['stage_plan_temp_list_id']);})
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

                        if (session('fullCalender')['mode'] === 'offical'){$stage_plan_table = 'stage_plan';}else{$stage_plan_table = 'stage_plan_temp';}

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
                                $pred = DB::table($stage_plan_table)
                                ->when(session('fullCalender')['mode'] === 'temp',function ($query)
                                {return $query->where('stage_plan_temp_list_id',session('fullCalender')['stage_plan_temp_list_id']);})
                                ->where('code', $depCode)->first();
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
                        $endCleaning = null;
                        //Tim phòng tối ưu
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
                                        $endCleaning = $bestEnd->copy()->addHours((float) $room->C2_time_hours);
                                }

                        }

                        $this->saveSchedule(
                                        $title,
                                        $task->id,
                                        $bestRoom,
                                        $bestStart,
                                        $bestEnd,
                                        $endCleaning,
                                        2,
                                        1

                        );
        }

        /** Scheduler lô chiến dịch*/
        protected function scheduleCampaign( $campaignTasks, $stageCode, int $waite_time = 0, ?Carbon $start_date = null, bool $working_sunday = false){
                if (session('fullCalender')['mode'] === 'offical'){$stage_plan_table = 'stage_plan';}else{$stage_plan_table = 'stage_plan_temp';}
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
                        $pred = DB::table($stage_plan_table)
                        ->when(session('fullCalender')['mode'] === 'temp',function ($query)
                                {return $query->where('stage_plan_temp_list_id',session('fullCalender')['stage_plan_temp_list_id']);})
                        ->where('code', $depCode)->first();

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
                //Tim phòng tối ưu
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
                        $pred = DB::table($stage_plan_table)
                        ->when(session('fullCalender')['mode'] === 'temp',function ($query)
                                {return $query->where('stage_plan_temp_list_id',session('fullCalender')['stage_plan_temp_list_id']);})
                        ->where('code', $task->predecessor_code)->first();

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
                                $clearningType,
                                1
                        );
                        $counter++;
                        $currentStart = $endCleaning->copy();
                }
        }

        public function getRoomStatistics($startDate, $endDate){

                $startDate= Carbon::parse($startDate);
                $endDate= Carbon::parse($endDate);

                $totalSeconds =  $startDate->diffInSeconds($endDate);

                if (session('fullCalender')['mode'] === 'offical'){$stage_plan_table = 'stage_plan';}else{$stage_plan_table = 'stage_plan_temp';}
                // Query tính busy_hours
                $data = DB::table("$stage_plan_table as sp")
                ->select(
                        'sp.resourceId',
                        DB::raw("{$totalSeconds} / 3600 as total_hours"),
                        DB::raw("SUM(
                        TIMESTAMPDIFF(
                                SECOND,
                                GREATEST(sp.start, '{$startDate}'),
                                LEAST(COALESCE(sp.end_clearning, sp.end), '{$endDate}')
                        )
                        ) / 3600 as busy_hours")
                )
                ->when(session('fullCalender')['mode'] === 'temp',function ($query)
                                {return $query->where('sp.stage_plan_temp_list_id',session('fullCalender')['stage_plan_temp_list_id']);})
                ->whereRaw('((sp.start <= ? AND sp.end >= ?) OR (sp.start_clearning <= ? AND sp.end_clearning >= ?))', [$endDate, $startDate, $endDate, $startDate])
                ->where('sp.deparment_code', session('user')['production_code'])
                ->groupBy('sp.resourceId')
                ->get();

                // Bổ sung free_hours = total - busy
                $result = $data->map(function ($item) {
                        $item->busy_hours = $item->busy_hours ?? 0; // tránh null
                        $item->free_hours = $item->total_hours - $item->busy_hours;
                        return $item;
                });

                return $result; // 👉 QUAN TRỌNG
        } // đã có temp

        public function yield($startDate, $endDate, $group_By){

                if (session('fullCalender')['mode'] === 'offical'){$stage_plan_table = 'stage_plan';}else{$stage_plan_table = 'stage_plan_temp';}
                $startDate = Carbon::parse($startDate)->toDateTimeString();
                $endDate = Carbon::parse($endDate)->toDateTimeString();

                $result =  DB::table("$stage_plan_table as sp")
                        ->leftJoin('finished_product_category as fc', 'sp.product_caterogy_id', '=', 'fc.id')
                        ->leftJoin('intermediate_category as ic', 'fc.intermediate_code', '=', 'ic.intermediate_code')
                        ->whereRaw('((sp.start <= ? AND sp.end >= ?) OR (sp.start_clearning <= ? AND sp.end_clearning >= ?))', [$endDate, $startDate, $endDate, $startDate])
                        ->whereNotNull('sp.start')
                        ->where('sp.deparment_code', session('user')['production_code'])
                        ->when(session('fullCalender')['mode'] === 'temp',function ($query)
                                {return $query->where('sp.stage_plan_temp_list_id',session('fullCalender')['stage_plan_temp_list_id']);})
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

        } // đã có temp

        public function test(){
              //$this->scheduleAll (null);
              //$this->createAutoCampain();
              //$this->view (null);
        }

        ///////// Sắp Lịch Ngược ////////
        public function scheduleStartBackward($work_sunday, int $bufferDate, $start_date, $waite_time) {

                if (session('fullCalender')['mode'] === 'offical') {
                        $stage_plan_table = 'stage_plan';
                } else {
                        $stage_plan_table = 'stage_plan_temp';
                }
                $planMasters = DB::table('plan_master as pm')
                        ->whereIn('pm.id', function ($query) use ($stage_plan_table) {
                                $query->select(DB::raw('DISTINCT sp.plan_master_id'))
                                ->from("$stage_plan_table as sp")
                                ->whereNull('sp.start')
                                ->where('sp.active', 1)
                                ->where('sp.finished', 0)
                                ->where('sp.deparment_code', session('user')['production_code'])
                                ->when(session('fullCalender')['mode'] === 'temp', function ($query) {
                                        return $query->where('stage_plan_temp_list_id', session('fullCalender')['stage_plan_temp_list_id']);
                                });
                        })
                        ->orderBy('pm.expected_date', 'asc')
                        ->orderBy('pm.level', 'asc')
                        ->orderBy('pm.batch', 'asc')
                        ->pluck('pm.id');


                foreach ($planMasters as $planId) {

                        $check_plan_master_id_complete =  DB::table("$stage_plan_table as sp")
                        ->where ('plan_master_id', $planId)
                        ->whereNull ('sp.start')
                        ->where ('sp.active', 1)
                        ->where ('sp.finished', 0)
                        ->where('sp.deparment_code', session('user')['production_code'])
                        ->when(session('fullCalender')['mode'] === 'temp',function ($query)
                                                {return $query->where('stage_plan_temp_list_id',session('fullCalender')['stage_plan_temp_list_id']);})
                        ->exists();

                        if ($check_plan_master_id_complete){
                                $this->schedulePlanBackward($planId, $work_sunday, $bufferDate, $waite_time , $start_date);
                        }
                }

        } // khởi động và lấy mãng plan_master_id

        protected function schedulePlanBackward($plan_master_id,bool $working_sunday = false,int $bufferDate, $waite_time, Carbon $start_date) {

                $stage_plan_ids = [];
                $stage_plan_ids_null = [];
                if (session('fullCalender')['mode'] === 'offical') {
                        $stage_plan_table = 'stage_plan';
                } else {
                        $stage_plan_table = 'stage_plan_temp';
                }

                // toàn bộ các row trong stage_plan cùng plan_master_id của các công đoạn từ ĐG - PC
                $tasks = DB::table("$stage_plan_table as sp")
                ->select (
                        'sp.id',
                        'sp.plan_master_id',
                        'sp.product_caterogy_id',
                        'sp.predecessor_code',
                        'sp.nextcessor_code',
                        'sp.campaign_code',
                        'sp.code',
                        'sp.stage_code',
                        'sp.campaign_code',
                        'sp.tank',
                        'sp.keep_dry',
                        'fc.finished_product_code',
                        'fc.intermediate_code',
                        'pm.is_val',
                        'pm.code_val',
                        'pm.expected_date',
                        'pm.level',
                        'pm.batch',
                        'pm.after_weigth_date',
                        'pm.before_weigth_date',
                        'pm.after_parkaging_date',
                        'pm.before_parkaging_date',
                        'mk.code as market',
                        'pn.name',
                )
                ->leftJoin('finished_product_category as fc', 'sp.product_caterogy_id', '=', 'fc.id')
                ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
                ->leftJoin('product_name as pn', 'fc.product_name_id', '=', 'pn.id')
                ->leftJoin('market as mk', 'fc.market_id', '=', 'mk.id')
                ->where('plan_master_id', $plan_master_id)
                ->where('stage_code',">=",3)
                ->orderBy('stage_code', 'desc')
                ->get(); // 1 lô gồm tất cả các stage

                $latestEnd = Carbon::parse($tasks->first()->expected_date)->subDays(5 + $bufferDate);
                $nextCycle = 0; // thời gian sản xuất công đoạn trước = p_time + m_time
                $totalCount = 0; // vòng lập của ĐG --> không kiểm tra thời gian sản xuất với công đoạn trước


                foreach ($tasks as $task) { // Vòng lập chính duyệt qua toàn bộ các task cùng plan_master_id
                        if ($task->nextcessor_code){
                                $next_stage_code = explode('_', $task->nextcessor_code)[1];
                                if ($next_stage_code  && !$task->is_val) {
                                        $waite_time_for_task = $waite_time[$next_stage_code]['waite_time_nomal_batch'];
                                } else {
                                        $waite_time_for_task = $waite_time[$next_stage_code]['waite_time_val_batch'];
                                }
                        }else {$waite_time_for_task = null;}


                        $campaign_tasks = null;
                         // chứa id các row đã lưu. trường hợp các stage sau rơi và quá khứ sẽ dùng id này để xóa lịch đã sắp
                        if ($task->campaign_code){ // trường hợp chiến dịch
                                 $campaign_tasks = DB::table("$stage_plan_table as sp")
                                  ->select (
                                        'sp.id',
                                        'sp.plan_master_id',
                                        'sp.product_caterogy_id',
                                        'sp.predecessor_code',
                                        'sp.nextcessor_code',
                                        'sp.campaign_code',
                                        'sp.code',
                                        'sp.stage_code',
                                        'sp.tank',
                                        'sp.keep_dry',
                                        'fc.finished_product_code',
                                        'fc.intermediate_code',
                                        'pm.is_val',
                                        'pm.code_val',
                                        'pm.expected_date',
                                        'pm.level',
                                        'pm.batch',
                                        'pm.after_weigth_date',
                                        'pm.before_weigth_date',
                                        'pm.after_parkaging_date',
                                        'pm.before_parkaging_date',
                                        'mk.code as market',
                                        'pn.name')
                                ->leftJoin('finished_product_category as fc', 'sp.product_caterogy_id', '=', 'fc.id')
                                ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
                                ->leftJoin('product_name as pn', 'fc.product_name_id', '=', 'pn.id')
                                ->leftJoin('market as mk', 'fc.market_id', '=', 'mk.id')
                                ->where('sp.campaign_code',$task->campaign_code)
                                ->orderBy('expected_date', 'desc')
                                ->orderBy('level', 'desc')
                                ->orderBy('batch', 'desc')
                                ->get();
                        }
                        $parts = explode("_", $task->code_val);

                        /// Tìm Phòng Sản Xuất Thịch Hợp
                        if ($task->code_val !== null && $task->stage_code == 3 && isset($parts[1]) && $parts[1] > 1) {
                                $code_val_first = $parts[0] . '_1';

                                $room_id_first = DB::table("$stage_plan_table as sp")
                                        ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
                                        ->where('code_val', $code_val_first)
                                        ->where('stage_code', $task->stage_code)
                                        ->first();
                                if ($room_id_first) {
                                        $rooms = DB::table('quota')
                                        ->select(
                                                'room_id',
                                                DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                                                DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                                                DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                                                DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                                        )
                                        ->when($task->stage_code <= 6, function ($query) use ($task) {
                                                return $query->where('intermediate_code', $task->intermediate_code);
                                        }, function ($query) use ($task) {
                                                return $query->where('finished_product_code', $task->finished_product_code);
                                        })
                                        ->where('room_id', $room_id_first->resourceId)
                                        ->get();

                                } else {
                                        $rooms = DB::table('quota')
                                        ->select(
                                        'room_id',
                                        DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                                        DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                                        DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                                        DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                                        )
                                        ->when($task->stage_code <= 6, function ($query) use ($task) {
                                        return $query->where('intermediate_code', $task->intermediate_code);
                                        }, function ($query) use ($task) {
                                        return $query->where('finished_product_code', $task->finished_product_code);
                                        })
                                        ->where('stage_code', $task->stage_code)
                                        ->get();
                                }
                        }
                        elseif ($task->code_val !== null && $task->stage_code > 3 && isset($parts[1]) && $parts[1] > 1) {
                                $code_val_first = $parts[0];

                                $room_id_first = DB::table("$stage_plan_table as sp")
                                ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
                                ->where(DB::raw("SUBSTRING_INDEX(pm.code_val, '_', 1)"), '=', $parts[0])
                                ->where('sp.stage_code', $task->stage_code)
                                ->whereNotNull('start')
                                ->get();

                                if ($room_id_first) {

                                        $rooms = DB::table('quota')
                                        ->select(
                                                'room_id',
                                                DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                                                DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                                                DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                                                DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                                                )
                                                ->when($task->stage_code <= 6, function ($query) use ($task) {
                                                return $query->where('intermediate_code', $task->intermediate_code);
                                                }, function ($query) use ($task) {
                                                return $query->where('finished_product_code', $task->finished_product_code);
                                                })
                                        ->where('stage_code', $task->stage_code)
                                        ->get();


                                        if ($rooms->count () > $room_id_first->count ()) {
                                                foreach ($room_id_first as $first) {
                                                        $rooms->where('room_id', '!=', $first->resourceId);
                                                }
                                        }

                                } else {
                                        $rooms = DB::table('quota')
                                        ->select(
                                        'room_id',
                                        DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                                        DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                                        DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                                        DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                                        )
                                        ->when($task->stage_code <= 6, function ($query) use ($task) {
                                        return $query->where('intermediate_code', $task->intermediate_code);
                                        }, function ($query) use ($task) {
                                        return $query->where('finished_product_code', $task->finished_product_code);
                                        })
                                        ->where('stage_code', $task->stage_code)
                                        ->get();
                                }


                        }else {
                                $rooms = DB::table('quota')
                                        ->select(
                                        'room_id',
                                        DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                                        DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                                        DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                                        DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                                        )
                                        ->when($task->stage_code <= 6, function ($query) use ($task) {
                                        return $query->where('intermediate_code', $task->intermediate_code);
                                        }, function ($query) use ($task) {
                                        return $query->where('finished_product_code', $task->finished_product_code);
                                        })
                                        ->where('stage_code', $task->stage_code)
                                        ->get(); // dùng first() để đồng nhất với nhánh if
                        }

                        $bestRoom = null;
                        $bestRoomId = null;
                        $bestStart = null;
                        $bestEnd = null;
                        $bestEndCleaning = null;
                        $count_room = 1;
                        $index_campaign_tasks = null;
                        /// tim Phòng thich hợp
                        foreach ($rooms as $room) { // duyệt qua toàn bộ các room đã định mức để tìm bestroom

                                if ($campaign_tasks !== null){ $number_of_batch = $campaign_tasks->count();}else {$number_of_batch = 1;}

                                $beforeIntervalMinutes = (float) $room->p_time_minutes + (float) $room->m_time_minutes * $number_of_batch + (float) $room->C1_time_minutes * ($number_of_batch - 1);
                                $afterIntervalMinutes =  (float) $room->C2_time_minutes;
                                $currCycle = (float) $room->m_time_minutes;

                                if ($totalCount > 0){
                                        $next_stage_code  = explode('_', $task->nextcessor_code)[1];
                                        $batch_of_next_campaign = DB::table($stage_plan_table)
                                                ->where('plan_master_id', $task->plan_master_id)
                                                ->where('stage_code', $next_stage_code)
                                                ->when(session('fullCalender')['mode'] === 'temp', function ($query) {return $query->where('stage_plan_temp_list_id',
                                                                session('fullCalender')['stage_plan_temp_list_id']);})
                                                ->first();
                                        if ($campaign_tasks === null){
                                                 $latestEnd = Carbon::parse($batch_of_next_campaign->start);
                                        }else {
                                                $nextCycle = Carbon::parse($batch_of_next_campaign->start)->diffInMinutes(Carbon::parse($batch_of_next_campaign->end));

                                                if ($currCycle >= $nextCycle){
                                                        if ($count_room == 1){ // chỉ dò $index_campaign_tasks ở lần đầu tiên
                                                                foreach ($campaign_tasks as $campaign_task) {
                                                                        $next_last_batch = DB::table($stage_plan_table)
                                                                        ->whereNotNull ('start')
                                                                        ->where('stage_code', $next_stage_code)
                                                                        ->where('plan_master_id', $campaign_task->plan_master_id)
                                                                        ->when(session('fullCalender')['mode'] === 'temp', function ($query) {
                                                                        return $query->where('stage_plan_temp_list_id',session('fullCalender')['stage_plan_temp_list_id']);})
                                                                        ->first();
                                                                        if ($next_last_batch &&  $next_last_batch->plan_master_id !== null){break;}
                                                                }
                                                        }
                                                }else {

                                                          if ($count_room == 1){ // chỉ dò $index_campaign_tasks ở lần đầu tiên
                                                                foreach ($campaign_tasks->reverse() as $campaign_task) {
                                                                        $next_last_batch = DB::table($stage_plan_table)
                                                                                ->whereNotNull ('start')
                                                                                ->where('stage_code', $next_stage_code)
                                                                                ->where('plan_master_id', $campaign_task->plan_master_id)
                                                                                ->when(session('fullCalender')['mode'] === 'temp', function ($query) {
                                                                                                        return $query->where('stage_plan_temp_list_id',session('fullCalender')['stage_plan_temp_list_id']);})
                                                                                ->first();
                                                                        if ($next_last_batch &&  $next_last_batch->plan_master_id !== null){
                                                                                break;
                                                                        }
                                                                }
                                                        }
                                                }
                                                $index_campaign_tasks = $campaign_tasks->search(function ($item) use ($next_last_batch) {
                                                                        return $item->plan_master_id == $next_last_batch->plan_master_id;});
                                                $latestEnd = Carbon::parse($next_last_batch->start);
                                                $beforeIntervalMinutes = (float) $room->p_time_minutes + (float) $room->m_time_minutes * ($number_of_batch - $index_campaign_tasks) + ((float) $room->C1_time_minutes * ($number_of_batch - $index_campaign_tasks) - 1);
                                                $afterIntervalMinutes =  ((float) $room->m_time_minutes * ($index_campaign_tasks)) + ((float) $room->C1_time_minutes * ($index_campaign_tasks - 1)) + (float) $room->C2_time_minutes;
                                        }
                                }

                                if ($waite_time_for_task){
                                        $latestEnd = $latestEnd->copy()->subMinutes($waite_time_for_task);
                                }

                                if ($task->stage_code == 7 ){
                                        $before_parkaging_date = Carbon::parse($task->before_parkaging_date);
                                        if ($latestEnd->gt($before_parkaging_date)){
                                                $latestEnd = $before_parkaging_date;
                                        }
                                }elseif ($task->stage_code == 3) {
                                        $before_weigth_date = Carbon::parse($task->before_weigth_date);
                                        if ($latestEnd->gt($before_weigth_date)){
                                                $latestEnd = $before_weigth_date;
                                        }
                                }


                                $candidateEndClearning = $this->findLatestSlot(
                                        $room->room_id,
                                        $latestEnd,
                                        $beforeIntervalMinutes,
                                        $afterIntervalMinutes,
                                        60,
                                        $start_date,
                                        $task->tank,
                                        $task->keep_dry,
                                        2,
                                        $stage_plan_table

                                );

                               // candidateEndClearning Có vi phảm vào quá khứ không
                                if ($candidateEndClearning == false){
                                        if ($stage_plan_ids) {

                                                DB::table($stage_plan_table)
                                                ->whereIn('id', $stage_plan_ids)
                                                ->when(session('fullCalender')['mode'] === 'temp',function ($query)
                                                {return $query->where('stage_plan_temp_list_id',session('fullCalender')['stage_plan_temp_list_id']);})
                                                ->update([
                                                        'start'            => null,
                                                        'end'              => null,
                                                        'start_clearning'  => null,
                                                        'end_clearning'    => null,
                                                        'resourceId'       => null,
                                                        'title'            => null,
                                                        'title_clearning'  => null,
                                                        'schedualed'       => 0,
                                                ]);
                                        }
                                        $this->schedulePlanForwardPlanMasterId ($plan_master_id, $working_sunday, $waite_time, $start_date);

                                        return false;
                                }
                                if ($bestEndCleaning === null || $candidateEndClearning->gt($bestEndCleaning)) {
                                        $bestRoom = $room;
                                        $bestRoomId = $room->room_id;
                                        $bestEndCleaning  = $candidateEndClearning;
                                        $bestEnd = $bestEndCleaning->copy()->subMinutes((float) $afterIntervalMinutes);
                                        $bestStart = $bestEnd->copy()->subMinutes((float) $beforeIntervalMinutes);
                                }
                                $count_room++;
                        }

                        /// Lưu
                        if ($campaign_tasks !== null){
                                $campaign_counter = 1;
                                $current_end_clearning = $candidateEndClearning;
                                foreach ($campaign_tasks as $campaign_task){
                                        if ($campaign_counter == 1) {
                                                $bestEndClearning = $current_end_clearning;
                                                $bestEnd = $bestEndClearning->copy()->subMinutes((float) $bestRoom->C2_time_minutes);
                                                $bestStart = $bestEnd->copy()->subMinutes((float) $bestRoom->m_time_minutes); ;
                                                $clearningType = 2;

                                        }elseif ($campaign_counter == $campaign_tasks->count()){

                                                $bestEndClearning = $current_end_clearning;
                                                $bestEnd = $bestEndClearning->copy()->subMinutes((float) $bestRoom->C1_time_minutes);
                                                $bestStart = $bestEnd->copy()->subMinutes((float) $bestRoom->p_time_minutes + (float) $bestRoom->m_time_minutes); ;
                                                $clearningType = 1;
                                        }else {
                                                $bestEndClearning = $current_end_clearning;
                                                $bestEnd = $bestEndClearning->copy()->subMinutes((float) $bestRoom->C1_time_minutes);
                                                $bestStart = $bestEnd->copy()->subMinutes((float) $bestRoom->m_time_minutes); //Lô giữa chiến dịch
                                                $clearningType = 1;
                                        }
                                        $title = $campaign_task->name ."- ". $campaign_task->batch ."- ". $campaign_task->market;
                                        $this->saveSchedule(
                                                $title,
                                                $campaign_task->id,
                                                $bestRoomId,
                                                $bestStart,
                                                $bestEnd,
                                                $bestEndClearning,
                                                $clearningType,
                                                0
                                        );
                                        $current_end_clearning = $bestStart ;
                                        $stage_plan_ids [] = $campaign_task->id;
                                        $stage_plan_ids_null = [...$stage_plan_ids_null, ...DB::table($stage_plan_table)->where('plan_master_id',$campaign_task->plan_master_id)->where('stage_code','>=',3)->pluck('id')->toArray()];
                                        $campaign_counter++;
                                }
                        }else {
                                $title = $task->name ."- ". $task->batch ."- ". $task->market;
                                $this->saveSchedule(
                                        $title,
                                        $task->id,
                                        $bestRoomId,
                                        $bestStart,
                                        $bestEnd,
                                        $bestEndCleaning,
                                        2,
                                        0
                                );
                                $stage_plan_ids [] = $task->id;
                        }
                        // cập nhật latestEnd cho stage tiếp theo
                        $totalCount++;
                }
                $stage_plan_ids_null = array_unique($stage_plan_ids_null);
                $stage_plan_ids_null = array_diff($stage_plan_ids_null, $stage_plan_ids);
                // if (!empty($stage_plan_ids_null)){
                //       $this->schedulePlanForwardstageCodeId ($stage_plan_ids_null, $working_sunday , $start_date);
                // }
        } // khởi động và lấy mãng plan_master_id

        protected function schedulePlanForwardPlanMasterId($planId, bool $working_sunday = false, $waite_time,  ?Carbon $start_date = null) {

                if (session('fullCalender')['mode'] === 'offical') {
                        $stage_plan_table = 'stage_plan';
                } else {
                        $stage_plan_table = 'stage_plan_temp';
                }
                $now = Carbon::now();
                $minute = $now->minute;
                $roundedMinute = ceil($minute / 15) * 15;

                // toàn bộ các row trong stage_plan cùng plan_master_id của các công đoạn từ ĐG - PC
                $tasks = DB::table("$stage_plan_table as sp")
                        ->select (
                                'sp.id',
                                'sp.plan_master_id',
                                'sp.product_caterogy_id',
                                'sp.predecessor_code',
                                'sp.campaign_code',
                                'sp.code',
                                'sp.stage_code',
                                'sp.tank',
                                'sp.keep_dry',
                                'fc.finished_product_code',
                                'fc.intermediate_code',
                                'pm.is_val',
                                'pm.code_val',
                                'pm.expected_date',
                                'pm.batch',
                                'pm.after_weigth_date',
                                'pm.before_weigth_date',
                                'pm.after_parkaging_date',
                                'pm.before_parkaging_date',
                                'mk.code as market',
                                'pn.name',
                        )
                ->leftJoin('finished_product_category as fc', 'sp.product_caterogy_id', '=', 'fc.id')
                ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
                ->leftJoin('product_name as pn', 'fc.product_name_id', '=', 'pn.id')
                ->leftJoin('market as mk', 'fc.market_id', '=', 'mk.id')
                ->whereNull('start')
                ->where('plan_master_id', $planId)
                ->where('stage_code',">=",3)
                ->orderBy('stage_code', 'asc') // chạy thuận
                ->get(); // 1 lô gồm tất cả các stage

                $prevCycle = 0;
                foreach ($tasks as  $task) { // Vòng lập chính duyệt qua toàn bộ các task cùng plan_master_id
                        $waite_time_for_task = null;

                        if (!$task->is_val) {
                                $waite_time_for_task = $waite_time[$task->stage_code]['waite_time_nomal_batch'];
                        } else {
                                $waite_time_for_task = $waite_time[$task->stage_code]['waite_time_val_batch'];
                        }

                        $campaign_tasks = null;
                        $candidatesEarliest = [];
                        if ($task->campaign_code){ // trường hợp chiến dịch
                                $campaign_tasks = DB::table("$stage_plan_table as sp")
                                  ->select (
                                        'sp.id',
                                        'sp.plan_master_id',
                                        'sp.product_caterogy_id',
                                        'sp.predecessor_code',
                                        'sp.nextcessor_code',
                                        'sp.campaign_code',
                                        'sp.code',
                                        'sp.stage_code',
                                        'sp.campaign_code',
                                        'sp.tank',
                                        'sp.keep_dry',
                                        'fc.finished_product_code',
                                        'fc.intermediate_code',
                                        'pm.is_val',
                                        'pm.code_val',
                                        'pm.expected_date',
                                        'pm.level',
                                        'pm.batch',
                                        'pm.after_weigth_date',
                                        'pm.before_weigth_date',
                                        'pm.after_parkaging_date',
                                        'pm.before_parkaging_date',
                                        'mk.code as market',
                                        'pn.name')
                                ->leftJoin('finished_product_category as fc', 'sp.product_caterogy_id', '=', 'fc.id')
                                ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
                                ->leftJoin('product_name as pn', 'fc.product_name_id', '=', 'pn.id')
                                ->leftJoin('market as mk', 'fc.market_id', '=', 'mk.id')
                                ->whereNull('start')
                                ->where('campaign_code',$task->campaign_code)
                                ->orderBy('expected_date', 'asc')
                                ->orderBy('level', 'asc')
                                ->orderBy('batch', 'asc')
                                ->get();
                        }

                        /// Tìm Phòng Sản Xuất Thịch Hợp
                        if ($task->code_val !== null && $task->stage_code == 3 && isset($parts[1]) && $parts[1] > 1) {
                                $code_val_first = $parts[0] . '_1';

                                $room_id_first = DB::table("$stage_plan_table as sp")
                                        ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
                                        ->where('code_val', $code_val_first)
                                        ->where('stage_code', $task->stage_code)
                                        ->first();

                                if ($room_id_first) {
                                        $rooms = DB::table('quota')
                                        ->select(
                                                'room_id',
                                                DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                                                DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                                                DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                                                DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                                        )
                                        ->when($task->stage_code <= 6, function ($query) use ($task) {
                                                return $query->where('intermediate_code', $task->intermediate_code);
                                        }, function ($query) use ($task) {
                                                return $query->where('finished_product_code', $task->finished_product_code);
                                        })
                                        ->where('room_id', $room_id_first->resourceId)
                                        ->get();

                                } else {

                                        $rooms = DB::table('quota')->select('room_id',
                                                        DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                                                        DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                                                        DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                                                        DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                                                )
                                        ->when($task->stage_code <= 6, function ($query) use ($task) {
                                                return $query->where('intermediate_code', $task->intermediate_code);
                                        }, function ($query) use ($task) {
                                                return $query->where('finished_product_code', $task->finished_product_code);
                                        })
                                        ->where('stage_code', $task->stage_code)
                                        ->get();

                                }
                        }
                        elseif ($task->code_val !== null && $task->stage_code > 3 && isset($parts[1]) && $parts[1] > 1) {
                                $code_val_first = $parts[0];

                                $room_id_first = DB::table("$stage_plan_table as sp")
                                ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
                                ->where(DB::raw("SUBSTRING_INDEX(pm.code_val, '_', 1)"), '=', $parts[0])
                                ->where('sp.stage_code', $task->stage_code)
                                ->whereNotNull('start')
                                ->get();

                                if ($room_id_first) {

                                        $rooms = DB::table('quota')
                                        ->select(
                                                'room_id',
                                                DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                                                DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                                                DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                                                DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                                                )
                                                ->when($task->stage_code <= 6, function ($query) use ($task) {
                                                return $query->where('intermediate_code', $task->intermediate_code);
                                                }, function ($query) use ($task) {
                                                return $query->where('finished_product_code', $task->finished_product_code);
                                                })
                                        ->where('stage_code', $task->stage_code)
                                        ->get();


                                        if ($rooms->count () > $room_id_first->count ()) {
                                                foreach ($room_id_first as $first) {
                                                        $rooms->where('room_id', '!=', $first->resourceId);
                                                }
                                        }

                                } else {
                                        $rooms = DB::table('quota')->select('room_id',
                                                        DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                                                        DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                                                        DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                                                        DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                                                )
                                        ->when($task->stage_code <= 6, function ($query) use ($task) {
                                                return $query->where('intermediate_code', $task->intermediate_code);
                                        }, function ($query) use ($task) {
                                                return $query->where('finished_product_code', $task->finished_product_code);
                                        })
                                        ->where('stage_code', $task->stage_code)
                                        ->get();
                                }

                        }else {
                                $rooms = DB::table('quota')->select('room_id',
                                                DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                                                DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                                                DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                                                DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                                        )
                                ->when($task->stage_code <= 6, function ($query) use ($task) {
                                        return $query->where('intermediate_code', $task->intermediate_code);
                                }, function ($query) use ($task) {
                                        return $query->where('finished_product_code', $task->finished_product_code);
                                })
                                ->where('stage_code', $task->stage_code)
                                ->get();
                        }

                        $bestRoom = null;
                        $bestRoomId = null;
                        $bestStart = null;
                        $bestEnd = null;
                        $bestEndCleaning = null;

                        if ($roundedMinute == 60) {
                                $now->addHour();
                                $roundedMinute = 0;
                        }
                        $now->minute($roundedMinute)->second(0)->microsecond(0);

                        // Gom tất cả candidate time vào 1 mảng
                        if ($campaign_tasks !== null){
                                $prev_stage = DB::table ($stage_plan_table)
                                ->where('code', $campaign_tasks->first()->predecessor_code)->first();
                                $candidatesEarliest[] = Carbon::parse($prev_stage->end);


                        }else {
                                $prev_stage = DB::table ($stage_plan_table)
                                ->where('code', $task->predecessor_code)->first();
                                $candidatesEarliest[] = Carbon::parse($prev_stage->end);
                        }

                        $candidatesEarliest [] = Carbon::parse($now);
                        $candidatesEarliest[] = $start_date;
                        if ($task->stage_code == 7 ){
                                $candidatesEarliest[] = Carbon::parse($task->after_parkaging_date);
                        }elseif ($task->stage_code == 3) {
                                $candidatesEarliest[] = Carbon::parse($task->after_weigth_date);
                        }

                        $earliestStart = collect($candidatesEarliest)->max();

                        foreach ($rooms as $room) { // duyệt qua toàn bộ các room đã định mức để tìm bestroom
                                $intervalTimeMinutes = (float) $room->p_time_minutes + (float) $room->m_time_minutes;
                                $C2_time_minutes =  (float) $room->C2_time_minutes;
                                if ($campaign_tasks !== null){ // chỉ thực hiện khi có chiến dịch
                                        $intervalTimeMinutes = (float) $room->p_time_minutes + (float) $room->m_time_minutes * $campaign_tasks->count() + (float) $room->C1_time_minutes * ($campaign_tasks->count()-1);
                                        $C2_time_minutes =  (float) $room->C2_time_minutes;
                                        $currCycle =  (float) $room->m_time_minutes;
                                        foreach ($campaign_tasks->reverse() as $campaign_task) {
                                                $pre_task_last_batch = DB::table ($stage_plan_table)
                                                ->where('code', $campaign_task->predecessor_code)
                                                ->when(session('fullCalender')['mode'] === 'temp',function ($query)
                                                        {return $query->where('stage_plan_temp_list_id',session('fullCalender')['stage_plan_temp_list_id']);})
                                                ->first();
                                                if ($pre_task_last_batch && $pre_task_last_batch->end !== null){break;}
                                        }
                                        $prevCycle = Carbon::parse($pre_task_last_batch->start)->diffInMinutes(Carbon::parse($pre_task_last_batch->end));

                                        if ($currCycle < $prevCycle && $pre_task_last_batch){
                                                $earliestStart = $pre_task_last_batch->end;
                                        }
                                }

                                $candidateStart = $this->findEarliestSlot2(
                                        $room->room_id,
                                        $earliestStart,
                                        $intervalTimeMinutes,
                                        $C2_time_minutes,
                                        $task->tank,
                                        $task->keep_dry,
                                        $stage_plan_table,
                                        2,
                                        60
                                );



                                if ($bestStart === null || $candidateStart->lt(Carbon::parse($bestStart))) {
                                        $bestRoom = $room;
                                        $bestRoomId = $room->room_id;
                                        $bestStart = $candidateStart;
                                        $bestEnd = $bestStart->copy()->addMinutes((float) $room->p_time_minutes + (float) $room->m_time_minutes);
                                        $bestEndCleaning  = $bestEnd->copy()->addMinutes( (float) $room->C2_time_minutes);
                                }
                        }

                        if ($campaign_tasks !== null){
                                $campaign_counter = 1;
                                $pre_stage_code = explode('_', $task->predecessor_code)[1];
                                if ($pre_stage_code > 2 && $waite_time_for_task){
                                        $bestStart = $bestStart->copy()->addMinutes($waite_time_for_task);
                                }

                                foreach ($campaign_tasks as $task){
                                        if ($campaign_counter == 1) {
                                                $bestEnd = $bestStart->copy()->addMinutes((float) $bestRoom->m_time_minutes);
                                                $bestEndCleaning = $bestEnd->copy()->addMinutes((float) $bestRoom->C1_time_minutes);
                                                $clearningType = 1;
                                                $prevCycle = ((float) $bestRoom->m_time_minutes + (float) $bestRoom->C1_time_minutes);

                                        }elseif ($campaign_counter == $campaign_tasks->count()){
                                                $bestEnd = $bestStart->copy()->addMinutes((float) $bestRoom->m_time_minutes);
                                                $bestEndCleaning = $bestEnd->copy()->addMinutes((float) $bestRoom->C2_time_minutes);
                                                $clearningType = 2;

                                        }else {
                                                $bestEnd = $bestStart->copy()->addMinutes((float) $bestRoom->m_time_minutes);
                                                $bestEndCleaning = $bestEnd->copy()->addMinutes((float) $bestRoom->C1_time_minutes);
                                                $clearningType = 1;
                                        }
                                        $title = $task->name ."- ". $task->batch ."- ". $task->market;

                                        $this->saveSchedule(
                                                $title,
                                                $task->id,
                                                $bestRoomId,
                                                $bestStart,
                                                $bestEnd,
                                                $bestEndCleaning,
                                                $clearningType,
                                                1
                                        );
                                        $bestStart = $bestEndCleaning;
                                        $campaign_counter++;
                                }
                        }else {
                                $title = $task->name ."- ". $task->batch ."- ". $task->market;
                                $this->saveSchedule(
                                        $title,
                                        $task->id,
                                        $bestRoomId,
                                        $bestStart,
                                        $bestEnd,
                                        $bestEndCleaning,
                                        2,
                                        1
                                );
                        }
                }
        }

        protected function findLatestSlot($roomId,$latestEnd,$beforeIntervalMinutes,$afterIntervalMinutes, $time_clearning_tank = 60,
                ?Carbon $startPoint = null, bool $requireTank = false,bool $requireAHU = false, int $maxTank = 2, string $stage_plan_table = 'stage_plan') {

                $this->loadRoomAvailability();
                $startPoint = $startPoint ?? Carbon::now();
                $AHU_group  = DB::table ('room')->where ('id',$roomId)->value('AHU_group');

                if (!isset($this->roomAvailability[$roomId])) {
                        $this->roomAvailability[$roomId] = [];
                }

                $busyList = collect($this->roomAvailability[$roomId])->sortByDesc('end');
                $current_end_clearning = Carbon::parse($latestEnd)->copy()->addMinutes($afterIntervalMinutes);

                $tryCount = 0;
                while (true) {
                        foreach ($busyList as $busy) {
                        // nếu current nằm SAU block bận
                                if ($current_end_clearning->gt($busy['end'])) {
                                        $gap = $current_end_clearning->diffInMinutes($busy['end']);
                                        if ($gap >= ($beforeIntervalMinutes + $afterIntervalMinutes)) {
                                                // kiểm tra tank nếu cần
                                                if ($requireTank !=0 ) {
                                                        $bestEnd = $current_end_clearning->copy()->subMinutes($afterIntervalMinutes);
                                                        $bestStart = $bestEnd->copy()->subMinutes($beforeIntervalMinutes);

                                                        $overlapTankCount = DB::table($stage_plan_table)
                                                                ->whereNotNull('start')
                                                                ->where('tank', 1)
                                                                ->whereIn('stage_code', [3, 4])
                                                                ->where('start', '<', $bestEnd)
                                                                ->where('end', '>', $bestStart)
                                                                ->count();

                                                        if ($overlapTankCount >= $maxTank) {
                                                        // Nếu tank đã đầy thì lùi thêm 15 phút và thử lại
                                                                $current_end_clearning = $bestStart->copy()->addMinutes($beforeIntervalMinutes + $time_clearning_tank);
                                                                $tryCount++;
                                                        if ($tryCount > 100) return false; // tránh vòng lặp vô hạn
                                                                continue 2; // quay lại while
                                                        }
                                                }

                                                if ($requireAHU !=0 && $AHU_group !=0) {
                                                        $bestEnd = $current_end_clearning->copy()->subMinutes($afterIntervalMinutes);
                                                        $bestStart = $bestEnd->copy()->subMinutes($beforeIntervalMinutes);

                                                        $overlapAHUCount = DB::table($stage_plan_table)
                                                                ->whereNotNull('start')
                                                                ->where('stage_code', 7)
                                                                ->where('keep_dry', 1)
                                                                ->where('AHU_group', $AHU_group)
                                                                ->where('start', '<', $bestEnd)
                                                                ->where('end', '>', $bestStart)
                                                        ->count();

                                                        if ($overlapAHUCount >= 3) {
                                                                $current_end_clearning = $bestStart
                                                                ->copy()
                                                                ->addMinutes($beforeIntervalMinutes);
                                                                $tryCount++;
                                                                if ($tryCount > 100) return false; // tránh vòng lặp vô hạn
                                                                continue 2; // quay lại vòng while
                                                        }
                                                }



                                                return $current_end_clearning;
                                        }
                                }

                                // nếu current rơi VÀO block bận
                                if ($current_end_clearning->gt($busy['start'])) {
                                        $current_end_clearning = $busy['start']->copy();
                                }
                        }

                        if (($current_end_clearning->copy()->subMinutes($beforeIntervalMinutes + $afterIntervalMinutes))->lt($startPoint)) {
                                return false;
                        }

                        // kiểm tra tank ở vị trí cuối cùng (ngoài busyList)
                        if ($requireTank !=0) {
                                $bestEnd = $current_end_clearning->copy()->subMinutes($afterIntervalMinutes);
                                $bestStart = $bestEnd->copy()->subMinutes($beforeIntervalMinutes);

                                $overlapTankCount = DB::table($stage_plan_table)
                                                                        ->whereNotNull('start')
                                                                        ->where('tank', 1)
                                                                        ->whereIn('stage_code', [3, 4])
                                                                        ->where('start', '<', $bestEnd)
                                                                        ->where('end', '>', $bestStart)
                                                                        ->count();
                                if ($overlapTankCount >= $maxTank) {
                                // $current_end_clearning = $bestStart->copy()->subMinutes(15);
                                        $current_end_clearning = $bestStart->copy()->addMinutes($beforeIntervalMinutes + $time_clearning_tank);
                                        $tryCount++;
                                        if ($tryCount > 100) return false;
                                        continue; // thử lại
                                }
                        }

                        if ($requireAHU !=0 && $AHU_group !=0) {
                                $bestEnd = $current_end_clearning->copy()->subMinutes($afterIntervalMinutes);
                                $bestStart = $bestEnd->copy()->subMinutes($beforeIntervalMinutes);

                                 $overlapAHUCount = DB::table($stage_plan_table)
                                                                ->whereNotNull('start')
                                                                ->where('stage_code', 7)
                                                                ->where('keep_dry', 1)
                                                                ->where('AHU_group', $AHU_group)
                                                                ->where('start', '<', $bestEnd)
                                                                ->where('end', '>', $bestStart)
                                ->count();

                                if ($overlapAHUCount >= $maxTank) {
                                // $current_end_clearning = $bestStart->copy()->subMinutes(15);
                                        $current_end_clearning = $bestStart->copy()->addMinutes($beforeIntervalMinutes);
                                        $tryCount++;
                                        if ($tryCount > 100) return false;
                                        continue; // thử lại
                                }
                        }

                        return $current_end_clearning;
                }
        }

        protected function findEarliestSlot2($roomId, $Earliest, $intervalTime, $C2_time_minutes, $requireTank = 0, $requireAHU = 0, $stage_plan_table = 'stage_plan',  $maxTank = 1, $tankInterval = 60){

                $this->loadRoomAvailability();

                if (!isset($this->roomAvailability[$roomId])) {
                        $this->roomAvailability[$roomId] = [];
                }

                $busyList = $this->roomAvailability[$roomId]; // danh sách block bận
                $current_start = $Earliest instanceof Carbon ? $Earliest : Carbon::parse($Earliest);
                $AHU_group  = DB::table ('room')->where ('id',$roomId)->value('AHU_group');

                $tryCount = 0;

                while (true) {
                        foreach ($busyList as $busy) {

                                if ($current_start->lt($busy['start'])) {
                                        $gap = $current_start->diffInMinutes($busy['start']);
                                        if ($gap >= $intervalTime + $C2_time_minutes) {

                                        // --- kiểm tra tank ---
                                        if ($requireTank !=0){
                                                $bestEnd   = $current_start->copy()->addMinutes($intervalTime);
                                                $bestStart = $current_start->copy();

                                                $overlapTankCount = DB::table($stage_plan_table) // thay bằng $stage_plan_table nếu cần
                                                ->whereNotNull('start')
                                                ->where('tank', 1)
                                                ->whereIn('stage_code', [3, 4])
                                                ->where('start', '<', $bestEnd)
                                                ->where('end', '>', $bestStart)
                                                ->count();

                                                if ($overlapTankCount >= $maxTank) {
                                                        // Nếu tank đã đầy → dời thêm $tankInterval phút rồi thử lại
                                                        $current_start = $busy['end']->copy()->addMinutes($tankInterval);
                                                        $tryCount++;
                                                        if ($tryCount > 100) return false; // tránh vòng lặp vô hạn
                                                        continue 2; // quay lại while
                                                }
                                        }

                                        if ($requireAHU !=0 && $AHU_group !=0) {
                                                $bestEnd = $current_start->copy()->addMinutes($intervalTime);
                                                $bestStart = $current_start->copy();

                                                $overlapAHUCount = DB::table($stage_plan_table)
                                                        ->whereNotNull('start')
                                                        ->where('stage_code', 7)
                                                        ->where('keep_dry', 1)
                                                        ->where('AHU_group', $AHU_group)
                                                        ->where('start', '<', $bestEnd)
                                                        ->where('end', '>', $bestStart)
                                                ->count();

                                                if ($overlapAHUCount >= 3) {
                                                        $current_start = $busy['end']->copy()->addMinutes($tankInterval);
                                                        $tryCount++;
                                                        if ($tryCount > 100) return false; // tránh vòng lặp vô hạn
                                                        continue 2; // quay lại vòng while
                                                }
                                        }

                                return $current_start;
                                }
                        }

                        // nếu current rơi VÀO block bận
                                if ($current_start->lt($busy['end'])) {
                                        // nhảy tới ngay sau block bận
                                        $current_start = $busy['end']->copy();
                                }
                        }

                        // nếu không vướng block nào → kiểm tra tank trước khi trả về
                                if ($requireTank !=0) {
                                        $bestEnd   = $current_start->copy()->addMinutes($intervalTime);
                                        $bestStart = $current_start->copy();

                                        $overlapTankCount = DB::table('stage_plan')
                                                ->whereNotNull('start')
                                                ->where('tank', 1)
                                                ->whereIn('stage_code', [3, 4])
                                                ->where('start', '<', $bestEnd)
                                                ->where('end', '>', $bestStart)
                                                ->count();

                                        if ($overlapTankCount >= $maxTank) {
                                                $current_start->addMinutes($tankInterval);
                                                $tryCount++;
                                                if ($tryCount > 100) return false;
                                                continue; // quay lại while
                                        }

                                }


                                if ($requireAHU !=0 && $AHU_group !=0) {
                                                $bestEnd = $current_start->copy()->addMinutes($intervalTime);
                                                $bestStart = $current_start->copy();

                                                $overlapAHUCount = DB::table($stage_plan_table)
                                                        ->whereNotNull('start')
                                                        ->where('stage_code', 7)
                                                        ->where('keep_dry', 1)
                                                        ->where('AHU_group', $AHU_group)
                                                        ->where('start', '<', $bestEnd)
                                                        ->where('end', '>', $bestStart)
                                                ->count();

                                                if ($overlapAHUCount >= 3) {
                                                        $current_start->addMinutes(15);
                                                        $tryCount++;
                                                        if ($tryCount > 100) return false; // tránh vòng lặp vô hạn
                                                        continue ; // quay lại vòng while
                                                }
                                        }

                        return $current_start;
                }
        }

        protected function findQuarantineTimeHours ($intermediate_code, $stage_code) {

                $intermediate = DB::table('intermediate_category')
                        ->where('intermediate_code', $intermediate_code)
                        ->first();

                if (!$intermediate) {
                        return 0; // hoặc throw exception
                }

                // map stage_code -> column
                $map = [
                        1 => 'quarantine_weight',
                        2 => 'quarantine_weight',
                        3 => 'quarantine_preparing',
                        4 => 'quarantine_blending',
                        5 => 'quarantine_forming',
                        6 => 'quarantine_coating',
                ];

                if (!isset($map[$stage_code])) {
                        return 0; // stage_code không hợp lệ
                }

                $value = $intermediate->{$map[$stage_code]} ?? 0;

                if (!$value) {
                        return 0; // chưa khai báo thời gian
                }

                // Nếu quarantine_time_unit = 1 (ngày) → đổi sang giờ
                if ($intermediate->quarantine_time_unit == 1) {
                        return $value * 24;
                }

                return $value; // ngược lại là giờ

        } // chưa dùng


}

      function toMinutes($time) {
                [$hours, $minutes] = explode(':', $time);
                return ((int)$hours) * 60 + (int)$minutes;
        }



        // Log::info('getEvents:', [
        //         'task' => $task,
        //         'stageCode' => $stageCode,
        //         'waite_time' => $waite_time,
        //         'start_date' => $start_date,
        // ]);