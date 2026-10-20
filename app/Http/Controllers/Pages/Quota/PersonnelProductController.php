<?php

namespace App\Http\Controllers\Pages\Quota;

use App\Http\Controllers\Controller;
use App\Services\EmployeeProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Nhân sự – Sản phẩm: khai báo BTP/TP mà nhân viên vận hành được phép thực hiện.
 * Giao diện là tab "Sản phẩm được phép" của trang Danh sách nhân viên (PersonnelController@index);
 * controller này chỉ còn các API dùng cho tab đó.
 * Phòng được làm suy ra từ phòng của NV ∩ phòng định mức (quota) của sản phẩm — xem EmployeeProductService.
 */
class PersonnelProductController extends Controller
{
    public function __construct(private EmployeeProductService $service)
    {
    }

    /** Đường dẫn cũ của trang riêng → tab "Sản phẩm được phép" của Danh sách nhân viên */
    public function index($department = null)
    {
        $department = $department ?: (session('user')['production_code'] ?? session('user')['department']);

        return redirect()->route('pages.quota.personnel.list', ['department' => $department, 'tab' => 'products']);
    }

    /** Sản phẩm của 1 NV (cả 2 loại) cho modal khai báo */
    public function employee($id)
    {
        $employee = DB::table('employees')->where('id', $id)->select('id', 'code', 'name')->first();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy nhân viên.'], 404);
        }

        $roomIds = $this->service->employeeRooms([$employee->id])[$employee->id] ?? [];

        return response()->json([
            'success' => true,
            'employee' => $employee,
            'rooms' => $this->roomLabels($roomIds),
            'products' => [
                EmployeeProductService::TYPE_BTP => $this->service->productsForEmployee($employee->id, EmployeeProductService::TYPE_BTP),
                EmployeeProductService::TYPE_TP => $this->service->productsForEmployee($employee->id, EmployeeProductService::TYPE_TP),
            ],
        ]);
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'type' => 'required|in:' . implode(',', array_keys(EmployeeProductService::TYPES)),
            'codes' => 'nullable|array',
            'codes.*' => 'string|max:20',
        ]);

        $department = $this->employeeDepartment((int) $data['employee_id']);
        if (!EmployeeProductService::userCanEdit($department)) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền khai báo sản phẩm cho nhân viên của bộ phận này.'], 403);
        }

        $user = session('user');
        $result = $this->service->saveDeclarations(
            (int) $data['employee_id'],
            $data['type'],
            $data['codes'] ?? [],
            $user['fullName'] ?? ($user['userName'] ?? 'System'),
            $user['userName'] ?? 'System'
        );

        // Danh sách sau khi lưu (kèm người / lúc cập nhật) để giao diện dựng lại ô của NV
        $items = $this->service->declaredItems([(int) $data['employee_id']])[(int) $data['employee_id']][$data['type']] ?? [];

        return response()->json([
            'success' => true,
            'message' => "Đã lưu: thêm {$result['added']}, bỏ {$result['removed']} sản phẩm.",
            'total' => count($items),
            'items' => $items,
        ]);
    }

    private function employeeDepartment(int $employeeId): ?string
    {
        return DB::table('employee_assignments')
            ->where('employees_id', $employeeId)
            ->where('active', 1)
            ->where('production_code', '!=', '')
            ->orderByDesc('is_main')
            ->value('production_code');
    }

    private function roomLabels(array $roomIds): array
    {
        if (!$roomIds) {
            return [];
        }
        return DB::table('room')
            ->whereIn('id', array_unique($roomIds))
            ->orderBy('stage_code')
            ->orderBy('order_by')
            ->select('id', 'code', 'name', 'main_equiment_name', 'stage_code')
            ->get()
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'code' => $r->code,
                'name' => trim($r->name . ($r->main_equiment_name ? ' - ' . $r->main_equiment_name : '')),
                'stage_code' => (int) $r->stage_code,
            ])
            ->all();
    }
}
