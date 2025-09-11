<?php

namespace App\Http\Controllers\Pages\Plan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MaintenancePlanController extends Controller
{
       public function index()
       {   
                $datas = DB::table('plan_list')
                ->where ('active',1)
                ->where ('type',0)
                ->orderBy('created_at','desc')->get();

                session()->put(['title'=> 'KẾ HOẠCH BẢO TRÌ THÁNG']);
        
                return view('pages.plan.maintenance.plan_list',[
                        'datas' => $datas
                ]);
        }
        
        public function create_plan_list (Request $request) {
                       
                 DB::table('plan_list')->insert([
                        'name' => $request->name,
                        'month' => date('m'),
                        'type' => 0,
                        'send' => false,
                        'deparment_code'  => session('user')['production_code'],
                        'prepared_by' => session('user')['fullName'],
                        'created_at' => now(),
                ]);
                return redirect()->back()->with('success', "Tạo Mới $request->name Thành Công!");

        }

        public function open(Request  $request){
                
                $datas = DB::table('plan_master')
                ->select(
                        'plan_master.*',
                        'maintenance_category.code',
                        'maintenance_category.name',
                        'room.name as room_name',
                        'room.code as room_code'
                )
                ->where('plan_list_id', $request->plan_list_id)
                ->where('plan_master.active', 1)
                ->leftJoin('maintenance_category', 'plan_master.product_caterogy_id', '=', 'maintenance_category.id')
                ->leftJoin('room', 'maintenance_category.room_id', '=', 'room.id')
                ->orderBy('level', 'asc')
                ->orderBy('expected_date', 'asc')
                ->get()
                ->groupBy('id')   // group theo id của plan_master
                ->map(function ($items) {
                        $first = $items->first();

                        // Danh sách room_code - room_name
                        $first->rooms = $items->map(function ($item) {
                        return $item->room_code . ' - ' . $item->room_name;
                        })
                        ->filter()
                        ->unique()
                        ->implode(', ');

                        return $first;
                })
                ->values();

                

                $planMasterIds = $datas->pluck('id')->toArray();

                $historyCounts = DB::table('plan_master_history')
                        ->select('plan_master_id', DB::raw('COUNT(*) as total'))
                        ->whereIn('plan_master_id', $planMasterIds)
                        ->groupBy('plan_master_id')
                        ->pluck('total', 'plan_master_id')
                        ->toArray();
                        $datas = $datas->map(function($item) use ($historyCounts) {
                        $item->history_count = $historyCounts[$item->id] ?? 0; // nếu không có history thì = 0
                        return $item;
                });
               
                $maintenance_category = DB::table('maintenance_category')
                ->select(
                        'maintenance_category.*',
                        'room.name as room_name',
                        'room.code as room_code',
                        'room.id as room_id'
                )
                ->leftJoin('room', 'maintenance_category.room_id', '=', 'room.id')
                ->where('maintenance_category.active', 1)
                ->orderBy('maintenance_category.name', 'asc')
                ->get()
                ->groupBy('code')
                ->map(function ($items) {
                        $first = $items->first(); // lấy bản ghi gốc

                        // Danh sách room_code - room_name
                        $first->rooms = $items->map(function ($item) {
                                return $item->room_code . ' - ' . $item->room_name;
                        })
                        ->filter()
                        ->unique()
                        ->implode(', ');

                        // Danh sách maintenance_category_id -> chuỗi ngăn cách bằng dấu phẩy
                        $first->maintenance_category_ids = $items->pluck('id')
                        ->filter()
                        ->unique()
                        ->implode(',');

                        return $first;
                })
                ->values();

              

                $production  =  session('user')['production_name'];
                session()->put(['title'=> " $request->name - $production"]);
        
                return view('pages.plan.maintenance.list',[
                        'datas' => $datas, 
                        'plan_list_id' => $request->plan_list_id,
                        'month' => $request->month, 
                        'production' => $request->production,
                        'category' => $maintenance_category,
                        'send'=> $request->send??1,
                        
                ]);
        }

        public function store(Request $request)
        {
        // Validate
        $validator = Validator::make($request->all(), [
                'devices.*.expected_date' => 'required|date',
        ], [
                'devices.*.expected_date.required' => 'Vui lòng chọn ngày dự kiến KCS cho tất cả thiết bị',
        ]);

        if ($validator->fails()) {
                return redirect()->back()
                ->withErrors($validator, 'create_Errors')
                ->withInput();
        }

        $now            = now();
        $preparedBy     = session('user')['fullName'];
        $departmentCode = session('user')['production_code'];

        DB::beginTransaction();
        try {
                $planMasterHistoryData = [];

                foreach ($request->devices as $device) {
                // Tách nhiều maintenance_category_ids
                $maintenanceCategoryIds = explode(',', $device['maintenance_category_ids']);

                foreach ($maintenanceCategoryIds as $catId) {
                        // Insert từng dòng vào plan_master để lấy id
                        $pmId = DB::table('plan_master')->insertGetId([
                        "product_caterogy_id" => $catId,
                        "plan_list_id"        => $request->plan_list_id,
                        "batch"               => "NA",
                        "expected_date"       => $device['expected_date'],
                        "level"               => 1,
                        "is_val"              => 0,
                        "percent_parkaging"   => 1,
                        "only_parkaging"      => 0,
                        "note"                => $device['note'] ?? "NA",
                        "deparment_code"      => $departmentCode,
                        "prepared_by"         => $preparedBy,
                        "created_at"          => $now,
                        ]);

                        // Chuẩn bị dữ liệu cho history
                        $planMasterHistoryData[] = [
                        "plan_master_id"      => $pmId,
                        "plan_list_id"        => $request->plan_list_id,
                        "product_caterogy_id" => $catId,
                        "batch"               => "NA",
                        "expected_date"       => $device['expected_date'],
                        "level"               => 1,
                        "is_val"              => 0,
                        "percent_parkaging"   => 1,
                        "only_parkaging"      => 0,
                        "note"                => $device['note'] ?? "NA",
                        "deparment_code"      => $departmentCode,
                        "prepared_by"         => $preparedBy,
                        "created_at"          => $now,
                        "updated_at"          => $now,
                        "version"             => 1,
                        ];
                }
                }

                // Insert nhiều dòng vào plan_master_history
                DB::table('plan_master_history')->insert($planMasterHistoryData);

                DB::commit();
                return redirect()->back()->with('success', 'Đã thêm thành công!');
        } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Lỗi khi thêm dữ liệu: ' . $e->getMessage());
        }
        }


        public function history(Request $request) {
                //dd ($request->all());

               $histories = DB::table('plan_master_history')
                ->select(
                        'plan_master_history.*', 
                        'maintenance_category.code', 
                        'maintenance_category.name', 
                        DB::raw('CONCAT(room.code, " - ", room.name) as room')
                )
                ->where('plan_master_history.plan_master_id', $request->id)
                ->leftJoin('maintenance_category', 'plan_master_history.product_caterogy_id', '=', 'maintenance_category.id')
                ->leftJoin('room', 'maintenance_category.room_id', '=', 'room.id')
                ->orderBy('version', 'desc')
                ->orderBy('expected_date', 'asc')
                ->get();
                        
                 return response()->json($histories);
        }

        public function deActive(Request $request){
                
                $reason = $request->deactive_reason;

                $updatesql = [
                        'prepared_by' => session('user')['fullName'],
                        'updated_at' => now(),
                ];

                if ($request->type === 'delete') {
                        $updatesql['active'] = 0;
                } elseif ($request->type === 'cancel') {
                        $updatesql['cancel'] = 1;
                        $active_stage_plan = 0;
                } elseif ($request->type === 'restore') {
                        $updatesql['cancel'] = 0;
                        $active_stage_plan = 1;
                }

                DB::table('plan_master')->where('id', $request->id)->update($updatesql);

                $latest = DB::table('plan_master_history')
                ->where('plan_master_id', $request->id)
                ->orderByDesc('version')
                ->first();

                if ($latest) {
                        DB::table('plan_master_history')
                        ->where('id', $latest->id)
                        ->update(['reason' => $reason]);
                }

                if ($request->type !== 'delete'){
                        DB::table('stage_plan')->where('plan_master_id', $request->id)->update([
                        'active' => $active_stage_plan]);
                }


                return redirect()->back()->with('success', 'Cập nhật trạng thái thành công!');
        }

        public function update(Request $request){
                
                $validator = Validator::make($request->all(), [
                        'expected_date' => 'required',
                ], [
                        'expected_date.required' => 'Vui lòng chọn ngày dự kiến KCS',
                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'update_Errors')->withInput();
                }

                // Update dữ liệu chính
                DB::table('plan_master')->where('id', $request->id)->update([
                        "expected_date" => $request->expected_date,
                        "note" => $request->note ?? "NA",
                        'prepared_by' => session('user')['fullName'],
                        'updated_at' => now(),
                ]);

                // Lấy dữ liệu gốc từ plan_master
                $plan = DB::table('plan_master')->where('id', $request->id)->first();
                
                // Tìm version cao nhất hiện tại trong history
                $lastVersion = DB::table('plan_master_history')
                        ->where('plan_master_id', $request->id)
                        ->max('version');

                $newVersion = $lastVersion ? $lastVersion + 1 : 1;

                
                DB::table('plan_master_history')->insert([
                        'plan_master_id' => $plan->id,
                        'plan_list_id' => $plan->plan_list_id,
                        'product_caterogy_id' => $plan->product_caterogy_id,
                        'version' => $newVersion,
                        'level' => 1,
                        'batch' => "NA",
                        'expected_date' => $request->expected_date,
                        'is_val' => 0,
                        'percent_parkaging' => 1,
                        'only_parkaging' =>  0,
                        'note' => $request->note,
                        'reason' => $request->reason ?? "NA",
                        'deparment_code' => session('user')['production_code'],

                        'prepared_by' => session('user')['fullName'],
                        'created_at' => now(),
                        'updated_at' => now(),
                        ]);

                return redirect()->back()->with('success', 'Đã cập nhật thành công!');

        }

        public function send(Request $request){
              
                $plans = DB::table('plan_master')
                ->where('plan_master.plan_list_id', $request->plan_list_id)
                ->where('plan_master.active',1)
                ->where('plan_master.cancel',0)
                ->select(
                        'plan_master.id',
                        'plan_master.plan_list_id',
                        'plan_master.product_caterogy_id',
                )
                ->get();

                
                $dataToInsert = [];

                foreach ($plans as $plan) {
                        $dataToInsert[] = [
                                'plan_list_id' => $plan->plan_list_id,
                                'plan_master_id' => $plan->id,
                                'product_caterogy_id'=> $plan->product_caterogy_id,
                                'stage_code'=> 8,
                                'order_by'=>  $plan->id,
                                'code'=>  $plan->id ."_8",
                                'deparment_code' => session('user')['production_code'],
                                'created_date' => now(),
                        ];
                        
                }

               
                DB::table('stage_plan')->insert($dataToInsert);
                DB::table('plan_list')->where ('id', $request->plan_list_id)->update([
                        'send' => 1,
                        'send_by' => session('user')['fullName'],
                        'send_date' => now(),
                ]);


                $datas = DB::table('plan_list')
                ->where ('active',1)
                ->where ('deparment_code',session('user')['production_code'])
                ->where ('type',0)
                ->orderBy('created_at','desc')->get();

                 session()->put(['title'=> 'Kế Hoạch Sản Xuất Tháng']);
                 return view('pages.plan.maintenance.plan_list',['datas' => $datas ]);
        }



}
