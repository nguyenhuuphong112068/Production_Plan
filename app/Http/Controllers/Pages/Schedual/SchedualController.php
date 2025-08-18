<?php

namespace App\Http\Controllers\Pages\Schedual;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Carbon\Carbon;

class SchedualController extends Controller
{       
        // Xem Calender
        public function view(){

                $plans = DB::table('stage_plan')
                ->select(
                        'id',
                        'title',
                        'start',
                        'end',
                        'start_clearning',
                        'end_clearning',
                        'title_clearning',
                        'resourceId',
                        'plan_master_id',
                        'stage_code'
                )
                ->where('finished', 0)
                ->where('active', 1)
                ->get();

                $events = collect();

                foreach ($plans as $plan) {
                // Event chính (sản xuất)
                if ($plan->start && $plan->end) {
                        $events->push([
                        'plan_id' => $plan->id,
                        'id' => "{$plan->id}-main",
                        //'groupId' => $plan->id,
                        'title' => $plan->title,
                        'start' => $plan->start,
                        'end' => $plan->end,
                        'resourceId' => $plan->resourceId,
                        'color' => '#7bed52ff', // màu xanh sản xuất
                        'plan_master_id'=> $plan->plan_master_id,
                        'stage_code'=> $plan->stage_code,
                        'is_clearning' => false,
                       
                        ]);
                }
                // Event vệ sinh
                if ($plan->start_clearning && $plan->end_clearning) {
                        $events->push([
                        'plan_id' => $plan->id,
                        'id' => "{$plan->id}-cleaning",
                        //'groupId' => $plan->id,
                        'title' => $plan->title_clearning ?? 'Vệ sinh',
                        'start' => $plan->start_clearning,
                        'end' => $plan->end_clearning,
                        'resourceId' => $plan->resourceId,
                        'color' => '#a1a2a2ff', // màu xám vệ sinh
                        'plan_master_id'=> $plan->plan_master_id,
                        'stage_code'=> $plan->stage_code,
                        'is_clearning' => true,
                        
                        ]);
                }
                }
                

                $resources = DB::table('room')
                                ->select('id', DB::raw("CONCAT(name, '-', code) as title"), 'stage', 'production_group')
                                ->where('active', 1)
                                ->orderBy('order_by', 'asc')
                                ->get();
                
                $plan = DB::table('stage_plan')
                                ->select('stage_plan.*',
                                        'finished_product_category.name',
                                        'finished_product_category.market',
                                        'finished_product_category.intermediate_code',
                                        'finished_product_category.finished_product_code',
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
                                        'source_material.name as source_material_name'
                                        )
                                ->leftJoin('finished_product_category', 'stage_plan.product_caterogy_id', '=', 'finished_product_category.id')
                                ->leftJoin('plan_master', 'stage_plan.plan_master_id', '=', 'plan_master.id')
                                ->join('source_material', 'plan_master.material_source_id', '=', 'source_material.id')
                                ->whereNull('stage_plan.start')->where('stage_plan.finished', 0)->where('stage_plan.active', 1)
                                ->orderBy('stage_plan.order_by', 'asc')
                                ->get();
         
                $quota = DB::table('quota')
                ->where('active', 1)
                ->get()
                ->map(function ($item) {
                        // Hàm chuyển từ "H:i" sang giây
                        $toSeconds = function ($time) {
                        [$h, $m] = explode(':', $time);
                        return ((int)$h * 3600) + ((int)$m * 60);
                        };

                        // Hàm chuyển từ giây về "H:i"
                        $toTime = function ($seconds) {
                        $h = floor($seconds / 3600);
                        $m = floor(($seconds % 3600) / 60);
                        return sprintf('%02d:%02d', $h, $m);
                        };

                        // Tính các giá trị thời gian
                        $p = $toSeconds($item->p_time);
                        $m = $toSeconds($item->m_time);
                        $c1 = $toSeconds($item->C1_time);
                        $c2 = $toSeconds($item->C2_time);

                        // Gán thêm các cột tổng hợp
                        $item->PM = $toTime($p + $m);
                        return $item;
                });

       

                // Gắn devices vào từng plan
                $plan->transform(function ($item) use ($quota) {
                        
                        if ($item->stage_code < 7) {
                                // Lấy theo intermediate_code
                                $item->devices = $quota[$item->intermediate_code] ?? collect();
                        
                        } else {
                                // Lấy theo finished_product_code
                                $item->devices = $quota[$item->finished_product_code] ?? collect();
                        }
                        return $item;
                });

               

                // $plan->each(function($p) {
                //         dump($p->devices);
                // });

                return Inertia::render('FullCalender', [
                        'title' => 'Lịch Sản Xuất',
                        'user' => session('user'),
                        'events' => $events,
                        'resources' => $resources,
                        'plan' => $plan,
                        'quota' => $quota
                ]);
        }

        public function index(){
                        $analysts = DB::table('analyst')->where ('active',1)->orderBy('created_at','desc')->get();
                        $instruments = DB::table('instrument')->where ('active',1)->orderBy('created_at','desc')->get();
        
                        $imports = DB::table('import')
                        ->select('import.*', 'product_category.name', 'product_category.code', 'product_category.testing','product_category.testing_code', 
                                'product_category.sample_Amout', 'product_category.unit', 'product_category.excution_time','product_category.instrument_type',)
                        ->where ('import.Active',1)->where('import.finished',0)->where('import.scheduled',0)
                        ->leftJoin('product_category', 'import.testing_code', 'product_category.testing_code')
                        ->orderBy('experted_date','asc')->get();
                        
                        $datas = DB::table('schedules')
                        ->select(
                                'schedules.*',
                                'product_category.name',
                                'product_category.code',
                                'product_category.testing',
                                'import.batch_no',
                                'import.experted_date',
                                'import.stage',
                                'import.imoported_amount',
                                'product_category.unit',
                                'import.id as imported_id',
                                'instrument.name as ins_name',
                                'product_category.excution_time',
                                
                        )
                        ->where('schedules.finished', 0)->where('schedules.active', 1)
                        ->leftJoin('import', 'schedules.imported_id', '=', 'import.id')
                        ->leftJoin('instrument', 'schedules.ins_Id', '=', 'instrument.id')
                        ->leftJoin('product_category', 'import.testing_code', '=', 'product_category.testing_code')
                        ->get();
                
                        session()->put(['title'=> 'Danh Sách Mẫu Chờ Kiểm']);
        
                        return view('pages.Schedual.list',['datas' => $datas,'imports' => $imports, 'instruments' => $instruments,  'analysts' => $analysts])
                        ->with('instrument_type', request()->get('instrument_type'));;
        }
    
        public function store (Request $request) {
                
                // $validator = Validator::make($request->all(), [
                //         'analyst'    => 'required',
                //         'startDate'  => 'required|date',
                //         'endDate'    => 'required|date|after_or_equal:startDate',
                //         'ins_Id' => 'required',
                //         'imported_id'=> 'required'
                // ], [
                //         'analyst.required' => 'Vui lòng chọn kiểm nghiệm viên',
                //         'startDate.required' => 'Vui lòng chọn ngày kiểm',
                //         'endDate.required' => 'Vui lòng chọn ngày kết thúc',
                //         'ins_Id.required' => 'Vui lòng chọn thiết bị kiểm',
                //         'imported_id.required' => 'Không có sản phẩm được chọn',
                // ]);
               

                // if ($validator->fails()) {
                //         return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
                // }
          
                try {
                        DB::table('stage_plan')
                        ->where('id', $request->id)
                        ->update([
                                'start' => $request->start,
                                'end' => $request->end,
                                'start_clearning' => $request->end,
                                'end_clearning' => $request->C_end,
                                'resourceId' => $request->resourceId,
                                'title' => $request->title,
                                'title_clearning' => $request->title . " Vệ Sinh 2",
                                'schedualed' => 1,
                                'schedualed_by' =>  session('user')['fullName'],
                                'schedualed_at' => now(),
                        ]);


                } catch (\Exception $e) {
                        Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);       
                }

        }

        public function multiStore(Request $request){
              
                try {
                $start = Carbon::parse($request->start); // ✅ chuyển về Carbon
                $quota = $request->quota;
                
                // Chuyển định dạng giờ phút thành số phút
                $p_time_minutes = toMinutes($quota['p_time']);
                $m_time_minutes = toMinutes($quota['m_time']);
                $C1_time_minutes = toMinutes($quota['C1_time']);
                $C2_time_minutes = toMinutes($quota['C2_time']);
                $total = count($request->draggedRows);
                

                foreach ($request->draggedRows as $index => $row) {


                if ($index === 0) {
                        // 🎯 Giai đoạn đầu tiên
                        $start_man = $start->copy();
                        $end_man = $start->copy()->addMinutes($p_time_minutes + $m_time_minutes);
                        $start_clear = $end_man->copy();
                        $end_clear = $start_clear->copy()->addMinutes($C1_time_minutes);
                        $clearning_type = "VS-II";
                       
                } elseif ($index === $total - 1) {
                        // 🎯 Giai đoạn cuối cùng
                        $start_man = $end_clear->copy();
                        $end_man = $start_man->copy()->addMinutes($m_time_minutes);
                        $start_clear = $end_man->copy();
                        $end_clear = $start_clear->copy()->addMinutes($C2_time_minutes);
                        $clearning_type = "VS-II";
                } else {
                        // 🎯 Giai đoạn ở giữa
                        $start_man = $end_clear->copy();
                        $end_man = $start_man->copy()->addMinutes($m_time_minutes);
                        $start_clear = $end_man->copy();
                        $end_clear = $start_clear->copy()->addMinutes($C1_time_minutes);
                        $clearning_type = "VS-I";
                }

                DB::table('stage_plan')
                        ->where('id', $row['id'])
                        ->update([
                        'start' => $start_man->format('Y-m-d H:i:s'),
                        'end' => $end_man->format('Y-m-d H:i:s'),
                        'start_clearning' => $start_clear->format('Y-m-d H:i:s'),
                        'end_clearning' => $end_clear->format('Y-m-d H:i:s'),
                        'resourceId' => $request->resourceId,
                        'title' => $row['name'] . " - " . $row['batch'] . " - " . $row['market'],
                        'title_clearning' => $clearning_type,
                        'schedualed' => 1,
                        'schedualed_by' => session('user')['fullName'],
                        'schedualed_at' => now(),
                        ]);
                }

                } catch (\Exception $e) {
                Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);
                }
        }

        public function update(Request $request){
            
                try {
                if (strpos($request->title, "VS-I") !== false ) {
                DB::table('stage_plan')
                        ->where('id', $request->id)
                        ->update([
                        'start_clearning' => $request->start,
                        'end_clearning' => $request->end,
                        'resourceId' => $request->resourceId,
                        'schedualed_by' => session('user')['fullName'],
                        'schedualed_at' => now(),
                        ]);
                } else {
                DB::table('stage_plan')
                        ->where('id', $request->id)
                        ->update([
                        'start' => $request->start,
                        'end' => $request->end,
                        'resourceId' => $request->resourceId,
                        'schedualed_by' => session('user')['fullName'],
                        'schedualed_at' => now(),
                        ]);
                }

                

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

                // Lấy plan_master_id từ 1 row
                $plan = DB::table('stage_plan')->where('id', $rowId)->first();

                if ($plan) {
                        // Update tất cả stage_plan theo rule
                        DB::table('stage_plan')
                        ->where('plan_master_id', $plan->plan_master_id)
                        ->where('stage_code', '>=', $stageCode)
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
                        //return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
                }

        
        }

        public function deActiveAll(){
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
                                        'title' => null,
                                        'title_clearning' => null,
                                        'schedualed' => 0,
                                        'schedualed_by' =>  session('user')['fullName'],
                                        'schedualed_at' => now(),
                        ]);

                        DB::table('room_status')
                                ->whereIn('stage_plan_id',  $ids)
                                ->delete();                        

                } catch (\Exception $e) {
                        Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);       
                }
        }

        public function finished(Request $request){
               
                $validator = Validator::make($request->all(), [
                        'analyst'    => 'required',
                        'startDate'  => 'required|date',
                        'endDate'    => 'required|date|after_or_equal:startDate',
                        'ins_Id' => 'required',
                        'schedual_id'=> 'required',
                        'result'=> 'required',
                        'relativeReport' => 'required'
                ], [
                        'analyst.required' => 'Vui lòng chọn kiểm nghiệm viên',
                        'startDate.required' => 'Vui lòng chọn ngày kiểm',
                        'endDate.required' => 'Vui lòng chọn ngày kết thúc',
                        'ins_Id.required' => 'Vui lòng chọn thiết bị kiểm',
                        'schedual_id.required' => 'Không có sản phẩm được chọn',
                        'result.required' => 'Vui lòng chọn kết quả',
                        'relativeReport.required' => 'Vui lòng nhập số báo cáo liên quan, nếu không nhập NA'
                ]);
      

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'createHistoryErrors')->withInput();
                }
                
              
                $check= DB::table('history')->insert([
                        
                        'schedual_id' => $request->schedual_id,
                        'analyst' => $request->analyst,
                        'startDate' => $request->startDate,
                        'endDate'  => $request->endDate,
                        'ins_Id'  => $request->ins_Id,
                        'note'  => $request->note,
                        'result'  => $request->result,
                        'relativeReport'  => $request->relativeReport,
                        'prepareBy' => session('user')['fullName'] ?? 'Admin',
                        'created_at' => now(),
                ]);
                
                if ($check){
                        DB::table('import')->where('id', $request->imported_id)->update(['finished' => 1]);
                        DB::table('schedules')->where('id', $request->schedual_id)->update(['finished' => 1]);

                        $datas = DB::table('history')
                        ->select(
                                'history.*',
                                'product_category.code','product_category.name',
                                'product_category.testing',
                                'import.batch_no','import.stage',
                                'instrument.name as instrument_name'
                        )
                        ->join('schedules', 'history.schedual_id', '=', 'schedules.id')
                        ->join('import', 'schedules.imported_id', '=', 'import.id')
                        ->join('product_category', 'import.testing_code', '=', 'product_category.testing_code')
                        ->join('instrument', 'history.ins_id', '=', 'instrument.id')
                        ->get();

                
                        session()->put(['title'=> 'Lịch Sử Kiểm Nghiệm']);

                        return view('pages.History.list',['datas' => $datas])->with('success', 'Thành công!');
                }
                else {
                        return redirect()->back()->withErrors($validator, 'createHistoryErrors');
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

        ///////// Các hàm liên Auto Schedualer
        protected $roomAvailability = [];

        public function __construct() {
                $this->loadRoomAvailability();
        }

        /**
         * Load room_status để lấy các slot đã bận
         */
        protected function loadRoomAvailability() {
                $schedules = DB::table('room_status')->orderBy('start')->get();
                foreach ($schedules as $row) {
                $this->roomAvailability[$row->room_id][] = [
                        'start' => Carbon::parse($row->start),
                        'end'   => Carbon::parse($row->end)
                ];
        }}

        /**
         * Tìm slot trống sớm nhất trong phòng
         */
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


        /**
         * Ghi kết quả vào stage_plan + log vào room_status
         */
        protected function saveSchedule($title, $stageId, $roomId, Carbon $start, Carbon $end,  ?Carbon $endCleaning = null, ?string $cleaningType = null) {
                
                DB::transaction(function() use ($title, $stageId, $roomId, $start, $end,  $endCleaning, $cleaningType) {

                if ($cleaningType == null){$titleCleaning = "VS-II";} else {$titleCleaning = "VS-I";}

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

        if ($start && $endCleaning) {
                $this->roomAvailability[$roomId][] = ['start'=>$start,'end'=>$endCleaning];
        }
                usort($this->roomAvailability[$roomId], fn($a,$b)=>$a['start']->lt($b['start']) ? -1 : 1);
        }


        /**
         * Scheduler cho 1 stage
         */
        public function scheduleStage($stageCode) {
                $tasks = DB::table('stage_plan')
                ->select('stage_plan.id',
                        'stage_plan.code', 
                        'stage_plan.predecessor_code', 
                        'plan_master.batch',
                        'plan_master.after_weigth_date',
                        'plan_master.before_weigth_date',
                        'plan_master.after_parkaging_date',
                        'plan_master.before_parkaging_date',
                        'finished_product_category.name',
                        'finished_product_category.market',
                        'finished_product_category.finished_product_code',
                        'finished_product_category.intermediate_code',
                )
                ->leftJoin('plan_master', 'stage_plan.plan_master_id', 'plan_master.id')
                ->leftJoin('finished_product_category', 'stage_plan.product_caterogy_id', 'finished_product_category.id')
                ->where('stage_code', $stageCode)
                ->whereNull('start')
                ->orderBy('order_by','asc')
                ->get();
                
                foreach ($tasks as $task) {
                        $title = $task->name ."- ". $task->batch ."- ". $task->market;


                        $now = Carbon::now();
                        // Lấy phút hiện tại
                        $minute = $now->minute;
                        // Tính số phút làm tròn lên
                        $roundedMinute = ceil($minute / 15) * 15;
                        // Nếu bằng 60, tăng giờ lên 1 và đặt phút = 0
                        if ($roundedMinute == 60) {$now->addHour();$roundedMinute = 0;}

                        $now->minute($roundedMinute)->second(0)->microsecond(0);
                        // Ví dụ kết hợp với logic của bạn
                        $earliestStart = $task->after_weigth_date? Carbon::parse($task->after_weigth_date)->gte(now()) ? Carbon::parse($task->after_weigth_date) : $now: $now;

                        if ($task->predecessor_code) {
                                $pred = DB::table('stage_plan')->where('code', $task->predecessor_code)->first();
                                if ($pred && $pred->end) {
                                        $predEnd = Carbon::parse($pred->end);
                                        if ($predEnd->gt($earliestStart)) {
                                                $earliestStart = $predEnd;
                        }}}

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
                        }
                        
                        $endCleaning = $candidateEnd->copy()->addHours((int) $room->C2_time_hours);
                       
                        $this->saveSchedule(
                                $title,
                                $task->id,
                                $bestRoom,
                                $bestStart,
                                $bestEnd,
                                $endCleaning, 
                                null,
                            
                        );
                }

                //return response()->json(['message'=>"Stage {$stageCode} scheduled"]);
        }

        /**
         * Scheduler nhiều stage
         */
        // 1 hàm khỏi động
        public function scheduleAll() {
               
                $stageCodes = DB::table('stage_plan')
                ->distinct()
                ->orderBy('stage_code')
                ->pluck('stage_code');
                
                foreach ($stageCodes as $stageCode) {
                        $this->scheduleStage($stageCode);
                }
              
                //return response()->json(['message'=>"All stages scheduled"]);
        }
        

}
      function toMinutes($time) {
                [$hours, $minutes] = explode(':', $time);
                return ((int)$hours) * 60 + (int)$minutes;
        }

        