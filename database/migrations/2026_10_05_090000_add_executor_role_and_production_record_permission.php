<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Cùng nhóm với layout_production_execution */
    private const PERMISSION_GROUP = 6;

    private const PERMISSION = [
        'name' => 'layout_production_record',
        'display_name' => 'Trang Ghi Nhận Sản Xuất',
        'description' => 'Vào trang Ghi Nhận Sản Xuất: nhận / trả phòng tại các phòng mình đang được phân công trên Lịch Công Tác',
    ];

    /** Role chính là Executor thì chỉ vào được trang Ghi Nhận Sản Xuất (App\Http\Middleware\RestrictExecutor::ROLE) */
    private const ROLE = [
        'name' => 'Executor',
        'display_name' => 'Người Thực Thi',
        'description' => 'Người Thực Thi - đăng nhập chỉ thấy trang Ghi Nhận Sản Xuất với các phòng đang được phân công',
    ];

    private const GRANT_TO_ROLES = ['Admin', 'Executor'];

    public function up(): void
    {
        $now = Carbon::now();

        DB::table('permissions')->updateOrInsert(
            ['name' => self::PERMISSION['name']],
            self::PERMISSION + ['permission_group' => self::PERMISSION_GROUP, 'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('roles')->updateOrInsert(
            ['name' => self::ROLE['name']],
            self::ROLE + ['active' => 1, 'created_at' => $now, 'updated_at' => $now]
        );

        $permissionId = DB::table('permissions')->where('name', self::PERMISSION['name'])->value('id');
        foreach (DB::table('roles')->whereIn('name', self::GRANT_TO_ROLES)->pluck('id') as $roleId) {
            DB::table('role_permission')->updateOrInsert(['role_id' => $roleId, 'permission_id' => $permissionId], []);
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('name', self::PERMISSION['name'])->value('id');
        DB::table('role_permission')->where('permission_id', $permissionId)->delete();
        DB::table('user_permission')->where('permission_id', $permissionId)->delete();
        DB::table('permissions')->where('id', $permissionId)->delete();

        // Chỉ xóa role khi chưa gán cho ai
        $roleId = DB::table('roles')->where('name', self::ROLE['name'])->value('id');
        if ($roleId && !DB::table('user_role')->where('role_id', $roleId)->exists()) {
            DB::table('role_permission')->where('role_id', $roleId)->delete();
            DB::table('roles')->where('id', $roleId)->delete();
        }
    }
};
