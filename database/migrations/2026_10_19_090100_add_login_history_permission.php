<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Quyền xem Lịch Sử Đăng Nhập toàn hệ thống (LoginHistoryController::index).
 * Trang "Của tôi" không cần quyền — ai đăng nhập cũng xem được lịch sử của chính mình.
 */
return new class extends Migration
{
    private const PERMISSION = [
        'name' => 'login_history_view',
        'display_name' => 'Xem Lịch Sử Đăng Nhập',
        'description' => 'Lượt đăng nhập của mọi tài khoản (thành công / sai mật khẩu / bị khoá), phiên đang mở, tài khoản nhập sai bất thường, xuất Excel',
    ];

    private const GRANT_TO_ROLES = ['Admin', 'QA Manager', 'Compliance_Officer'];

    public function up(): void
    {
        $now = Carbon::now();

        DB::table('permissions')->updateOrInsert(
            ['name' => self::PERMISSION['name']],
            self::PERMISSION + ['permission_group' => 0, 'created_at' => $now, 'updated_at' => $now]
        );

        $permissionId = DB::table('permissions')->where('name', self::PERMISSION['name'])->value('id');

        foreach (DB::table('roles')->whereIn('name', self::GRANT_TO_ROLES)->pluck('id') as $roleId) {
            DB::table('role_permission')->updateOrInsert(['role_id' => $roleId, 'permission_id' => $permissionId], []);
        }
    }

    public function down(): void
    {
        $id = DB::table('permissions')->where('name', self::PERMISSION['name'])->value('id');

        DB::table('role_permission')->where('permission_id', $id)->delete();
        DB::table('user_permission')->where('permission_id', $id)->delete();
        DB::table('permissions')->where('id', $id)->delete();
    }
};
