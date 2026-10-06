<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Phân quyền thao tác trên card phòng của trang Thực Thi Sản Xuất / Ghi Nhận Sản Xuất:
 * Nhận phòng, Nhận phòng vệ sinh sau bảo trì, Trả phòng. Kiểm tra ở ProductionExecutionController::act().
 * Vào trang vẫn do layout_production_execution / layout_production_record.
 */
return new class extends Migration
{
    /** Cùng nhóm với layout_production_execution */
    private const PERMISSION_GROUP = 6;

    private const PERMISSIONS = [
        [
            'name' => 'execution_receive',
            'display_name' => 'Thực Thi SX: Nhận Phòng',
            'description' => 'Bấm [Nhận phòng]: chọn lô / lịch bảo trì và ghi giờ nhận phòng (actual_start)',
        ],
        [
            'name' => 'execution_receive_cleaning',
            'display_name' => 'Thực Thi SX: Nhận Phòng Vệ Sinh Sau Bảo Trì',
            'description' => 'Bấm [Nhận phòng vệ sinh sau BT] khi phòng đã bảo trì xong và chờ vệ sinh (actual_start_clearning)',
        ],
        [
            'name' => 'execution_release',
            'display_name' => 'Thực Thi SX: Trả Phòng',
            'description' => 'Bấm [Trả phòng]: ghi giờ trả phòng (actual_end_clearning / actual_end của lịch bảo trì) và tịnh tuyến lịch nếu công tắc đang bật',
        ],
    ];

    /**
     * Các nhóm quyền được cấp sẵn để không làm gián đoạn vận hành: Admin, Executor (trang Ghi Nhận Sản Xuất),
     * Leader, Production Clerk. Nhóm khác (Viewer, Planner...) tự tick ở trang phân quyền nếu cần.
     */
    private const GRANT_TO_ROLES = ['Admin', 'Executor', 'Leader', 'Production Clerk'];

    public function up(): void
    {
        $now = Carbon::now();

        foreach (self::PERMISSIONS as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name']],
                $permission + ['permission_group' => self::PERMISSION_GROUP, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $permissionIds = DB::table('permissions')->whereIn('name', array_column(self::PERMISSIONS, 'name'))->pluck('id');

        // updateOrInsert để chạy lại migration không sinh dòng trùng trong role_permission
        foreach (DB::table('roles')->whereIn('name', self::GRANT_TO_ROLES)->pluck('id') as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permission')->updateOrInsert(['role_id' => $roleId, 'permission_id' => $permissionId], []);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('name', array_column(self::PERMISSIONS, 'name'))->pluck('id');

        DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('user_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
