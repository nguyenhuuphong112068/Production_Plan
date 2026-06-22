<?php

namespace App\Http\Controllers\Pages\Quota;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PersonnelController extends Controller
{
    public function portal()
    {
        $departments = [
            ['code' => 'PXV1', 'name' => 'Phân Xưởng Viên 1', 'icon' => 'fas fa-pills'],
            ['code' => 'PXV2', 'name' => 'Phân Xưởng Viên 2', 'icon' => 'fas fa-capsules'],
            ['code' => 'PXVH', 'name' => 'Phân Xưởng Hormone', 'icon' => 'fas fa-tablets'],
            ['code' => 'PXTN', 'name' => 'Phân Xưởng Thuốc Nước', 'icon' => 'fas fa-wine-bottle'],
            ['code' => 'PXDN', 'name' => 'Phân Xưởng Dùng Ngoài', 'icon' => 'svg-tube'],
            ['code' => 'EN', 'name' => 'Kỹ Thuật Bảo Trì (EN)', 'icon' => 'fas fa-wrench'],
            ['code' => 'QA', 'name' => 'Đảm Bảo Chất Lượng (QA)', 'icon' => 'fas fa-clipboard-check'],
        ];

        session()->put(['title' => 'QUẢN LÝ NHÂN SỰ']);

        $today = now();
        $day = $today->day;
        if ($day <= 20) {
            $queryMonth = $today->month;
            $queryYear = $today->year;
        } else {
            $queryMonth = $today->month + 1;
            $queryYear = $today->year;
            if ($queryMonth > 12) {
                $queryMonth = 1;
                $queryYear++;
            }
        }
        $dayKey = 'day' . $day;

        $depMapping = [
            'EN' => 3,
            'QA' => 9,
            'PXTN' => 6,
            'PXV1' => 15,
            'PXVH' => 30,
            'PXDN' => 34,
            'PXV2' => 32
        ];

        $shiftCounts = [];

        foreach ($departments as $d) {
            $code = $d['code'];
            $depId = $depMapping[$code] ?? null;

            // Mặc định
            $shiftCounts[$code] = [
                'total' => 0,
                'c1' => 0,
                'c2' => 0,
                'c3' => 0,
                'c4' => 0,
                'hc' => 0,
                'p' => 0
            ];

            if (!$depId) {
                continue;
            }

            // Cache trong 5 phút bằng file để tránh lỗi max_allowed_packet với data lớn
            $cacheKey = "portal_shifts_{$code}_{$queryYear}_{$queryMonth}";

            $personnelData = Cache::store('file')->remember($cacheKey, 300, function () use ($queryMonth, $queryYear, $depId) {
                $url = "http://s-webdev:5070/api/shifts/by-department?month={$queryMonth}&year={$queryYear}&department={$depId}";
                try {
                    $ctx = stream_context_create(['http' => ['timeout' => 5]]); // 5s timeout
                    $data = @file_get_contents($url, false, $ctx);
                    if ($data) {
                        return json_decode($data, true) ?: [];
                    }
                } catch (\Exception $e) {
                    // Bỏ qua lỗi
                }
                return [];
            });

            if (is_array($personnelData) && count($personnelData) > 0) {
                // Lọc theo danh sách nhân sự active ở local DB
                $activeEmployeeCodes = DB::table('employees as e')
                    ->whereExists(function ($q) use ($code) {
                        $q->select(DB::raw(1))
                            ->from('employee_assignments as ea')
                            ->whereColumn('ea.employees_id', 'e.id')
                            ->where('ea.production_code', $code)
                            ->where('ea.active', 1);
                    })
                    ->pluck('e.code')
                    ->toArray();

                if (!empty($activeEmployeeCodes)) {
                    $activeCodesSet = array_flip($activeEmployeeCodes);
                    $filteredPersonnelData = [];
                    foreach ($personnelData as $person) {
                        $empCode = $person['employeeId'] ?? $person['code'] ?? null;
                        if ($empCode && isset($activeCodesSet[$empCode])) {
                            $filteredPersonnelData[] = $person;
                        }
                    }
                    $personnelData = $filteredPersonnelData;
                } else {
                    $personnelData = [];
                }

                $shiftCounts[$code]['total'] = count($personnelData);
                foreach ($personnelData as $person) {
                    $dayData = $person['days'][$dayKey] ?? null;
                    if (is_array($dayData)) {
                        $shift = strtoupper(trim($dayData['shift'] ?? ''));
                    } else {
                        $shift = strtoupper(trim($dayData ?? ''));
                    }
                    if ($shift === '') {
                        $shift = 'HC';
                    }
                    if ($shift === 'C1') {
                        $shiftCounts[$code]['c1']++;
                    } elseif ($shift === 'C2') {
                        $shiftCounts[$code]['c2']++;
                    } elseif ($shift === 'C3') {
                        $shiftCounts[$code]['c3']++;
                    } elseif ($shift === 'C4') {
                        $shiftCounts[$code]['c4']++;
                    } elseif ($shift === 'HC') {
                        $shiftCounts[$code]['hc']++;
                    } elseif ($shift === 'P') {
                        $shiftCounts[$code]['p']++;
                    }
                }
            }
        }

        return view('pages.quota.personnel.portal', compact('departments', 'shiftCounts'));
    }

    public function index(Request $request, $department = null)
    {
        // 1. Lấy mã bộ phận: Ưu tiên URL -> Session
        $departmentCode = $department ?? session('user')['department'];

        $filterGroupId = $request->group_id;
        $filterRoomId = $request->room_id;

        // 2. Truy vấn danh sách nhân viên theo bộ phận (kèm nhóm và phòng cho phép)
        $query = DB::table('employees as e')
            ->whereExists(function ($q) use ($departmentCode) {
                $q->select(DB::raw(1))
                    ->from('employee_assignments as ea')
                    ->whereColumn('ea.employees_id', 'e.id')
                    ->where('ea.production_code', $departmentCode)
                    ->where('ea.active', 1);
            })
            ->select('e.*')
            ->addSelect(DB::raw("(SELECT GROUP_CONCAT(CONCAT(eg.group_id, ':', eg.active, ':', COALESCE(u.name, eg.created_by), ':', DATE_FORMAT(eg.created_at, '%d/%m/%y')) SEPARATOR '|') FROM (SELECT group_id, employees_id, MAX(active) as active, MAX(created_by) as created_by, MAX(created_at) as created_at FROM employee_assignments WHERE group_id > 0 GROUP BY group_id, employees_id) eg LEFT JOIN employees u ON eg.created_by = u.code WHERE eg.employees_id = e.id) as allowed_groups"))
            ->addSelect(DB::raw("(SELECT GROUP_CONCAT(CONCAT(er.room_id, ':', er.level, ':', er.active, ':', COALESCE(u.name, er.created_by), ':', DATE_FORMAT(er.created_at, '%d/%m/%y'), ':', er.group_id, ':', COALESCE(er.priority_level, 1)) ORDER BY er.group_id, COALESCE(er.priority_level, 999) ASC, er.room_id SEPARATOR '|') FROM employee_assignments er LEFT JOIN employees u ON er.created_by = u.code WHERE er.employees_id = e.id AND er.room_id > 0) as allowed_rooms_with_levels"))
            ->addSelect(DB::raw("(SELECT production_code FROM employee_assignments WHERE employees_id = e.id AND is_main = 1 LIMIT 1) as main_production"))
            ->addSelect(DB::raw("(SELECT GROUP_CONCAT(CONCAT(ep2.production_code, ':', ep2.active, ':', COALESCE(u.name, ep2.created_by), ':', DATE_FORMAT(ep2.created_at, '%d/%m/%y')) SEPARATOR '|') FROM (SELECT production_code, employees_id, MAX(active) as active, MAX(created_by) as created_by, MAX(created_at) as created_at, MAX(is_main) as is_main FROM employee_assignments WHERE production_code != '' GROUP BY production_code, employees_id) ep2 LEFT JOIN employees u ON ep2.created_by = u.code WHERE ep2.employees_id = e.id AND ep2.is_main = 0) as temp_productions"));

        if ($filterGroupId) {
            $query->whereExists(function ($q) use ($filterGroupId) {
                $q->select(DB::raw(1))
                    ->from('employee_assignments')
                    ->whereColumn('employees_id', 'e.id')
                    ->where('group_id', $filterGroupId);
            });
        }

        if ($filterRoomId) {
            $query->whereExists(function ($q) use ($filterRoomId) {
                $q->select(DB::raw(1))
                    ->from('employee_assignments')
                    ->whereColumn('employees_id', 'e.id')
                    ->where('room_id', $filterRoomId);
            });
        }

        $datas = $query->orderBy('e.code', 'asc')->get();

        // 2.1 Tính toán thời gian làm việc tại từng phòng
        $startOfYear = now()->startOfYear()->format('Y-m-d H:i:s');
        $workHoursRaw = DB::table('assignment_personnel as ap')
            ->join('assignments as a', 'ap.assignment_id', '=', 'a.id')
            ->where('a.active', 1)
            ->select(
                'ap.personnel_id',
                'a.room_id',
                DB::raw("SUM(CASE WHEN a.start >= '{$startOfYear}' THEN TIMESTAMPDIFF(SECOND, a.start, a.end) / 3600 ELSE 0 END) as hours_year"),
                DB::raw('SUM(TIMESTAMPDIFF(SECOND, a.start, a.end) / 3600) as hours_total')
            )
            ->groupBy('ap.personnel_id', 'a.room_id')
            ->get();

        $workHours = [];
        foreach ($workHoursRaw as $wh) {
            $workHours[$wh->personnel_id][$wh->room_id] = [
                'year' => round($wh->hours_year, 1),
                'total' => round($wh->hours_total, 1)
            ];
        }

        session()->put(['title' => 'NHÂN VIÊN - BỘ PHẬN: ' . $departmentCode]);

        $isENorQA = in_array($departmentCode, ['EN', 'QA']);

        if ($isENorQA) {

            if ($departmentCode == 'QA') {
                $groups = DB::table('stage_groups')
                    ->where('code', 20)
                    ->select('id', 'code', 'name')
                    ->get();
            } else {
                $groups = DB::table('stage_groups')
                    ->where('type', 2)
                    ->where('code', "!=", 20)
                    ->select('id', 'code', 'name')
                    ->get();
            }
        } else {
            $groups = collect([
                1 => "Trung Tâm Cân",
                3 => "Pha Chế",
                4 => "Văn Phòng",
                5 => "Định Hình",
                6 => "Bao Phim",
                7 => "ĐGSC",
                8 => "ĐGTC",
                9 => "VSCN + Kho BTP"
            ])->map(function ($name, $id) {
                return (object) ['id' => $id, 'code' => $id, 'name' => $name];
            })->values();
        }
        //dd($groups);
        $rooms = DB::table('room')
            ->where('deparment_code', $departmentCode)
            ->where('only_maintenance', 0)
            ->get();

        $viewName = $isENorQA ? 'pages.quota.personnel.en_qa_list' : 'pages.quota.personnel.list';
        // dd($datas, $groups);
        return view($viewName, [
            'datas' => $datas,
            'departments' => session('user')['department'],
            'groups' => $groups,
            'rooms' => $rooms,
            'currentDepartment' => $departmentCode,
            'workHours' => $workHours,
            'filterGroupId' => $filterGroupId,
            'filterRoomId' => $filterRoomId
        ]);
    }

    public function sync(Request $request)
    {
        $departmentCode = $request->department ?? session('user')['production_code'];

        // Mapping department codes to IDs for the external API
        $depMapping = [
            'PXV1' => 15,
            'PXV2' => 32,
            'PXVH' => 30,
            'PXDN' => 30,
            'EN' => 3,
            'QA' => 9,
            'PXTN' => 6
        ];

        $depId = $depMapping[$departmentCode] ?? null;
        if (!$depId) {
            return redirect()->back()->with('error', "Bộ phận {$departmentCode} không hỗ trợ đồng bộ tự động.");
        }

        $month = now()->month;
        $year = now()->year;

        $url = "http://s-webdev:5070/api/shifts/by-department?month={$month}&year={$year}&department={$depId}";

        try {
            $data = file_get_contents($url);
            $employees = json_decode($data) ?: [];

            if ($departmentCode === 'PXV1') {
                $url17 = "http://s-webdev:5070/api/shifts/by-department?month={$month}&year={$year}&department=17";
                try {
                    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
                    $data17 = @file_get_contents($url17, false, $ctx);
                    if ($data17) {
                        $employees17 = json_decode($data17);
                        if (is_array($employees17)) {
                            foreach ($employees17 as $emp17) {
                                $emp17->is_warehouse = true;
                                if (isset($emp17->employeeName)) {
                                    $emp17->employeeName = trim($emp17->employeeName) . ' - WH';
                                }
                                $employees[] = $emp17;
                            }
                        }
                    }
                } catch (\Exception $ex17) {
                }
            }

            if (empty($employees)) {
                return redirect()->back()->with('info', 'Không tìm thấy dữ liệu nhân sự trên hệ thống nguồn.');
            }

            $count = 0;
            $warehouseAllowedCodes = ['21049', '21048', '21077', '21064', '21080', '21090', '21120', '21122', '21130', '21143', '21148', '21152'];

            foreach ($employees as $emp) {
                // 1. Kiểm tra/Tạo nhân viên
                $employee = DB::table('employees')->where('code', $emp->employeeId)->first();

                // Rule: "các nhân sự có employees.resign không tiến hành cập nhật lại"
                if ($employee && $employee->resign == 1) {
                    continue;
                }

                $isWarehouse = !empty($emp->is_warehouse);
                $isAllowedWarehouse = $isWarehouse && in_array((string)$emp->employeeId, $warehouseAllowedCodes);

                $resignVal = $isWarehouse ? ($isAllowedWarehouse ? 0 : 1) : 0;
                $activeVal = $isWarehouse ? ($isAllowedWarehouse ? 1 : 0) : 1;
                $groupIdVal = $isWarehouse ? ($isAllowedWarehouse ? 1 : 0) : 0;

                if (!$employee) {
                    $employeeId = DB::table('employees')->insertGetId([
                        'code' => $emp->employeeId,
                        'name' => $emp->employeeName,
                        'active' => $activeVal,
                        'resign' => $resignVal,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    $count++;
                } else {
                    $employeeId = $employee->id;
                    DB::table('employees')->where('id', $employeeId)->update([
                        'name' => $emp->employeeName,
                        'active' => $activeVal,
                        'resign' => $resignVal,
                        'updated_at' => now()
                    ]);
                }

                // 2. Cập nhật phân xưởng trực thuộc (is_main = 1)
                DB::table('employee_assignments')
                    ->where('employees_id', $employeeId)
                    ->where('is_main', 1)
                    ->delete();

                DB::table('employee_assignments')->insert([
                    'employees_id' => $employeeId,
                    'production_code' => $departmentCode,
                    'is_main' => 1,
                    'group_id' => $groupIdVal,
                    'active' => $activeVal,
                    'created_by' => 'API Sync',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            return redirect()->back()->with('success', "Đã đồng bộ thành công {$count} nhân sự mới.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Lỗi đồng bộ: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|unique:employees,code',
            'name' => 'required',
        ], [
            'code.required' => 'Vui lòng nhập Mã nhân viên',
            'code.unique' => 'Mã nhân viên đã tồn tại.',
            'name.required' => 'Vui lòng nhập Tên nhân viên',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        DB::table('employees')->insert([
            'code' => $request->code,
            'name' => $request->name,
            'deparment_code' => $request->deparment_code,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Đã thêm thành công!');
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|unique:employees,code,' . $request->id,
            'name' => 'required',
        ], [
            'code.required' => 'Vui lòng nhập Mã nhân viên',
            'code.unique' => 'Mã nhân viên đã tồn tại.',
            'name.required' => 'Vui lòng nhập Tên nhân viên',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        DB::table('employees')->where('id', $request->id)->update([
            'code' => $request->code,
            'name' => $request->name,
            'deparment_code' => $request->deparment_code,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Cập nhật thành công!');
    }

    public function deActive(string|int $id)
    {
        $current = DB::table('employees')->where('id', $id)->first();
        DB::table('employees')->where('id', $id)->update([
            'active' => !$current->active,
            'updated_at' => now(),
        ]);
        return redirect()->back()->with('success', 'Đã thay đổi trạng thái thành công!');
    }

    public function updatePermissions(Request $request)
    {
        \Illuminate\Support\Facades\Log::info($request->all());
        $employeeId = $request->employee_id;
        $type = $request->type; // 'group' or 'room'
        $ids = $request->ids ?? []; // Array of IDs
        $userName = session('user')['fullName'] ?? 'System';

        try {
            DB::beginTransaction();

            if ($type == 'group') {
                // Đảm bảo phải có ít nhất 1 tổ active được gửi lên
                $hasActiveGroup = false;
                foreach ($ids as $item) {
                    if (empty($item)) continue;
                    $parts = explode(':', $item);
                    if (($parts[1] ?? 1) == 1) {
                        $hasActiveGroup = true;
                        break;
                    }
                }

                if (!$hasActiveGroup) {
                    return response()->json(['success' => false, 'message' => 'Một nhân viên luôn luôn phải có ít nhất 1 tổ hoạt động.'], 400);
                }

                // Lấy danh sách trạng thái active hiện tại của các tổ trong DB
                $dbGroupActiveStates = DB::table('employee_assignments')
                    ->where('employees_id', $employeeId)
                    ->whereNotNull('group_id')
                    ->groupBy('group_id')
                    ->select('group_id', DB::raw('MAX(active) as active'))
                    ->pluck('active', 'group_id')
                    ->toArray();

                foreach ($ids as $item) {
                    if (empty($item)) continue;

                    $parts = explode(':', $item);
                    $id = $parts[0];
                    $active = intval($parts[1] ?? 1);

                    // Lấy mã phân xưởng liên quan đến tổ này
                    $departmentCode = $request->department ?? session('user')['department'] ?? session('user')['production_code'];
                    $isENorQA = in_array($departmentCode, ['EN', 'QA']);
                    if ($isENorQA) {
                        $groupCode = DB::table('stage_groups')
                            ->where('id', $id)
                            ->orWhere('code', $id)
                            ->value('code') ?? $id;
                    } else {
                        $groupCode = $id; // Với tổ sản xuất, ID chính là group_code từ list hardcode
                    }

                    $currentActive = intval($dbGroupActiveStates[$groupCode] ?? 0);

                    // Chỉ thực hiện thay đổi khi trạng thái mong muốn khác trạng thái hiện tại
                    if ($active !== $currentActive) {
                        $productionCode = DB::table('room')
                            ->where('group_code', $groupCode)
                            ->where('deparment_code', $departmentCode)
                            ->value('deparment_code') ?? $departmentCode;

                        // Nếu bật tổ này, kích hoạt tất cả các phòng thuộc tổ này
                        if ($active == 1) {
                            DB::table('employee_assignments')
                                ->where('employees_id', $employeeId)
                                ->where('group_id', $groupCode)
                                ->where('room_id', '>', 0)
                                ->update(['active' => 1, 'updated_at' => now()]);
                        }

                        // Nếu tắt tổ này, tắt tất cả các phòng thuộc tổ này
                        if ($active == 0) {
                            DB::table('employee_assignments')
                                ->where('employees_id', $employeeId)
                                ->where('group_id', $groupCode)
                                ->where('room_id', '>', 0)
                                ->update(['active' => 0, 'updated_at' => now()]);
                        }

                        $exists = DB::table('employee_assignments')
                            ->where('employees_id', $employeeId)
                            ->where('production_code', $productionCode)
                            ->where('group_id', $groupCode)
                            ->where('room_id', 0)
                            ->exists();

                        // Nếu đã có bản ghi Phòng trong tổ này, không cần bản ghi Tổ trống (room_id=0)
                        $hasRooms = DB::table('employee_assignments')
                            ->where('employees_id', $employeeId)
                            ->where('group_id', $groupCode)
                            ->where('room_id', '>', 0)
                            ->exists();

                        if ($hasRooms && $active == 1) {
                            // Xóa bản ghi Tổ trống nếu nó tồn tại
                            DB::table('employee_assignments')
                                ->where('employees_id', $employeeId)
                                ->where('group_id', $groupCode)
                                ->where('room_id', 0)
                                ->delete();

                            // Đồng thời xóa bản ghi Phân xưởng trống (group_id=0) nếu có
                            DB::table('employee_assignments')
                                ->where('employees_id', $employeeId)
                                ->where('production_code', $productionCode)
                                ->where('group_id', 0)
                                ->delete();
                            continue;
                        }

                        if ($exists) {
                            DB::table('employee_assignments')
                                ->where('employees_id', $employeeId)
                                ->where('production_code', $productionCode)
                                ->where('group_id', $groupCode)
                                ->where('room_id', 0)
                                ->update([
                                    'is_main' => 1,
                                    'active' => $active,
                                    'created_by' => $userName,
                                    'updated_at' => now()
                                ]);

                            // Xóa bản ghi Phân xưởng trống (group_id=0) nếu có
                            DB::table('employee_assignments')
                                ->where('employees_id', $employeeId)
                                ->where('production_code', $productionCode)
                                ->where('group_id', 0)
                                ->delete();
                        } else {
                            // Ưu tiên tìm xem có dòng placeholder (group_id = 0 và room_id = 0) không để cập nhật
                            $placeholderRow = DB::table('employee_assignments')
                                ->where('employees_id', $employeeId)
                                ->where('production_code', $productionCode)
                                ->where('group_id', 0)
                                ->where('room_id', 0)
                                ->first();

                            if ($placeholderRow) {
                                DB::table('employee_assignments')
                                    ->where('id', $placeholderRow->id)
                                    ->update([
                                        'group_id' => $groupCode,
                                        'active' => $active,
                                        'created_by' => $userName,
                                        'updated_at' => now()
                                    ]);
                            } else {
                                DB::table('employee_assignments')->insert([
                                    'employees_id' => $employeeId,
                                    'production_code' => $productionCode,
                                    'is_main' => 1,
                                    'group_id' => $groupCode,
                                    'room_id' => 0,
                                    'active' => $active,
                                    'created_by' => $userName,
                                    'created_at' => now(),
                                    'updated_at' => now()
                                ]);
                            }
                        }
                    }
                }
            } else {
                $mode = $request->input('mode', 'reconcile'); // 'add' = only insert/update, no delete

                // Get list of submitted room_id & group_id pairs
                $submittedPairs = [];
                foreach ($ids as $item) {
                    if (empty($item)) continue;
                    $parts = explode(':', $item);
                    $roomId = $parts[0];
                    $submittedGroupId = $parts[3] ?? null;

                    if ($submittedGroupId) {
                        $groupId = $submittedGroupId;
                    } else {
                        $room = DB::table('room')->where('id', $roomId)->first();
                        $groupCode = $room->group_code ?? '';
                        $groupId = DB::table('stage_groups')->where('code', $groupCode)->value('id') ?? 0;
                    }
                    $submittedPairs[] = $groupId . '_' . $roomId;
                }

                // Delete assignments that are NOT in the submitted list (only in reconcile mode)
                if ($mode !== 'add') {
                    $currentAssignments = DB::table('employee_assignments')
                        ->where('employees_id', $employeeId)
                        ->where('room_id', '>', 0)
                        ->get();

                    foreach ($currentAssignments as $ca) {
                        $pair = $ca->group_id . '_' . $ca->room_id;
                        if (!in_array($pair, $submittedPairs)) {
                            $groupRoomsCount = DB::table('employee_assignments')
                                ->where('employees_id', $employeeId)
                                ->where('group_id', $ca->group_id)
                                ->where('room_id', '>', 0)
                                ->count();

                            if ($groupRoomsCount <= 1) {
                                DB::table('employee_assignments')->where('id', $ca->id)->update([
                                    'room_id' => 0,
                                    'level' => 1,
                                    'updated_at' => now()
                                ]);
                            } else {
                                DB::table('employee_assignments')->where('id', $ca->id)->delete();
                            }
                        }
                    }
                }

                foreach ($ids as $item) {
                    if (empty($item)) continue;
                    $parts = explode(':', $item);
                    $roomId = $parts[0];
                    $level = $parts[1] ?? 1;
                    $active = $parts[2] ?? 1;
                    $submittedGroupId = $parts[3] ?? null;
                    $priorityLevel = $parts[4] ?? 1;

                    // Lấy thông tin PX và Tổ từ bảng room
                    $room = DB::table('room')->where('id', $roomId)->first();
                    $productionCode = $room->deparment_code ?? '';

                    if ($submittedGroupId) {
                        $groupId = $submittedGroupId;
                    } else {
                        $groupCode = $room->group_code ?? '';
                        $groupId = DB::table('stage_groups')->where('code', $groupCode)->value('id') ?? 0;
                    }

                    // Nếu gán phòng, tự động xóa các bản ghi cấp cha "trống" (Placeholder)
                    if ($active == 1) {
                        // Xóa Tổ trống
                        DB::table('employee_assignments')
                            ->where('employees_id', $employeeId)
                            ->where('group_id', $groupId)
                            ->where('room_id', 0)
                            ->delete();

                        // Xóa Phân xưởng trống
                        DB::table('employee_assignments')
                            ->where('employees_id', $employeeId)
                            ->where('production_code', $productionCode)
                            ->where('group_id', 0)
                            ->delete();
                    }

                    // Kiểm tra xem phân xưởng này có phải là Phân xưởng chính của nhân viên không
                    $isMain = DB::table('employee_assignments')
                        ->where('employees_id', $employeeId)
                        ->where('production_code', $productionCode)
                        ->where('is_main', 1)
                        ->where('group_id', 0)
                        ->where('room_id', 0)
                        ->exists() ? 1 : 0;

                    $exists = DB::table('employee_assignments')
                        ->where('employees_id', $employeeId)
                        ->where('production_code', $productionCode)
                        ->where('group_id', $groupId)
                        ->where('room_id', $roomId)
                        ->exists();

                    if ($exists) {
                        DB::table('employee_assignments')
                            ->where('employees_id', $employeeId)
                            ->where('production_code', $productionCode)
                            ->where('group_id', $groupId)
                            ->where('room_id', $roomId)
                            ->update([
                                'is_main' => 1,
                                'level' => $level,
                                'active' => $active,
                                'priority_level' => $priorityLevel,
                                'created_by' => $userName,
                                'updated_at' => now()
                            ]);
                    } else {
                        // Kiểm tra nếu có bản ghi placeholder room_id = 0 thì UPDATE thay vì INSERT
                        $placeholderRoom = DB::table('employee_assignments')
                            ->where('employees_id', $employeeId)
                            ->where('group_id', $groupId)
                            ->where('room_id', 0)
                            ->first();

                        if ($placeholderRoom) {
                            DB::table('employee_assignments')
                                ->where('id', $placeholderRoom->id)
                                ->update([
                                    'production_code' => $productionCode,
                                    'room_id' => $roomId,
                                    'level' => $level,
                                    'active' => $active,
                                    'priority_level' => $priorityLevel,
                                    'created_by' => $userName,
                                    'updated_at' => now()
                                ]);
                        } else {
                            DB::table('employee_assignments')->insert([
                                'employees_id' => $employeeId,
                                'production_code' => $productionCode,
                                'is_main' => 1,
                                'group_id' => $groupId,
                                'room_id' => $roomId,
                                'level' => $level,
                                'active' => $active,
                                'created_by' => $userName,
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            // Recalculate counts for the relevant department
            $this->recalculateRoomCounts($request->department ?? session('user')['department'] ?? session('user')['production_code']);

            return response()->json(['success' => true, 'message' => 'Cập nhật thành công!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Recalculates and persists personnel counts to Room and Assignments tables.
     */
    private function recalculateRoomCounts($departmentCode)
    {
        if (!$departmentCode) return;

        try {
            // 1. Update Room table counts by level
            $roomStats = DB::table('employee_assignments')
                ->where('production_code', $departmentCode)
                ->where('active', 1)
                ->where('room_id', '>', 0)
                ->select('room_id', 'level', DB::raw('count(*) as count'))
                ->groupBy('room_id', 'level')
                ->get();

            // Reset all counts for this department first
            DB::table('room')->where('deparment_code', $departmentCode)->update([
                'number_of_employes_on_sheet1' => 0,
                'number_of_employes_on_sheet2' => 0,
                'number_of_employes_on_sheet3' => 0,
                'number_of_employes_on_sheet4' => 0,
                'number_of_employes_on_sheet_regular' => 0,
            ]);

            foreach ($roomStats as $stat) {
                $column = match ((int)$stat->level) {
                    1 => 'number_of_employes_on_sheet1',
                    2 => 'number_of_employes_on_sheet2',
                    3 => 'number_of_employes_on_sheet3',
                    4 => 'number_of_employes_on_sheet4',
                    default => 'number_of_employes_on_sheet_regular',
                };

                DB::table('room')->where('id', $stat->room_id)->update([
                    $column => $stat->count
                ]);
            }

            // 2. Update Assignments table for today
            $today = now()->format('Y-m-d');
            $assignmentStats = DB::table('employee_assignments')
                ->where('production_code', $departmentCode)
                ->where('active', 1)
                ->where('room_id', '>', 0)
                ->select('room_id', DB::raw('count(*) as count'))
                ->groupBy('room_id')
                ->get();

            foreach ($assignmentStats as $stat) {
                DB::table('assignments')
                    ->where('room_id', $stat->room_id)
                    ->whereDate('start', $today)
                    ->update(['number_of_employes' => $stat->count]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error recalculating room counts: " . $e->getMessage());
        }
    }

    public function updateProductions(Request $request)
    {
        $employeeId = $request->employee_id;
        $productions = $request->productions ?? []; // Array of production codes
        $userName = session('user')['fullName'] ?? session('user')['userName'] ?? 'System';

        try {
            DB::beginTransaction();

            // 1. Đánh dấu tất cả các phân xưởng (cả trực thuộc và tạm thời) là inactive
            DB::table('employee_assignments')
                ->where('employees_id', $employeeId)
                ->whereNotNull('production_code')
                ->update(['active' => 0]);

            // 2. Nếu có phân xưởng tạm thời được chọn, kích hoạt phân xưởng đầu tiên trong danh sách (đảm bảo chỉ có 1)
            if (!empty($productions)) {
                $code = $productions[0];

                // Nếu đã có bất kỳ Tổ hoặc Phòng nào thuộc PX này, ta không cần bản ghi PX trống (group_id=0)
                $hasChildren = DB::table('employee_assignments')
                    ->where('employees_id', $employeeId)
                    ->where('production_code', $code)
                    ->where('group_id', '>', 0)
                    ->exists();

                if ($hasChildren) {
                    // Đảm bảo các con đều active (tùy nghiệp vụ, ở đây ta chỉ xóa bản ghi 0)
                    DB::table('employee_assignments')
                        ->where('employees_id', $employeeId)
                        ->where('production_code', $code)
                        ->where('group_id', 0)
                        ->delete();
                } else {
                    $exists = DB::table('employee_assignments')
                        ->where('employees_id', $employeeId)
                        ->where('production_code', $code)
                        ->where('group_id', 0)
                        ->where('room_id', 0)
                        ->exists();

                    if ($exists) {
                        DB::table('employee_assignments')
                            ->where('employees_id', $employeeId)
                            ->where('production_code', $code)
                            ->where('group_id', 0)
                            ->where('room_id', 0)
                            ->update([
                                'active' => 1,
                                'created_by' => $userName,
                                'updated_at' => now()
                            ]);
                    } else {
                        DB::table('employee_assignments')->insert([
                            'employees_id' => $employeeId,
                            'production_code' => $code,
                            'is_main' => 1,
                            'group_id' => 0,
                            'room_id' => 0,
                            'active' => 1,
                            'created_by' => $userName,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }
            } else {
                // 3. Nếu không có phân xưởng tạm thời nào được chọn, tự động kích hoạt lại phân xưởng trực thuộc
                DB::table('employee_assignments')
                    ->where('employees_id', $employeeId)
                    ->where('is_main', 1)
                    ->update(['active' => 1, 'updated_at' => now()]);
            }

            DB::commit();

            // Recalculate counts
            $this->recalculateRoomCounts(session('user')['department'] ?? session('user')['production_code']);

            return response()->json(['success' => true, 'message' => 'Cập nhật phân xưởng công tác thành công!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getStats(Request $request, $depId)
    {
        $range = $request->input('range', 'day');
        $groupId = $request->input('group_id');

        $activeEmployeeCodes = [];
        if ($groupId) {
            $activeEmployeeCodes = DB::table('employee_assignments as ea')
                ->join('employees as e', 'ea.employees_id', '=', 'e.id')
                ->where('ea.group_id', $groupId)
                ->where('ea.active', 1)
                ->pluck('e.code')
                ->toArray();
        } else {
            $depMapping = [
                3 => 'EN',
                9 => 'QA',
                6 => 'PXTN',
                15 => 'PXV1',
                30 => 'PXVH',
                34 => 'PXDN',
                32 => 'PXV2'
            ];
            $departmentCode = $depMapping[$depId] ?? null;
            if ($departmentCode) {
                $activeEmployeeCodes = DB::table('employees as e')
                    ->whereExists(function ($q) use ($departmentCode) {
                        $q->select(DB::raw(1))
                            ->from('employee_assignments as ea')
                            ->whereColumn('ea.employees_id', 'e.id')
                            ->where('ea.production_code', $departmentCode)
                            ->where('ea.active', 1);
                    })
                    ->pluck('e.code')
                    ->toArray();
            }
        }

        if ($range === 'day') {
            $dateStr = $request->input('date', now()->format('Y-m-d'));
            $date = \Carbon\Carbon::parse($dateStr);
            $year = $date->year;
            $month = $date->month;
            $day = $date->day;

            if ($day >= 21) {
                $month += 1;
                if ($month > 12) {
                    $month = 1;
                    $year += 1;
                }
            }

            $merged = $this->getFilteredMergedShifts($year, $month, $depId, $activeEmployeeCodes);

            $stats = [
                'hc' => 0,
                'c1' => 0,
                'c2' => 0,
                'c3' => 0,
                'c4' => 0,
                'p' => 0
            ];

            foreach ($merged as $person) {
                $shift = $person['days'][$day] ?? '';
                if ($shift === '') {
                    $shift = 'HC';
                }
                if ($shift === 'C1') $stats['c1']++;
                elseif ($shift === 'C2') $stats['c2']++;
                elseif ($shift === 'C3') $stats['c3']++;
                elseif ($shift === 'C4') $stats['c4']++;
                elseif ($shift === 'HC') $stats['hc']++;
                elseif ($shift === 'P') $stats['p']++;
            }

            return response()->json([
                'data' => [$stats]
            ]);
        } elseif ($range === 'week') {
            $weekNum = intval($request->input('week', now()->weekOfYear));
            $year = intval($request->input('year', now()->year));

            $startOfWeek = \Carbon\Carbon::now()->setISODate($year, $weekNum)->startOfWeek();
            $endOfWeek = $startOfWeek->copy()->endOfWeek();

            $data = [];
            for ($date = $startOfWeek->copy(); $date->lte($endOfWeek); $date->addDay()) {
                $dYear = $date->year;
                $dMonth = $date->month;
                $dDay = $date->day;

                if ($dDay >= 21) {
                    $dMonth += 1;
                    if ($dMonth > 12) {
                        $dMonth = 1;
                        $dYear += 1;
                    }
                }

                $merged = $this->getFilteredMergedShifts($dYear, $dMonth, $depId, $activeEmployeeCodes);

                $stats = [
                    'date' => $date->format('d/m/Y'),
                    'hc' => 0,
                    'c1' => 0,
                    'c2' => 0,
                    'c3' => 0,
                    'c4' => 0,
                    'p' => 0
                ];

                foreach ($merged as $person) {
                    $shift = $person['days'][$dDay] ?? '';
                    if ($shift === '') {
                        $shift = 'HC';
                    }
                    if ($shift === 'C1') $stats['c1']++;
                    elseif ($shift === 'C2') $stats['c2']++;
                    elseif ($shift === 'C3') $stats['c3']++;
                    elseif ($shift === 'C4') $stats['c4']++;
                    elseif ($shift === 'HC') $stats['hc']++;
                    elseif ($shift === 'P') $stats['p']++;
                }
                $data[] = $stats;
            }

            return response()->json([
                'data' => $data
            ]);
        } else { // month
            $month = intval($request->input('month', now()->month));
            $year = intval($request->input('year', now()->year));

            $daysInMonth = \Carbon\Carbon::create($year, $month, 1)->daysInMonth;

            $data = [];
            $merged = $this->getFilteredMergedShifts($year, $month, $depId, $activeEmployeeCodes);

            for ($dDay = 1; $dDay <= $daysInMonth; $dDay++) {
                $stats = [
                    'day_of_month' => $dDay,
                    'hc' => 0,
                    'c1' => 0,
                    'c2' => 0,
                    'c3' => 0,
                    'c4' => 0,
                    'p' => 0
                ];

                foreach ($merged as $person) {
                    $shift = $person['days'][$dDay] ?? '';
                    if ($shift === '') {
                        $shift = 'HC';
                    }
                    if ($shift === 'C1') $stats['c1']++;
                    elseif ($shift === 'C2') $stats['c2']++;
                    elseif ($shift === 'C3') $stats['c3']++;
                    elseif ($shift === 'C4') $stats['c4']++;
                    elseif ($shift === 'HC') $stats['hc']++;
                    elseif ($shift === 'P') $stats['p']++;
                }
                $data[] = $stats;
            }

            return response()->json([
                'data' => $data
            ]);
        }
    }

    private function getFilteredMergedShifts($year, $month, $depId, $activeEmployeeCodes)
    {
        $merged = $this->getMergedMonthlyShifts($year, $month, $depId);
        if (!empty($activeEmployeeCodes)) {
            $activeCodesSet = array_flip($activeEmployeeCodes);
            $mergedFiltered = [];
            foreach ($merged as $person) {
                if (isset($activeCodesSet[$person['code']])) {
                    $mergedFiltered[] = $person;
                }
            }
            $merged = $mergedFiltered;
        }
        return $merged;
    }

    private function getMergedMonthlyShifts($year, $month, $depId)
    {
        $cacheKey = "merged_shifts_{$depId}_{$year}_{$month}";
        return Cache::store('file')->remember($cacheKey, 300, function () use ($year, $month, $depId) {
            $url1 = "http://s-webdev:5070/api/shifts/by-department?month={$month}&year={$year}&department={$depId}";
            $data1 = [];
            try {
                $ctx = stream_context_create(['http' => ['timeout' => 5]]);
                $res = @file_get_contents($url1, false, $ctx);
                if ($res) {
                    $data1 = json_decode($res, true) ?: [];
                }
            } catch (\Exception $e) {
            }

            if ($depId == 15) {
                try {
                    $url1_17 = "http://s-webdev:5070/api/shifts/by-department?month={$month}&year={$year}&department=17";
                    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
                    $res17 = @file_get_contents($url1_17, false, $ctx);
                    if ($res17) {
                        $data1_17 = json_decode($res17, true) ?: [];
                        if (is_array($data1_17)) {
                            foreach ($data1_17 as &$p17) {
                                if (isset($p17['employeeName'])) {
                                    $p17['employeeName'] = trim($p17['employeeName']) . ' - WH';
                                }
                            }
                            $data1 = array_merge($data1, $data1_17);
                        }
                    }
                } catch (\Exception $e) {
                }
            }

            $prevMonth = $month - 1;
            $prevYear = $year;
            if ($prevMonth < 1) {
                $prevMonth = 12;
                $prevYear--;
            }
            $url2 = "http://s-webdev:5070/api/shifts/by-department?month={$prevMonth}&year={$prevYear}&department={$depId}";
            $data2 = [];
            try {
                $ctx = stream_context_create(['http' => ['timeout' => 5]]);
                $res = @file_get_contents($url2, false, $ctx);
                if ($res) {
                    $data2 = json_decode($res, true) ?: [];
                }
            } catch (\Exception $e) {
            }

            if ($depId == 15) {
                try {
                    $url2_17 = "http://s-webdev:5070/api/shifts/by-department?month={$prevMonth}&year={$prevYear}&department=17";
                    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
                    $res2_17 = @file_get_contents($url2_17, false, $ctx);
                    if ($res2_17) {
                        $data2_17 = json_decode($res2_17, true) ?: [];
                        if (is_array($data2_17)) {
                            foreach ($data2_17 as &$p17) {
                                if (isset($p17['employeeName'])) {
                                    $p17['employeeName'] = trim($p17['employeeName']) . ' - WH';
                                }
                            }
                            $data2 = array_merge($data2, $data2_17);
                        }
                    }
                } catch (\Exception $e) {
                }
            }

            $empShifts = [];
            foreach ($data1 as $person) {
                $code = $person['employeeId'] ?? $person['code'] ?? null;
                if ($code) {
                    $empShifts[$code] = [
                        'name' => $person['employeeName'] ?? '',
                        'days' => $person['days'] ?? []
                    ];
                }
            }

            $empShiftsPrev = [];
            foreach ($data2 as $person) {
                $code = $person['employeeId'] ?? $person['code'] ?? null;
                if ($code) {
                    $empShiftsPrev[$code] = [
                        'name' => $person['employeeName'] ?? '',
                        'days' => $person['days'] ?? []
                    ];
                }
            }

            $allCodes = array_unique(array_merge(array_keys($empShifts), array_keys($empShiftsPrev)));

            $merged = [];
            foreach ($allCodes as $code) {
                $days = [];
                $name = $empShifts[$code]['name'] ?? ($empShiftsPrev[$code]['name'] ?? '');

                for ($d = 1; $d <= 20; $d++) {
                    $dayKey = 'day' . $d;
                    $dayData = $empShifts[$code]['days'][$dayKey] ?? null;
                    // Trích xuất chỉ mã ca để tiết kiệm bộ nhớ cache
                    if (is_array($dayData)) {
                        $days[$d] = strtoupper(trim($dayData['shift'] ?? ''));
                    } else {
                        $days[$d] = $dayData ? strtoupper(trim($dayData)) : null;
                    }
                }
                for ($d = 21; $d <= 31; $d++) {
                    $dayKey = 'day' . $d;
                    $dayData = $empShiftsPrev[$code]['days'][$dayKey] ?? null;
                    if (is_array($dayData)) {
                        $days[$d] = strtoupper(trim($dayData['shift'] ?? ''));
                    } else {
                        $days[$d] = $dayData ? strtoupper(trim($dayData)) : null;
                    }
                }

                $merged[] = [
                    'code' => $code,
                    'name' => $name,
                    'days' => $days
                ];
            }

            return $merged;
        });
    }
}
