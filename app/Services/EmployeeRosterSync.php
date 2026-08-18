<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Đồng bộ danh sách nhân sự từ eO2 PMS về bảng `employees` / `employee_assignments`.
 *
 * Tách khỏi LoginController vì máy chủ nguồn quá chậm để chạy trong luồng đăng
 * nhập: đo thực tế bộ phận 15 (PXV1) mất **~88 giây cho một ngày duy nhất**
 * (bộ phận 17 ~11s, bộ phận 6 ~9.5s). Không có mức timeout nào vừa đủ để vừa
 * đồng bộ được PXV1 vừa không treo màn hình đăng nhập.
 *
 * Vì vậy phân vai rõ ràng:
 *   - `App\Console\Commands\SyncEmployeeRoster` (chạy nền / theo lịch / thủ công)
 *     gọi `refresh()` — được phép chờ lâu, nó nạp luôn cache danh sách nhân sự.
 *   - Luồng đăng nhập gọi `syncFromCache()` — TUYỆT ĐỐI không gọi HTTP, chỉ dùng
 *     cache đã có sẵn nên luôn tức thì.
 */
class EmployeeRosterSync
{
    /** Mã bộ phận nội bộ -> ID bộ phận trên API eO2. */
    public const DEPARTMENTS = [
        'EN' => 3,
        'QA' => 9,
        'PXTN' => 6,
        'PXV1' => 15,
        'PXVH' => 30,
        'PXDN' => 34,
        'PXV2' => 32,
    ];

    public const WAREHOUSE_ID = 17;

    /** Nhân sự Kho (bộ phận 17) được phép hiển thị tại Trung Tâm Cân của PXV1. */
    public const WAREHOUSE_ALLOWED_CODES = [
        '21049', '21048', '21077', '21064', '21080', '21090',
        '21120', '21122', '21130', '21143', '21148', '21152',
    ];

    public function __construct(private ShiftApiService $shiftApi) {}

    /**
     * Gọi API lấy danh sách nhân sự mới nhất rồi đồng bộ xuống DB.
     * Dùng cho command chạy nền — được phép chờ lâu.
     *
     * @return array{synced:bool, employees:int, reason:?string}
     */
    public function refresh(string $departmentCode, ?int $timeout = null): array
    {
        $depId = self::DEPARTMENTS[$departmentCode] ?? null;
        if (!$depId) {
            return ['synced' => false, 'employees' => 0, 'reason' => "Bộ phận {$departmentCode} không có trong bảng ánh xạ"];
        }

        $needWarehouse = $departmentCode === 'PXV1';
        $departments = $needWarehouse ? [$depId, self::WAREHOUSE_ID] : [$depId];

        $rosters = $this->shiftApi->roster($departments, now(), $timeout);

        return $this->applyRosters($departmentCode, $depId, $rosters);
    }

    /**
     * Đồng bộ bằng danh sách nhân sự ĐÃ có trong cache — không gọi HTTP.
     * Dùng cho luồng đăng nhập: không có cache thì bỏ qua, không làm người dùng chờ.
     *
     * @return array{synced:bool, employees:int, reason:?string}
     */
    public function syncFromCache(string $departmentCode): array
    {
        $depId = self::DEPARTMENTS[$departmentCode] ?? null;
        if (!$depId) {
            return ['synced' => false, 'employees' => 0, 'reason' => 'Bộ phận không được hỗ trợ'];
        }

        // Dù không gọi HTTP, việc ghi DB cho bộ phận lớn vẫn tốn ~1.5-2s (PXV1
        // có 567 nhân sự). Command chạy nền mới là nơi đồng bộ chính, nên ở đây
        // chỉ chạy khi đã lâu không đồng bộ — đóng vai trò lưới an toàn phòng khi
        // scheduler không chạy.
        $throttleHours = (float) config('shiftapi.login_sync_interval_hours', 12);
        if ($throttleHours > 0 && Cache::get("employee_sync_last_run:{$departmentCode}")) {
            return ['synced' => false, 'employees' => 0, 'reason' => 'Đã đồng bộ gần đây'];
        }

        $needWarehouse = $departmentCode === 'PXV1';
        $departments = $needWarehouse ? [$depId, self::WAREHOUSE_ID] : [$depId];

        $rosters = $this->shiftApi->cachedRoster($departments);
        if (!isset($rosters[$depId])) {
            // Không có cache nghĩa là command chạy nền đã lâu không chạy. Ghi log
            // (có giới hạn tần suất để khỏi spam) để còn phát hiện scheduler chết.
            if (!Cache::get("employee_sync_stale_warned:{$departmentCode}")) {
                Cache::put("employee_sync_stale_warned:{$departmentCode}", 1, 3600);
                Log::warning(
                    "Cache danh sach nhan su rong - hay kiem tra scheduler co chay 'employees:sync-roster' khong",
                    ['department' => $departmentCode]
                );
            }
            return ['synced' => false, 'employees' => 0, 'reason' => 'Chưa có dữ liệu trong cache'];
        }

        return $this->applyRosters($departmentCode, $depId, $rosters);
    }

    /**
     * Ghi danh sách nhân sự xuống DB.
     *
     * @param array $rosters [departmentId => [code => name]]
     */
    private function applyRosters(string $departmentCode, int $depId, array $rosters): array
    {
        // Bộ phận chính không lấy được -> bỏ hẳn lần đồng bộ này, nếu không sẽ
        // vô hiệu hoá nhầm toàn bộ nhân sự của bộ phận.
        if (!isset($rosters[$depId])) {
            return ['synced' => false, 'employees' => 0, 'reason' => 'Không lấy được danh sách bộ phận chính'];
        }

        $employeesFromApi = [];
        foreach ($rosters[$depId] as $empCode => $empName) {
            $employeesFromApi[] = (object) [
                'employeeId' => (string) $empCode,
                'employeeName' => $empName,
                'is_warehouse' => false,
            ];
        }

        // Cờ đánh dấu API kho (dept 17) có lấy được dữ liệu hay không. Dùng để
        // tránh vô hiệu hóa nhầm nhân sự kho khi API kho lỗi tạm thời (timeout/429).
        $api17Ok = false;
        if ($departmentCode === 'PXV1' && isset($rosters[self::WAREHOUSE_ID])) {
            $api17Ok = true;
            foreach ($rosters[self::WAREHOUSE_ID] as $empCode => $empName) {
                $employeesFromApi[] = (object) [
                    'employeeId' => (string) $empCode,
                    'employeeName' => trim((string) $empName) . ' - WH',
                    'is_warehouse' => true,
                ];
            }
        }

        if (empty($employeesFromApi)) {
            return ['synced' => false, 'employees' => 0, 'reason' => 'Danh sách nhân sự rỗng'];
        }

        $warehouseAllowedCodes = self::WAREHOUSE_ALLOWED_CODES;

        $apiEmployeeCodes = array_map(fn($emp) => $emp->employeeId, $employeesFromApi);

        // Nếu API kho lỗi, coi các mã kho hợp lệ như vẫn còn trong API để không bị vô hiệu hóa nhầm
        if ($departmentCode === 'PXV1' && !$api17Ok) {
            $apiEmployeeCodes = array_merge($apiEmployeeCodes, $warehouseAllowedCodes);
        }

        DB::transaction(function () use ($employeesFromApi, $apiEmployeeCodes, $departmentCode, $warehouseAllowedCodes) {
            // 1. Vô hiệu hóa các phân công (assignments) không còn trong API cho bộ phận này
            // Bỏ qua bộ phận QA vì có một số nhân sự được quản lý thủ công (không có trong API)
            if ($departmentCode != 'QA') {
                $activeAssignments = DB::table('employee_assignments as ea')
                    ->join('employees as e', 'ea.employees_id', '=', 'e.id')
                    ->where('ea.production_code', $departmentCode)
                    ->where('ea.active', 1)
                    ->select('ea.id', 'e.id as employee_id', 'e.code')
                    ->get();

                foreach ($activeAssignments as $assignment) {
                    if (!in_array($assignment->code, $apiEmployeeCodes)) {
                        // Vô hiệu hóa assignment
                        DB::table('employee_assignments')
                            ->where('id', $assignment->id)
                            ->update(['active' => 0, 'updated_at' => now()]);

                        // Sau khi vô hiệu hóa assignment này, kiểm tra xem nhân viên còn assignment active nào khác không
                        $otherActiveAssignmentsCount = DB::table('employee_assignments')
                            ->where('employees_id', $assignment->employee_id)
                            ->where('active', 1)
                            ->count();

                        // Nếu không còn assignment nào active, vô hiệu hóa luôn nhân viên (soft delete)
                        if ($otherActiveAssignmentsCount == 0) {
                            DB::table('employees')
                                ->where('id', $assignment->employee_id)
                                ->update(['active' => 0, 'updated_at' => now()]);
                        }
                    }
                }
            }

            // 2. Cập nhật hoặc thêm mới nhân sự từ API
            foreach ($employeesFromApi as $emp) {
                if (empty($emp->employeeId)) continue;

                // Đảm bảo nhân sự tồn tại trong bảng employees
                $employee = DB::table('employees')->where('code', $emp->employeeId)->first();

                // Rule: "các nhân sự có employees.resign không tiến hành cập nhật lại"
                if ($employee && $employee->resign == 1) {
                    continue;
                }

                $isWarehouse = !empty($emp->is_warehouse);
                $isAllowedWarehouse = $isWarehouse && in_array((string) $emp->employeeId, $warehouseAllowedCodes);

                $resignVal = $isWarehouse ? ($isAllowedWarehouse ? 0 : 1) : 0;
                $activeVal = $isWarehouse ? ($isAllowedWarehouse ? 1 : 0) : 1;
                $groupIdVal = $isWarehouse ? ($isAllowedWarehouse ? 1 : 0) : 0;

                if (!$employee) {
                    $employeeId = DB::table('employees')->insertGetId([
                        'code' => $emp->employeeId,
                        'name' => $emp->employeeName ?? 'N/A',
                        'active' => $activeVal,
                        'resign' => $resignVal,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                } else {
                    $employeeId = $employee->id;
                    // Cập nhật lại tên và đảm bảo active status được đồng bộ đúng
                    DB::table('employees')->where('id', $employeeId)->update([
                        'name' => $emp->employeeName ?? $employee->name,
                        'active' => $activeVal,
                        'resign' => $resignVal,
                        'updated_at' => now()
                    ]);
                }

                // Đồng bộ vào bảng phân vùng sản xuất (employee_assignments)
                $hasAssignment = DB::table('employee_assignments')
                    ->where('employees_id', $employeeId)
                    ->where('production_code', $departmentCode)
                    ->exists();

                if (!$hasAssignment) {
                    // Nếu chưa từng có phân công tại bộ phận này, tạo mới bản ghi chính (is_main = 1)
                    DB::table('employee_assignments')->insert([
                        'employees_id' => $employeeId,
                        'production_code' => $departmentCode,
                        'is_main' => 1,
                        'group_id' => $groupIdVal,
                        'room_id' => 0,
                        'active' => $activeVal,
                        'created_by' => 'System Sync',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                } else {
                    // Nếu đã từng có dữ liệu tại đây (có thể là nhiều dòng bao gồm cả phân tổ/phòng),
                    // chỉ cập nhật lại TẤT CẢ các dòng liên quan nếu trạng thái active của nhân viên thực sự thay đổi
                    if ($departmentCode != 'QA') {
                        $updateData = ['updated_at' => now()];

                        $statusChanged = ($employee && $employee->active != $activeVal);
                        if ($statusChanged) {
                            $updateData['active'] = $activeVal;
                        }

                        if ($isWarehouse) {
                            $updateData['group_id'] = $groupIdVal;
                        }

                        // Nếu không có thay đổi active hoặc group_id thì không cần update toàn bộ bảng
                        if ($statusChanged || $isWarehouse) {
                            DB::table('employee_assignments')
                                ->where('employees_id', $employeeId)
                                ->where('production_code', $departmentCode)
                                ->update($updateData);
                        }
                    }
                }
            }
        });

        Log::info('Dong bo nhan su thanh cong', [
            'department' => $departmentCode,
            'employees' => count($employeesFromApi),
        ]);

        // TTL trùng với khoảng chặn tần suất: hết hạn nghĩa là "đã lâu không đồng
        // bộ", lúc đó lần đăng nhập kế tiếp sẽ tự chạy lại như một lưới an toàn.
        $throttleHours = (float) config('shiftapi.login_sync_interval_hours', 12);
        Cache::put(
            "employee_sync_last_run:{$departmentCode}",
            now()->toDateTimeString(),
            (int) (($throttleHours > 0 ? $throttleHours : 12) * 3600)
        );

        return ['synced' => true, 'employees' => count($employeesFromApi), 'reason' => null];
    }
}
