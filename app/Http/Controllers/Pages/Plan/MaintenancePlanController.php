<?php

namespace App\Http\Controllers\Pages\Plan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MaintenancePlanController extends Controller
{
        public function index(Request $request)
        {
                $type = $request->type; // 1: HC, 2: BT, 3: TI
                $type_names = [
                        1 => 'HIỆU CHUẨN',
                        2 => 'BẢO TRÌ',
                        3 => 'TIỆN ÍCH'
                ];

                $query = DB::table('plan_list')
                        ->where('active', 1)
                        ->where('type', 0); // 0 là loại KHBT theo cấu trúc cũ

                if ($type) {
                        $typeName = $type_names[$type];
                        $query->where('name', 'like', "KH {$typeName}%");
                }

                $datas = $query->orderBy('created_at', 'desc')->get();

                $title = $type ? "KẾ HOẠCH {$type_names[$type]} THÁNG" : 'KẾ HOẠCH BẢO TRÌ THÁNG';
                session()->put(['title' => $title]);

                return view('pages.plan.maintenance.plan_list', [
                        'datas' => $datas,
                        'type' => $type
                ]);
        }

        public function create_plan_list(Request $request)
        {
                $startDate = $request->from_date ?? date('Y-m-01');
                $endDate = $request->to_date ?? date('Y-m-t');
                $type = $request->type;
                $departmentCode = session('user')['production_code'];

                try {
                        $result = $this->generateMaintenancePlan($startDate, $endDate, $departmentCode, $type);
                        if ($result['success']) {
                                return redirect()->back()->with('success', $result['message']);
                        } else {
                                $typeMsg = $result['total_devices'] === 0 ? 'warning' : 'error';
                                return redirect()->back()->with($typeMsg, $result['message']);
                        }
                } catch (\Exception $e) {
                        return redirect()->back()->with('error', 'Lỗi: ' . $e->getMessage());
                }
        }

        public function autoCreatePlan(Request $request)
        {
                $startDate = $request->from_date;
                $endDate = $request->to_date;
                $type = $request->type;
                $departments = $request->departments; // mảng các PX

                if (empty($departments)) {
                        return redirect()->back()->with('error', 'Vui lòng chọn ít nhất một phân xưởng.');
                }

                $results = [];
                $successCount = 0;
                $totalNewDevices = 0;

                // Fetch schedules based on type
                $schedules = $this->fetchAllSchedules($startDate, $endDate, $type);

                foreach ($departments as $dept) {
                        try {
                                $res = $this->generateMaintenancePlan($startDate, $endDate, $dept, $type, $schedules);
                                if ($res['success']) {
                                        $successCount++;
                                        $totalNewDevices += $res['count'];
                                }
                                $results[$dept] = $res['message'];
                        } catch (\Exception $e) {
                                $results[$dept] = 'Lỗi: ' . $e->getMessage();
                        }
                }

                if ($successCount > 0) {
                        return redirect()->back()->with('success', "Đã tạo thành công kế hoạch cho {$successCount} phân xưởng. Tổng số thiết bị mới: {$totalNewDevices}.");
                } else {
                        return redirect()->back()->with('warning', 'Không có kế hoạch mới nào được tạo. Có thể các kế hoạch đã tồn tại.');
                }
        }

        private function fetchAllSchedules($startDate, $endDate, $type = null)
        {
                $schedules = collect();
                $connections = ['cal1', 'cal2'];
                $suffixes = $type ? [$type] : [1, 2, 3];

                foreach ($connections as $conn) {
                        foreach ($suffixes as $suffix) {
                                try {
                                        $result = DB::connection($conn)
                                                ->table("Schedule_Master_{$suffix}")
                                                // ->table("Schedule_Master_3")
                                                ->whereBetween('Sch_DueDate', [$startDate, $endDate])
                                                ->where('Sch_Result_Status', 'Pending')
                                                ->select('Inst_ID', 'Sch_DueDate', 'Sch_Remark')
                                                ->get();

                                        $schedules = $schedules->merge($result);

                                        // dd($schedules);
                                } catch (\Exception $e) {
                                        Log::warning("Could not fetch schedules from {$conn}.Schedule_Master_{$suffix}: " . $e->getMessage());
                                }
                        }
                }
                return $schedules;
        }

        private function generateMaintenancePlan($startDate, $endDate, $departmentCode, $type, $schedules = null)
        {
                $fromDisplay = \Carbon\Carbon::parse($startDate)->format('d/m/Y');
                $toDisplay = \Carbon\Carbon::parse($endDate)->format('d/m/Y');
                $month = \Carbon\Carbon::parse($startDate)->format('m');
                $year = \Carbon\Carbon::parse($startDate)->format('Y');

                $type_names = [
                        1 => 'Hiệu Chuẩn',
                        2 => 'Bảo Trì',
                        3 => 'Tiện Ích'
                ];
                $type_prefix = [
                        1 => 'HC',
                        2 => 'BT',
                        3 => 'TI'
                ];

                $typeName = $type_names[$type] ?? 'BT-HC';
                $name = "KH {$typeName} T{$month}/{$year} ({$fromDisplay}-{$toDisplay})";

                if (!$schedules) {
                        $schedules = $this->fetchAllSchedules($startDate, $endDate, $type);
                }

                DB::beginTransaction();
                try {
                        // 1. Tạo plan_list
                        $planListId = DB::table('plan_list')->insertGetId([
                                'name' => $name,
                                'month' => $month,
                                'type' => 0,
                                'send' => false,
                                'deparment_code' => $departmentCode,
                                'prepared_by' => session('user')['fullName'],
                                'created_at' => now(),
                        ]);

                        // 3. Map Inst_ID → quota_maintenance
                        $instIds = $schedules->pluck('Inst_ID')->unique()->toArray();
                        $query = DB::table('quota_maintenance')
                                ->whereIn('inst_id', $instIds)
                                ->where('active', 1)
                                ->where('deparment_code', $departmentCode);

                        // Lọc theo prefix block HC, BT, TI
                        if ($type && isset($type_prefix[$type])) {
                                $query->where('block', 'like', $type_prefix[$type] . '-%');
                        }

                        $quotas = $query->get()->keyBy('inst_id');

                        // 4. Insert plan_master + plan_master_history
                        $now = now();
                        $preparedBy = session('user')['fullName'];
                        $count = 0;

                        // Lấy danh sách đã tồn tại để tránh tạo trùng (trong cùng phân xưởng)
                        $existing = DB::table('plan_master')
                                ->where('active', 1)
                                ->where('deparment_code', $departmentCode)
                                ->select('product_caterogy_id', 'expected_date')
                                ->get()
                                ->map(function ($item) {
                                        $date = \Carbon\Carbon::parse($item->expected_date)->format('Y-m-d');
                                        return $item->product_caterogy_id . '_' . $date;
                                })
                                ->toArray();
                        $existingSet = array_flip($existing);

                        foreach ($schedules as $sch) {
                                $quota = $quotas[$sch->Inst_ID] ?? null;
                                if (!$quota) continue;

                                // Kiểm tra trùng: cùng thiết bị + cùng ngày đã tạo rồi thì bỏ qua
                                $schDate = \Carbon\Carbon::parse($sch->Sch_DueDate)->format('Y-m-d');
                                $key = $quota->id . '_' . $schDate;
                                if (isset($existingSet[$key])) continue;

                                $pmId = DB::table('plan_master')->insertGetId([
                                        'product_caterogy_id' => $quota->id,
                                        'plan_list_id' => $planListId,
                                        'batch' => 'NA',
                                        'expected_date' => $sch->Sch_DueDate,
                                        'level' => 1,
                                        'is_val' => 0,
                                        'percent_parkaging' => 1,
                                        'only_parkaging' => 0,
                                        'number_parkaging' => 1,
                                        'note' => $sch->Sch_Remark ?? 'NA',
                                        'deparment_code' => $departmentCode,
                                        'prepared_by' => $preparedBy,
                                        'created_at' => $now,
                                ]);

                                DB::table('plan_master_history')->insert([
                                        'plan_master_id' => $pmId,
                                        'plan_list_id' => $planListId,
                                        'product_caterogy_id' => $quota->id,
                                        'batch' => 'NA',
                                        'expected_date' => $sch->Sch_DueDate,
                                        'level' => 1,
                                        'is_val' => 0,
                                        'percent_parkaging' => 1,
                                        'only_parkaging' => 0,
                                        'number_parkaging' => 1,
                                        'note' => $sch->Sch_Remark ?? 'NA',
                                        'deparment_code' => $departmentCode,
                                        'prepared_by' => $preparedBy,
                                        'created_at' => $now,
                                        'updated_at' => $now,
                                        'version' => 1,
                                        'reason' => 'Tạo tự động từ lịch bảo trì',
                                ]);

                                $count++;
                        }

                        if ($count === 0) {
                                DB::rollBack();
                                return [
                                        'success' => false,
                                        'count' => 0,
                                        'total_devices' => 0,
                                        'message' => "Không có kế hoạch mới cho PX {$departmentCode}."
                                ];
                        }

                        DB::commit();
                        return [
                                'success' => true,
                                'count' => $count,
                                'message' => "Tạo tự động cho PX {$departmentCode} thành công! ({$count} thiết bị)"
                        ];
                } catch (\Exception $e) {
                        DB::rollBack();
                        Log::error("Lỗi tạo kế hoạch bảo trì tự động cho {$departmentCode}: " . $e->getMessage());
                        throw $e;
                }
        }

        public function open(Request  $request)
        {

                $datas = DB::table('plan_master')
                        ->select(
                                'plan_master.*',
                                'quota_maintenance.inst_id as code',
                                'quota_maintenance.block',
                                'room.name as room_name',
                                'room.code as room_code'
                        )
                        ->where('plan_list_id', $request->plan_list_id)
                        ->where('plan_master.active', 1)
                        ->leftJoin('quota_maintenance', 'plan_master.product_caterogy_id', '=', 'quota_maintenance.id')
                        ->leftJoin('room', 'quota_maintenance.room_id', '=', 'room.id')
                        ->orderBy('level', 'asc')
                        ->orderBy('expected_date', 'asc')
                        ->get()
                        ->groupBy('id')
                        ->map(function ($items) {
                                $first = $items->first();

                                $first->rooms = $items->map(function ($item) {
                                        return $item->room_code . ' - ' . $item->room_name;
                                })
                                        ->filter()
                                        ->unique()
                                        ->implode(', ');

                                return $first;
                        })
                        ->values();

                // Lấy tên thiết bị từ cal1/cal2 (6 bảng)
                $instIds = $datas->pluck('code')->filter()->unique()->toArray();
                $instruments = collect();
                if (!empty($instIds)) {
                        $connections = ['cal1', 'cal2'];
                        $suffixes = [1, 2, 3];
                        foreach ($connections as $conn) {
                                foreach ($suffixes as $suffix) {
                                        $result = DB::connection($conn)
                                                ->table("Inst_Master_{$suffix}")
                                                ->whereIn('Inst_id', $instIds)
                                                ->select('Inst_id', 'Inst_Name', 'Parent_Equip_id')
                                                ->get()
                                                ->keyBy('Inst_id');
                                        $instruments = $instruments->merge($result);
                                }
                        }
                }

                $datas = $datas->map(function ($item) use ($instruments) {
                        $inst = $instruments[$item->code] ?? null;
                        $item->name = $inst->Inst_Name ?? $item->code;
                        $item->parent_code = $inst->Parent_Equip_id ?? '';
                        return $item;
                });

                // Fetch Sch_Type from Schedule_Master
                $schTypes = $this->fetchSchTypes($datas);
                $datas = $datas->map(function ($item) use ($schTypes) {
                        $date = \Carbon\Carbon::parse($item->expected_date)->format('Y-m-d');
                        $key = trim($item->code) . '_' . $date;
                        $item->sch_type = $schTypes[$key] ?? '';
                        return $item;
                });

                $planMasterIds = $datas->pluck('id')->toArray();

                $historyCounts = DB::table('plan_master_history')
                        ->select('plan_master_id', DB::raw('COUNT(*) as total'))
                        ->whereIn('plan_master_id', $planMasterIds)
                        ->groupBy('plan_master_id')
                        ->pluck('total', 'plan_master_id')
                        ->toArray();
                $datas = $datas->map(function ($item) use ($historyCounts) {
                        $item->history_count = $historyCounts[$item->id] ?? 0;
                        return $item;
                });



                $production  =  DB::table('plan_list')->where('id', $request->plan_list_id)->value('deparment_code');

                session()->put(['title' => " $request->name - $production"]);

                return view('pages.plan.maintenance.list', [
                        'datas' => $datas,
                        'plan_list_id' => $request->plan_list_id,
                        'month' => $request->month,
                        'production' => $request->production,
                        'send' => $request->send ?? 1,
                ]);
        }

        public function store(Request $request)
        {



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
                                                "number_parkaging"    => 1,
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
                                                "number_parkaging"    => 1,
                                                "note"                => $device['note'] ?? "NA",
                                                "deparment_code"      => $departmentCode,
                                                "prepared_by"         => $preparedBy,
                                                "created_at"          => $now,
                                                "updated_at"          => $now,
                                                "version"             => 1,
                                                "reason"             => "Tạo Mới",
                                        ];
                                }
                        }
                        //dd ($planMasterHistoryData);    
                        // Insert nhiều dòng vào plan_master_history
                        DB::table('plan_master_history')->insert($planMasterHistoryData);

                        DB::commit();
                        return redirect()->back()->with('success', 'Đã thêm thành công!');
                } catch (\Exception $e) {
                        DB::rollBack();
                        Log::error('Lỗi khi thêm dữ liệu plan_master: ' . $e->getMessage(), [
                                'trace' => $e->getTraceAsString(),
                                'request_data' => $request->all()
                        ]);
                        return redirect()->back()->with('error', 'Lỗi khi thêm dữ liệu: ' . $e->getMessage());
                }
        }


        public function history(Request $request)
        {
                $histories = DB::table('plan_master_history')
                        ->select(
                                'plan_master_history.*',
                                'quota_maintenance.inst_id as code',
                                'quota_maintenance.block',
                                DB::raw('CONCAT(room.code, " - ", room.name) as room')
                        )
                        ->where('plan_master_history.plan_master_id', $request->id)
                        ->leftJoin('quota_maintenance', 'plan_master_history.product_caterogy_id', '=', 'quota_maintenance.id')
                        ->leftJoin('room', 'quota_maintenance.room_id', '=', 'room.id')
                        ->orderBy('version', 'desc')
                        ->orderBy('expected_date', 'asc')
                        ->get();

                // Lấy tên thiết bị từ cal1/cal2 (6 bảng)
                $instIds = $histories->pluck('code')->filter()->unique()->toArray();
                $instruments = collect();
                if (!empty($instIds)) {
                        $connections = ['cal1', 'cal2'];
                        $suffixes = [1, 2, 3];
                        foreach ($connections as $conn) {
                                foreach ($suffixes as $suffix) {
                                        $result = DB::connection($conn)
                                                ->table("Inst_Master_{$suffix}")
                                                ->whereIn('Inst_id', $instIds)
                                                ->select('Inst_id', 'Inst_Name')
                                                ->get()
                                                ->keyBy('Inst_id');
                                        $instruments = $instruments->merge($result);
                                }
                        }
                }

                $histories = $histories->map(function ($item) use ($instruments) {
                        $inst = $instruments[$item->code] ?? null;
                        $item->name = $inst->Inst_Name ?? $item->code;
                        return $item;
                });

                // Fetch Sch_Type for history
                $historyItems = $histories->map(function ($h) {
                        return (object)['code' => $h->code, 'expected_date' => $h->expected_date];
                });
                $schTypes = $this->fetchSchTypes($historyItems);
                $histories = $histories->map(function ($item) use ($schTypes) {
                        $date = \Carbon\Carbon::parse($item->expected_date)->format('Y-m-d');
                        $key = trim($item->code) . '_' . $date;
                        $item->sch_type = $schTypes[$key] ?? '';
                        return $item;
                });

                return response()->json($histories);
        }

        public function deActive(Request $request)
        {

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

                if ($request->type !== 'delete') {
                        DB::table('stage_plan')->where('plan_master_id', $request->id)->update([
                                'active' => $active_stage_plan
                        ]);
                }


                if ($request->ajax()) {
                        return response()->json(['success' => true, 'message' => 'Cập nhật trạng thái thành công!']);
                }

                return redirect()->back()->with('success', 'Cập nhật trạng thái thành công!');
        }

        public function update(Request $request)
        {
                //dd ($request->all());
                $validator = Validator::make($request->all(), [
                        'expected_date' => 'required',
                ], [
                        'expected_date.required' => 'Vui lòng chọn ngày dự kiến KCS',
                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'update_Errors')->withInput();
                }
                $ids = DB::table('quota_maintenance')->where('inst_id', $request->code)->pluck('id')->toArray();

                // Update dữ liệu chính
                DB::table('plan_master')->whereIn('product_caterogy_id', $ids)->update([
                        "expected_date" => $request->expected_date,
                        "note" => $request->note ?? "NA",
                        'prepared_by' => session('user')['fullName'],
                        'updated_at' => now(),
                ]);

                // Lấy dữ liệu gốc từ plan_master
                $plans = DB::table('plan_master')
                        ->whereIn('product_caterogy_id', $ids)
                        ->get();


                foreach ($plans as $plan) {
                        $lastVersion = DB::table('plan_master_history')
                                ->where('plan_master_id', $plan->id)
                                ->max('version');

                        $newVersion = $lastVersion ? $lastVersion + 1 : 1;

                        DB::table('plan_master_history')->insert([
                                'plan_master_id'     => $plan->id,
                                'plan_list_id'       => $plan->plan_list_id,
                                'product_caterogy_id' => $plan->product_caterogy_id,
                                'version'            => $newVersion,
                                'level'              => 1,
                                'batch'              => "NA",
                                'expected_date'      => $request->expected_date,
                                'is_val'             => 0,
                                'percent_parkaging'  => 1,
                                'only_parkaging'     => 0,
                                'note'               => $request->note,
                                'reason'             => $request->reason ?? "NA",
                                'deparment_code'     => session('user')['production_code'],
                                'prepared_by'        => session('user')['fullName'],
                                'created_at'         => now(),
                                'updated_at'         => now(),
                        ]);
                }

                return redirect()->back()->with('success', 'Đã cập nhật thành công!');
        }

        public function send(Request $request)
        {
                $plans = DB::table('plan_master')
                        ->where('plan_master.plan_list_id', $request->plan_list_id)
                        ->where('plan_master.active', 1)
                        ->where('plan_master.cancel', 0)
                        ->leftJoin('quota_maintenance', 'plan_master.product_caterogy_id', '=', 'quota_maintenance.id')
                        ->select(
                                'plan_master.id',
                                'plan_master.plan_list_id',
                                'plan_master.product_caterogy_id',
                                'plan_master.expected_date',
                                'quota_maintenance.inst_id'
                        )
                        ->get();

                $dataToInsert = [];

                foreach ($plans as $plan) {
                        $mmyy = $plan->expected_date ? \Carbon\Carbon::parse($plan->expected_date)->format('my') : '0000';
                        $campaignCode = trim($plan->inst_id) . '_' . $mmyy;

                        $dataToInsert[] = [
                                'plan_list_id' => $plan->plan_list_id,
                                'plan_master_id' => $plan->id,
                                'product_caterogy_id' => $plan->product_caterogy_id,
                                'stage_code' => 8,
                                'campaign_code' => $campaignCode,
                                'order_by' =>  $plan->id,
                                'code' =>  $plan->id . "_8",
                                'deparment_code' => session('user')['production_code'],
                                'created_date' => now(),
                        ];
                }

                DB::table('stage_plan')->insert($dataToInsert);

                DB::table('plan_list')->where('id', $request->plan_list_id)->update([
                        'send' => 1,
                        'send_by' => session('user')['fullName'],
                        'send_date' => now(),
                ]);


                $datas = DB::table('plan_list')
                        ->where('active', 1)
                        ->where('type', 0)
                        ->orderBy('created_at', 'desc')->get();

                session()->put(['title' => 'Kế Hoạch Bảo Trì Tháng']);
                return view('pages.plan.maintenance.plan_list', ['datas' => $datas]);
        }

        private function fetchSchTypes($items)
        {
                $lookup = [];
                $instIds = $items->pluck('code')->filter()->unique()->toArray();
                if (empty($instIds)) return [];

                $connections = ['cal1', 'cal2'];
                $suffixes = [1, 2, 3];

                foreach ($connections as $conn) {
                        foreach ($suffixes as $suffix) {
                                try {
                                        $results = DB::connection($conn)
                                                ->table("Schedule_Master_{$suffix}")
                                                ->whereIn('Inst_ID', $instIds)
                                                ->select('Inst_ID', 'Sch_DueDate', 'Sch_Type')
                                                ->get();

                                        foreach ($results as $res) {
                                                $date = \Carbon\Carbon::parse($res->Sch_DueDate)->format('Y-m-d');
                                                $key = trim($res->Inst_ID) . '_' . $date;
                                                $lookup[$key] = $res->Sch_Type;
                                        }
                                } catch (\Exception $e) {
                                        // Skip missing tables or connection issues
                                }
                        }
                }
                return $lookup;
        }
}
