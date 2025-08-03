<?php

namespace App\Http\Controllers\Pages\Schedual;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

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
                        'id' => "{$plan->id}-main",
                        //'groupId' => $plan->id,
                        'title' => $plan->title,
                        'start' => $plan->start,
                        'end' => $plan->end,
                        'resourceId' => $plan->resourceId,
                        'color' => '#7bed52ff', // màu xanh sản xuất
                        'plan_master_id'=> $plan->plan_master_id,
                        'stage_code'=> $plan->stage_code,
                        'is_clearning' => false
                        ]);
                }
                // Event vệ sinh
                if ($plan->start_clearning && $plan->end_clearning) {
                        $events->push([
                        'id' => "{$plan->id}-cleaning",
                        //'groupId' => $plan->id,
                        'title' => $plan->title_clearning ?? 'Vệ sinh',
                        'start' => $plan->start_clearning,
                        'end' => $plan->end_clearning,
                        'resourceId' => $plan->resourceId,
                        'color' => '#a1a2a2ff', // màu xám vệ sinh
                        'plan_master_id'=> $plan->plan_master_id,
                        'stage_code'=> $plan->stage_code,
                        'is_clearning' => true
                        ]);
                }
                }

                $resources = DB::table('room')
                                ->select('id', DB::raw("CONCAT(name, '-', code) as title"), 'stage')
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
                                        'plan_master.note'
                                        
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
                        $item->PMC1 = $toTime($p + $m + $c1);
                        $item->MC1  = $toTime($m + $c1);
                        $item->MC2  = $toTime($m + $c2);
                        $item->PMC2 = $toTime($p + $m + $c2);

                        return $item;
                });

        

                
                return Inertia::render('FullCalender', [
                        'title' => 'Lịch Sản XUất',
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

        // Cap nhạt lại Calender khi có thay đổi vị trí lịch hoặc thay đổi thời gian
        public function update(Request $request){
                try {
                        // Nếu chỉ update 1 event (resize)
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


        public function deActive( int|string $id){
                try {
                        DB::table('stage_plan')
                        ->where('id', $id)
                        ->update([
                                'start' => null,
                                'end' => null,
                                'resourceId' => null,
                                'title' => null,
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





}
