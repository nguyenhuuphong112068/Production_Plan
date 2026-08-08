<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Nhóm quyền của các chức năng theo dõi thuộc tab Kế Hoạch */
    private const PERMISSION_GROUP = 15;

    private const PERMISSIONS = [
        [
            'name' => 'kcs_tracking_update',
            'display_name' => 'Cập Nhật Theo Dõi Hồ Sơ KCS',
            'description' => 'Nhập / sửa các mốc hồ sơ KCS của lô sản xuất',
        ],
    ];

    /** Các nhóm quyền được cấp sẵn quyền này khi cài đặt */
    private const GRANT_TO_ROLES = ['Admin'];

    /**
     * Run the migrations.
     */
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $names = array_column(self::PERMISSIONS, 'name');

        $ids = DB::table('permissions')->whereIn('name', $names)->pluck('id');

        DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('user_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
