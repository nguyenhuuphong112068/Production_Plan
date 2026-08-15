<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Cùng nhóm với layout_warning - "Chức Năng Canh Báo Lịch Vi Phạm" */
    private const PERMISSION_GROUP = 9;

    private const PERMISSIONS = [
        [
            'name' => 'layout_wip_coverage',
            'display_name' => 'Chức Năng Cảnh Báo Tồn BTP',
            'description' => 'Xem tồn bán thành phẩm giữa các công đoạn và số ngày đáp ứng cho công đoạn sau',
        ],
    ];

    /** Các nhóm quyền được cấp sẵn quyền này khi cài đặt */
    private const GRANT_TO_ROLES = ['Admin'];

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

        $roleIds = DB::table('roles')->whereIn('name', self::GRANT_TO_ROLES)->pluck('id');

        // updateOrInsert để chạy lại migration không sinh dòng trùng trong role_permission
        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permission')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    []
                );
            }
        }
    }

    public function down(): void
    {
        $names = array_column(self::PERMISSIONS, 'name');

        $ids = DB::table('permissions')->whereIn('name', $names)->pluck('id');

        DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('user_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
