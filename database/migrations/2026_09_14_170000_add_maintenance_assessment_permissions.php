<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Nhóm quyền của các chức năng kế hoạch / lịch bảo trì - hiệu chuẩn */
    private const PERMISSION_GROUP = 1;

    /** Quyền được cấp sẵn cho nhóm Admin */
    private const GRANT_TO_ROLE_IDS = [1];

    private const PERMISSIONS = [
        [
            'name' => 'maintenance_assessment_view',
            'display_name' => 'Xem Đánh Giá BT-HC',
            'description' => 'Xem trang đánh giá công tác bảo trì - hiệu chuẩn theo tháng',
        ],
        [
            'name' => 'maintenance_assessment_create',
            'display_name' => 'Đánh Giá Công Tác BT-HC',
            'description' => 'Tạo / sửa đánh giá công tác bảo trì - hiệu chuẩn, kể cả đánh giá ngoài kế hoạch',
        ],
        [
            'name' => 'maintenance_assessment_update_employees',
            'display_name' => 'Cập Nhật Nhân Sự Đánh Giá BT-HC',
            'description' => 'Cập nhật lại danh sách nhân sự liên quan của một đánh giá đã có',
        ],
    ];

    public function up(): void
    {
        $now = Carbon::now();

        foreach (self::PERMISSIONS as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name']],
                array_merge($permission, [
                    'permission_group' => self::PERMISSION_GROUP,
                    'updated_at' => $now,
                    'created_at' => $now,
                ])
            );
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', array_column(self::PERMISSIONS, 'name'))
            ->pluck('id');

        foreach (self::GRANT_TO_ROLE_IDS as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permission')->updateOrInsert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('name', array_column(self::PERMISSIONS, 'name'))
            ->pluck('id');

        DB::table('role_permission')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('user_permission')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
