<?php

namespace App\Http\Controllers\Pages\Quota;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PersonnelController extends Controller
{
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
            ->addSelect(DB::raw("(SELECT GROUP_CONCAT(CONCAT(er.room_id, ':', er.level, ':', er.active, ':', COALESCE(u.name, er.created_by), ':', DATE_FORMAT(er.created_at, '%d/%m/%y')) ORDER BY er.group_id, er.room_id SEPARATOR '|') FROM employee_assignments er LEFT JOIN employees u ON er.created_by = u.code WHERE er.employees_id = e.id AND er.room_id > 0) as allowed_rooms_with_levels"))
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

        // 3. Lấy danh sách bộ phận, tổ và phòng để hỗ trợ nhập liệu
        $departments = DB::table('deparments')->where('active', true)->get();
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
            return (object) ['id' => $id, 'name' => $name];
        })->values();
        $rooms = DB::table('room')->where('deparment_code', $departmentCode)->get();

        return view('pages.quota.personnel.list', [
            'datas' => $datas,
            'departments' => $departments,
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
            'PXV2' => 31,
            'PXVH' => 30,
            'PXDN' => 30,
            'EN' => 3,
            'PXN' => 6,
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
            $employees = json_decode($data);

            if (empty($employees)) {
                return redirect()->back()->with('info', 'Không tìm thấy dữ liệu nhân sự trên hệ thống nguồn.');
            }

            $count = 0;
            foreach ($employees as $emp) {
                // 1. Kiểm tra/Tạo nhân viên
                $employee = DB::table('employees')->where('code', $emp->employeeId)->first();
                if (!$employee) {
                    $employeeId = DB::table('employees')->insertGetId([
                        'code' => $emp->employeeId,
                        'name' => $emp->employeeName,
                        'active' => 1,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    $count++;
                } else {
                    $employeeId = $employee->id;
                    DB::table('employees')->where('id', $employeeId)->update([
                        'name' => $emp->employeeName,
                        'updated_at' => now()
                    ]);
                }

                // 2. Cập nhật phân xưởng trực thuộc (is_main = 1)
                // Theo quy tắc: Xóa cái cũ và gán cái mới từ API
                DB::table('employee_assignments')
                    ->where('employees_id', $employeeId)
                    ->where('is_main', 1)
                    ->delete();

                DB::table('employee_assignments')->insert([
                    'employees_id' => $employeeId,
                    'production_code' => $departmentCode,
                    'is_main' => 1,
                    'active' => 1,
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

                // Đánh dấu tất cả là inactive trước (Đảm bảo chỉ có 1 tổ active)
                DB::table('employee_assignments')
                    ->where('employees_id', $employeeId)
                    ->whereNotNull('group_id')
                    ->update(['active' => 0]);

                // Khi Tổ bị deactive (hoặc thay đổi), các phòng liên quan cũng phải bị deactive
                DB::table('employee_assignments')
                    ->where('employees_id', $employeeId)
                    ->whereNotNull('room_id')
                    ->update(['active' => 0]);

                foreach ($ids as $item) {
                    if (empty($item)) continue;

                    $parts = explode(':', $item);
                    $id = $parts[0];
                    $active = $parts[1] ?? 1;

                    // Lấy mã phân xưởng liên quan đến tổ này
                    $groupCode = DB::table('stage_groups')->where('id', $id)->value('code');
                    $productionCode = DB::table('room')->where('group_code', $groupCode)->value('deparment_code') ?? '';

                    // Kiểm tra xem phân xưởng này có phải là Phân xưởng chính của nhân viên không
                    $isMain = DB::table('employee_assignments')
                        ->where('employees_id', $employeeId)
                        ->where('production_code', $productionCode)
                        ->where('is_main', 1)
                        ->where('group_id', 0)
                        ->where('room_id', 0)
                        ->exists() ? 1 : 0;

                    // Nếu kích hoạt tổ này, xóa bản ghi "Phân xưởng trống" (group_id=0) nếu có
                    if ($active == 1) {
                        DB::table('employee_assignments')
                            ->where('employees_id', $employeeId)
                            ->where('production_code', $productionCode)
                            ->where('group_id', 0)
                            ->delete();
                    }

                    // Nếu bật tổ này, cũng tự động kích hoạt lại tất cả các phòng thuộc tổ này (để tránh bị deactive toàn bộ khi chuyển tổ)
                    if ($active == 1) {
                        DB::table('employee_assignments')
                            ->where('employees_id', $employeeId)
                            ->where('group_id', $id)
                            ->where('room_id', '>', 0)
                            ->update(['active' => 1, 'updated_at' => now()]);
                    }

                    // Nếu tắt tổ này, cũng tắt tất cả các phòng thuộc tổ này
                    if ($active == 0) {
                        DB::table('employee_assignments')
                            ->where('employees_id', $employeeId)
                            ->where('group_id', $id)
                            ->where('room_id', '>', 0)
                            ->update(['active' => 0, 'updated_at' => now()]);
                    }

                    $exists = DB::table('employee_assignments')
                        ->where('employees_id', $employeeId)
                        ->where('production_code', $productionCode)
                        ->where('group_id', $id)
                        ->where('room_id', 0)
                        ->exists();

                    // Nếu đã có bản ghi Phòng trong tổ này, không cần bản ghi Tổ trống (room_id=0)
                    $hasRooms = DB::table('employee_assignments')
                        ->where('employees_id', $employeeId)
                        ->where('group_id', $id)
                        ->where('room_id', '>', 0)
                        ->exists();

                    if ($hasRooms && $active == 1) {
                        // Xóa bản ghi Tổ trống nếu nó tồn tại
                        DB::table('employee_assignments')
                            ->where('employees_id', $employeeId)
                            ->where('group_id', $id)
                            ->where('room_id', 0)
                            ->delete();
                        continue;
                    }

                    if ($exists) {
                        DB::table('employee_assignments')
                            ->where('employees_id', $employeeId)
                            ->where('production_code', $productionCode)
                            ->where('group_id', $id)
                            ->where('room_id', 0)
                            ->update([
                                'is_main' => $isMain,
                                'active' => $active,
                                'created_by' => $userName,
                                'updated_at' => now()
                            ]);
                    } else {
                        DB::table('employee_assignments')->insert([
                            'employees_id' => $employeeId,
                            'production_code' => $productionCode,
                            'is_main' => $isMain,
                            'group_id' => $id,
                            'room_id' => 0,
                            'active' => $active,
                            'created_by' => $userName,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }
            } else {
                // Đánh dấu tất cả room là inactive trước
                DB::table('employee_assignments')
                    ->where('employees_id', $employeeId)
                    ->where('room_id', '>', 0)
                    ->update(['active' => 0]);

                foreach ($ids as $item) {
                    if (empty($item)) continue;
                    $parts = explode(':', $item);
                    $roomId = $parts[0];
                    $level = $parts[1] ?? 1;
                    $active = $parts[2] ?? 1;

                    // Lấy thông tin PX và Tổ từ bảng room
                    $room = DB::table('room')->where('id', $roomId)->first();
                    $productionCode = $room->deparment_code ?? '';
                    $groupCode = $room->group_code ?? '';
                    $groupId = DB::table('stage_groups')->where('code', $groupCode)->value('id') ?? 0;

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
                                'is_main' => $isMain,
                                'level' => $level,
                                'active' => $active,
                                'created_by' => $userName,
                                'updated_at' => now()
                            ]);
                    } else {
                        DB::table('employee_assignments')->insert([
                            'employees_id' => $employeeId,
                            'production_code' => $productionCode,
                            'is_main' => $isMain,
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
                            'is_main' => 0,
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
}
