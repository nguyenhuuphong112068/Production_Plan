<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Nhóm quyền của các chức năng thuộc tab Danh Mục */
    private const PERMISSION_GROUP = 3;

    private const PERMISSIONS = [
        [
            'name' => 'material_source_warning_create',
            'display_name' => 'Thêm Ma Trận Cảnh Báo Nguồn NL',
            'description' => 'Khai báo mới 1 dòng ma trận Mã BTP - Mã NL - Thị trường - Thiết bị theo công đoạn',
        ],
        [
            'name' => 'material_source_warning_update',
            'display_name' => 'Cập Nhật Ma Trận Cảnh Báo Nguồn NL',
            'description' => 'Sửa thị trường, ghi chú và danh sách thiết bị được phép của 1 dòng ma trận',
        ],
        [
            'name' => 'material_source_warning_deActive',
            'display_name' => 'Vô Hiệu Ma Trận Cảnh Báo Nguồn NL',
            'description' => 'Bật/tắt hiệu lực của 1 dòng ma trận cảnh báo nguồn NL',
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
