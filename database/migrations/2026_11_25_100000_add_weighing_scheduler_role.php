<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Nhóm quyền "Weighing Scheduler" - sắp lịch trung tâm cân (App\Support\WeighingScheduler::ROLE).
 * Nhóm quyền chính là Weighing Scheduler thì trên Lịch Sản Xuất Chart chỉ sửa / xoá / Submit lịch công đoạn cân.
 * Quyền cấp sẵn: tải lô chờ sắp + các màn hình xem; Admin chỉnh thêm ở /User/role.
 * Server: database/sql/2026_11_25_weighing_scheduler_role.sql
 */
return new class extends Migration
{
    private const ROLE = [
        'name' => 'Weighing Scheduler',
        'display_name' => 'Người Lập Lịch Cân',
        'description' => 'Sắp lịch trung tâm cân: trên Lịch Sản Xuất Chart chỉ sắp lịch và Submit công đoạn cân, không đổi lịch các công đoạn khác',
    ];

    private const PERMISSIONS = [
        'loading_plan_waiting',
        'layout_history',
        'layout_quarantine',
        'layout_warning',
        'layout_wip_coverage',
        'layout_daily_report',
        'layout_weekly_report',
        'schedual_wip_rows',
        'user_guide_view',
    ];

    public function up(): void
    {
        $now = Carbon::now();

        DB::table('roles')->updateOrInsert(
            ['name' => self::ROLE['name']],
            self::ROLE + ['active' => 1, 'created_at' => $now, 'updated_at' => $now]
        );

        $roleId = DB::table('roles')->where('name', self::ROLE['name'])->value('id');
        foreach (DB::table('permissions')->whereIn('name', self::PERMISSIONS)->pluck('id') as $permissionId) {
            DB::table('role_permission')->updateOrInsert(['role_id' => $roleId, 'permission_id' => $permissionId], []);
        }
    }

    public function down(): void
    {
        // Chỉ xóa role khi chưa gán cho ai
        $roleId = DB::table('roles')->where('name', self::ROLE['name'])->value('id');
        if ($roleId && !DB::table('user_role')->where('role_id', $roleId)->exists()) {
            DB::table('role_permission')->where('role_id', $roleId)->delete();
            DB::table('roles')->where('id', $roleId)->delete();
        }
    }
};
