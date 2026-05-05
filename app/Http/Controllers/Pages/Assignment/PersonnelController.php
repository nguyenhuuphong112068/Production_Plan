<?php

namespace App\Http\Controllers\Pages\Assignment;

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
            ->join('employee_productions as ep', 'e.id', '=', 'ep.employees_id')
            ->select('e.*')
            ->addSelect(DB::raw("(SELECT GROUP_CONCAT(CONCAT(eg.group_id, ':', eg.active, ':', COALESCE(u.name, eg.created_by), ':', DATE_FORMAT(eg.created_at, '%d/%m/%y')) SEPARATOR '|') FROM employee_groups eg LEFT JOIN employees u ON eg.created_by = u.code WHERE eg.employees_id = e.id) as allowed_groups"))
            ->addSelect(DB::raw("(SELECT GROUP_CONCAT(CONCAT(er.room_id, ':', er.level, ':', er.active, ':', COALESCE(u.name, er.created_by), ':', DATE_FORMAT(er.created_at, '%d/%m/%y')) SEPARATOR '|') FROM employee_rooms er LEFT JOIN employees u ON er.created_by = u.code WHERE er.employees_id = e.id) as allowed_rooms_with_levels"))
            ->addSelect(DB::raw("(SELECT production_code FROM employee_productions WHERE employees_id = e.id AND is_main = 1 LIMIT 1) as main_production"))
            ->addSelect(DB::raw("(SELECT GROUP_CONCAT(CONCAT(ep2.production_code, ':', ep2.active, ':', COALESCE(u.name, ep2.created_by), ':', DATE_FORMAT(ep2.created_at, '%d/%m/%y')) SEPARATOR '|') FROM employee_productions ep2 LEFT JOIN employees u ON ep2.created_by = u.code WHERE ep2.employees_id = e.id AND ep2.is_main = 0) as temp_productions"))
            ->where('ep.production_code', $departmentCode)
            ->where('ep.active', 1);

        if ($filterGroupId) {
            $query->whereExists(function ($q) use ($filterGroupId) {
                $q->select(DB::raw(1))
                    ->from('employee_groups')
                    ->whereColumn('employees_id', 'e.id')
                    ->where('group_id', $filterGroupId);
            });
        }

        if ($filterRoomId) {
            $query->whereExists(function ($q) use ($filterRoomId) {
                $q->select(DB::raw(1))
                    ->from('employee_rooms')
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
        $groups = DB::table('stage_groups')->get();
        $rooms = DB::table('room')->where('deparment_code', $departmentCode)->get();

        return view('pages.assignment.personnel.list', [
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
                DB::table('employee_productions')
                    ->where('employees_id', $employeeId)
                    ->where('is_main', 1)
                    ->delete();

                DB::table('employee_productions')->insert([
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
                // Đánh dấu tất cả là inactive trước
                DB::table('employee_groups')->where('employees_id', $employeeId)->update(['active' => 0]);

                foreach ($ids as $item) {
                    if (empty($item)) continue;

                    $parts = explode(':', $item);
                    $id = $parts[0];
                    $active = $parts[1] ?? 1;

                    // Cập nhật hoặc thêm mới với trạng thái active tương ứng
                    $exists = DB::table('employee_groups')
                        ->where('employees_id', $employeeId)
                        ->where('group_id', $id)
                        ->exists();

                    if ($exists) {
                        DB::table('employee_groups')
                            ->where('employees_id', $employeeId)
                            ->where('group_id', $id)
                            ->update([
                                'active' => $active,
                                'created_by' => $userName
                            ]);
                    } else {
                        DB::table('employee_groups')->insert([
                            'employees_id' => $employeeId,
                            'group_id' => $id,
                            'active' => $active,
                            'created_by' => $userName,
                            'created_at' => now()
                        ]);
                    }
                }
            } else {
                // Đánh dấu tất cả room là inactive trước
                DB::table('employee_rooms')->where('employees_id', $employeeId)->update(['active' => 0]);

                foreach ($ids as $item) {
                    if (empty($item)) continue;
                    $parts = explode(':', $item);
                    $roomId = $parts[0];
                    $level = $parts[1] ?? 1;
                    $active = $parts[2] ?? 1;

                    $exists = DB::table('employee_rooms')
                        ->where('employees_id', $employeeId)
                        ->where('room_id', $roomId)
                        ->exists();

                    if ($exists) {
                        DB::table('employee_rooms')
                            ->where('employees_id', $employeeId)
                            ->where('room_id', $roomId)
                            ->update([
                                'level' => $level,
                                'active' => $active,
                                'created_by' => $userName
                            ]);
                    } else {
                        DB::table('employee_rooms')->insert([
                            'employees_id' => $employeeId,
                            'room_id' => $roomId,
                            'level' => $level,
                            'active' => $active,
                            'created_by' => $userName,
                            'created_at' => now()
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Cập nhật thành công!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateProductions(Request $request)
    {
        $employeeId = $request->employee_id;
        $productions = $request->productions ?? []; // Array of production codes
        $userName = session('user')['fullName'] ?? session('user')['userName'] ?? 'System';

        try {
            DB::beginTransaction();

            // Lấy mã phân xưởng trực thuộc để không xóa nhầm
            $mainProduction = DB::table('employee_productions')
                ->where('employees_id', $employeeId)
                ->where('is_main', 1)
                ->first();

            // Đánh dấu tất cả các phân xưởng công tác tạm thời là inactive trước
            DB::table('employee_productions')
                ->where('employees_id', $employeeId)
                ->where('is_main', 0)
                ->update(['active' => 0]);

            // Cập nhật hoặc thêm mới các phân xưởng công tác tạm thời
            foreach ($productions as $code) {
                if (empty($code)) continue;
                if ($mainProduction && $code == $mainProduction->production_code) continue;

                $exists = DB::table('employee_productions')
                    ->where('employees_id', $employeeId)
                    ->where('production_code', $code)
                    ->exists();

                if ($exists) {
                    DB::table('employee_productions')
                        ->where('employees_id', $employeeId)
                        ->where('production_code', $code)
                        ->update([
                            'active' => 1,
                            'created_by' => $userName,
                            'updated_at' => now()
                        ]);
                } else {
                    DB::table('employee_productions')->insert([
                        'employees_id' => $employeeId,
                        'production_code' => $code,
                        'is_main' => 0,
                        'active' => 1,
                        'created_by' => $userName,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Cập nhật phân xưởng công tác thành công!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
