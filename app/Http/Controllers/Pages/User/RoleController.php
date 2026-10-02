<?php

namespace App\Http\Controllers\Pages\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    /** Nhóm quyền Admin luôn giữ toàn quyền, không cho sửa / vô hiệu hoá */
    private const ADMIN_ROLE_ID = 1;

    /** Quyền cần có để tạo / sửa / vô hiệu hoá nhóm quyền */
    private const MANAGE_PERMISSION = 'layout_User';

    /**
     * Tên nhóm chức năng theo permissions.permission_group.
     * Mỗi nhóm là 1 card trên bảng, thứ tự khai báo cũng là thứ tự hiển thị.
     */
    private const GROUPS = [
        0  => ['label' => 'Chức Năng Chung (Menu)', 'icon' => 'fa-layer-group'],
        1  => ['label' => 'Kế Hoạch', 'icon' => 'fa-clipboard-list'],
        2  => ['label' => 'Dữ Liệu Gốc', 'icon' => 'fa-database'],
        3  => ['label' => 'Danh Mục', 'icon' => 'fa-list-alt'],
        4  => ['label' => 'Định Mức', 'icon' => 'fa-chart-line'],
        5  => ['label' => 'Thông Báo', 'icon' => 'fa-bell'],
        6  => ['label' => 'Lịch Sản Xuất - Thực Thi', 'icon' => 'fa-industry'],
        7  => ['label' => 'Báo Cáo', 'icon' => 'fa-chart-bar'],
        8  => ['label' => 'Báo Cáo Ngày', 'icon' => 'fa-calendar-day'],
        9  => ['label' => 'Bao Bì - Cảnh Báo', 'icon' => 'fa-exclamation-triangle'],
        10 => ['label' => 'Phân Công Công Việc', 'icon' => 'fa-user-check'],
        11 => ['label' => 'Chính Sách Sản Lượng', 'icon' => 'fa-balance-scale'],
        12 => ['label' => 'Cảnh Báo Lịch NL/BB', 'icon' => 'fa-calendar-times'],
        13 => ['label' => 'Tồn Kho MMS', 'icon' => 'fa-warehouse'],
        14 => ['label' => 'Kế Hoạch Năm', 'icon' => 'fa-calendar-alt'],
        15 => ['label' => 'Theo Dõi', 'icon' => 'fa-tasks'],
    ];

    public function index()
    {
        // Toàn bộ nhóm quyền (kể cả đã vô hiệu) cho modal quản lý, kèm số user đang gán
        $userCounts = DB::table('user_role')
            ->select('role_id', DB::raw('COUNT(*) as total'))
            ->groupBy('role_id')
            ->pluck('total', 'role_id');

        $allRoles = DB::table('roles')->orderBy('id', 'asc')->get()
            ->map(function ($role) use ($userCounts) {
                $role->user_count = $userCounts[$role->id] ?? 0;
                return $role;
            });

        // Ma trận chỉ hiện nhóm quyền đang dùng
        $roles = $allRoles->where('active', 1)->values();

        $permissions = DB::table('permissions')
            ->select('id', 'name', 'display_name', 'permission_group')
            ->orderBy('permission_group', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Gom quyền theo nhóm chức năng: mỗi nhóm 1 card
        $tree = [];
        foreach ($permissions as $permission) {
            $key = $permission->permission_group;

            if (!isset($tree[$key])) {
                $meta = self::GROUPS[$key] ?? ['label' => 'Khác', 'icon' => 'fa-ellipsis-h'];
                $tree[$key] = ['label' => $meta['label'], 'icon' => $meta['icon'], 'permissions' => []];
            }

            $tree[$key]['permissions'][] = $permission;
        }

        // Khoá "roleId-permissionId" để view tra nhanh trạng thái checkbox
        $assigned = DB::table('role_permission')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->role_id . '-' . $item->permission_id => true])
            ->toArray();

        session()->put(['title' => 'DANH SÁCH NHÓM QUYỀN']);

        return view('pages.User.role.list', [
            'roles' => $roles,
            'allRoles' => $allRoles,
            'tree' => $tree,
            'assigned' => $assigned,
            'canManage' => $this->canManage(),
        ]);
    }

    private function canManage(): bool
    {
        $userId = session('user')['userId'] ?? null;

        return $userId && user_has_permission($userId, self::MANAGE_PERMISSION, 'boolean');
    }

    /**
     * Bật / tắt một quyền cho một nhóm quyền (checkbox trên ma trận).
     */
    public function store_or_update(Request $request)
    {
        try {
            $roleId = $request->input('role_id');
            $permissionId = $request->input('permission_id');
            $checked = filter_var($request->input('checked'), FILTER_VALIDATE_BOOLEAN);

            if (!$roleId || !$permissionId) {
                return response()->json(['error' => 'Thiếu dữ liệu role hoặc permission'], 400);
            }

            if ($checked) {
                DB::table('role_permission')->updateOrInsert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ], []);
            } else {
                if ($roleId == self::ADMIN_ROLE_ID) {
                    return response()->json(['error' => 'Không thể gỡ quyền của nhóm Admin'], 400);
                }

                DB::table('role_permission')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionId)
                    ->delete();
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Thêm mới hoặc đổi tên / diễn giải một nhóm quyền.
     * Có 'id' -> cập nhật, không có -> tạo mới.
     */
    public function saveRole(Request $request)
    {
        if (!$this->canManage()) {
            return redirect()->back()->withErrors(['name' => 'Bạn không có quyền quản lý nhóm quyền.'], 'roleErrors');
        }

        $id = (int) $request->input('id');

        if ($id === self::ADMIN_ROLE_ID) {
            return redirect()->back()
                ->withErrors(['name' => 'Không thể chỉnh sửa nhóm quyền Admin.'], 'roleErrors')
                ->withInput();
        }

        $validator = Validator::make($request->all(), [
            'name' => [
                'required', 'string', 'max:100',
                $id ? 'unique:roles,name,' . $id : 'unique:roles,name',
            ],
            'description' => ['nullable', 'string', 'max:255'],
        ], [
            'name.required' => 'Vui lòng nhập tên nhóm quyền.',
            'name.unique' => 'Tên nhóm quyền này đã tồn tại.',
            'name.max' => 'Tên nhóm quyền không vượt quá :max ký tự.',
            'description.max' => 'Diễn giải không vượt quá :max ký tự.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'roleErrors')->withInput();
        }

        $name = trim($request->name);

        if ($id) {
            $old = DB::table('roles')->where('id', $id)->first();

            if (!$old) {
                return redirect()->back()->withErrors(['name' => 'Nhóm quyền không tồn tại.'], 'roleErrors');
            }

            DB::transaction(function () use ($id, $old, $name, $request) {
                DB::table('roles')->where('id', $id)->update([
                    'name' => $name,
                    'display_name' => $request->description,
                    'description' => $request->description,
                    'updated_at' => now(),
                ]);

                // user_management.userGroup lưu tên role chính của user -> đổi tên phải đổi theo
                if ($old->name !== $name) {
                    DB::table('user_management')->where('userGroup', $old->name)->update(['userGroup' => $name]);
                }
            });

            return redirect()->back()->with('success', 'Đã cập nhật nhóm quyền!');
        }

        DB::table('roles')->insert([
            'name' => $name,
            'display_name' => $request->description,
            'description' => $request->description,
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Đã thêm nhóm quyền!');
    }

    /**
     * Vô hiệu hoá / kích hoạt lại một nhóm quyền.
     * Không xoá dữ liệu: role_permission và user_role giữ nguyên để kích hoạt lại là dùng được ngay.
     */
    public function deActive($id)
    {
        if (!$this->canManage()) {
            return redirect()->back()->withErrors(['name' => 'Bạn không có quyền quản lý nhóm quyền.'], 'roleErrors');
        }

        $id = (int) $id;

        if ($id === self::ADMIN_ROLE_ID) {
            return redirect()->back()->withErrors(['name' => 'Không thể vô hiệu hoá nhóm quyền Admin.'], 'roleErrors');
        }

        $role = DB::table('roles')->where('id', $id)->first();

        if (!$role) {
            return redirect()->back()->withErrors(['name' => 'Nhóm quyền không tồn tại.'], 'roleErrors');
        }

        $active = $role->active ? 0 : 1;

        DB::table('roles')->where('id', $id)->update([
            'active' => $active,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with(
            'success',
            $active ? 'Đã kích hoạt lại nhóm quyền!' : 'Vô hiệu hoá nhóm quyền thành công!'
        );
    }
}
