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
                                        'plan_master.material_source',
                                        'plan_master.only_parkaging',
                                        'plan_master.percent_parkaging'
                                        )
                                ->leftJoin('finished_product_category', 'stage_plan.product_caterogy_id', '=', 'finished_product_category.id')
                                ->leftJoin('plan_master', 'stage_plan.plan_master_id', '=', 'plan_master.id')
                                ->where('stage_plan.schedualed', 0)->where('stage_plan.finished', 0)->where('stage_plan.active', 1)
                                ->orderBy('plan_master.level', 'asc')->orderBy('plan_master.expected_date', 'asc')->orderBy('plan_master.batch', 'asc')
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

        

                
                return Inertia::render('FullCalender', [
                        'title' => 'Lịch Sản Xuất',
                        'user' => session('user'),
                        'events' => $events,
                        'resources' => $resources,
                        'plan' => $plan,
                        'quota' => $quota
                ]);
        }

        // 
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
                        $clearning_type = "Vệ Sinh Cấp I";
                       
                } elseif ($index === $total - 1) {
                        // 🎯 Giai đoạn cuối cùng
                        $start_man = $end_clear->copy();
                        $end_man = $start_man->copy()->addMinutes($m_time_minutes);
                        $start_clear = $end_man->copy();
                        $end_clear = $start_clear->copy()->addMinutes($C2_time_minutes);
                        $clearning_type = "Vệ Sinh Cấp II";
                } else {
                        // 🎯 Giai đoạn ở giữa
                        $start_man = $end_clear->copy();
                        $end_man = $start_man->copy()->addMinutes($m_time_minutes);
                        $start_clear = $end_man->copy();
                        $end_clear = $start_clear->copy()->addMinutes($C1_time_minutes);
                        $clearning_type = "Vệ Sinh Cấp I";
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
                if (strpos($request->title, "Vệ Sinh") !== false ) {
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

        public function deActive( Request $request){
               $ids = collect($request->input('ids'))
                        ->map(function ($value) {
                                return explode('-', $value)[0]; // lấy phần trước dấu '-'
                        })
                        ->unique() // loại bỏ trùng lặp
                        ->values(); // reset key
                        
                try {
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

  

}
      function toMinutes($time) {
                // Chuyển "01:30" thành phút
                [$hours, $minutes] = explode(':', $time);
                return ((int)$hours) * 60 + (int)$minutes;
        }
        