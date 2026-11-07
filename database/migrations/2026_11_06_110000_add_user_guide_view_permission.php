<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Quyền xem Hướng Dẫn Sử Dụng (06/11/2026): user_guide_view, nhóm 0 "Chức Năng Chung (Menu)".
 * Chặn menu "Hướng Dẫn Sử Dụng", nút "?" trên topNAV và các route /userGuide (UserGuideController::canView).
 *
 * Cấp sẵn cho MỌI nhóm quyền đang hoạt động để không ai mất quyền xem sau khi cập nhật;
 * quản trị bỏ tick ở User Policy › Nhóm Phân Quyền với nhóm không cần xem.
 * Bản SQL tương đương: database/sql/2026_11_05-06_forming_mold_and_cleanup_permissions.sql (phần 5).
 *
 * Chạy riêng file này (bảng migrations lệch với CSDL, không chạy migrate trần):
 *   php artisan migrate --path=database/migrations/2026_11_06_110000_add_user_guide_view_permission.php
 */
return new class extends Migration
{
    private const NAME = 'user_guide_view';

    public function up(): void
    {
        DB::transaction(function () {
            if (! DB::table('permissions')->where('name', self::NAME)->exists()) {
                DB::table('permissions')->insert([
                    'permission_group' => 0,
                    'name' => self::NAME,
                    'display_name' => 'Xem Hướng Dẫn Sử Dụng',
                    'description' => 'Thấy menu Hướng Dẫn Sử Dụng, nút ? trên thanh trên cùng và mở các trang hướng dẫn',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $permissionId = DB::table('permissions')->where('name', self::NAME)->value('id');

            foreach (DB::table('roles')->where('active', 1)->pluck('id') as $roleId) {
                DB::table('role_permission')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId]);
            }
        });
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('name', self::NAME)->value('id');
        if (! $permissionId) {
            return;
        }
        DB::transaction(function () use ($permissionId) {
            DB::table('role_permission')->where('permission_id', $permissionId)->delete();
            DB::table('user_permission')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        });
    }
};
