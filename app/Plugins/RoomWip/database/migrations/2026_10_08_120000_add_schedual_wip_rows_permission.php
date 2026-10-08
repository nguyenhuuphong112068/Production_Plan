<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Quyền thấy nút "Tồn BTP" trên Gantt /Schedual (plugin RoomWip): bật dòng tổng tồn
 * chờ của công đoạn và dòng con tồn chờ vào từng phòng. Kiểm tra ở RoomWipController.
 */
return new class extends Migration
{
    /** Nhóm Lịch Sản Xuất - Thực Thi (RoleController::GROUPS) */
    private const PERMISSION_GROUP = 6;

    private const PERMISSION = [
        'name' => 'schedual_wip_rows',
        'display_name' => 'Lịch SX: Xem Tồn BTP Trên Gantt',
        'description' => 'Thấy nút [Tồn BTP] trên Lịch Sản Xuất: dòng tổng tồn chờ từng công đoạn và tồn chờ vào từng phòng (slot 1 ngày)',
    ];

    /** Cấp sẵn cho nhóm sắp lịch; nhóm khác tự tick ở trang phân quyền */
    private const GRANT_TO_ROLES = ['Admin', 'Schedualer'];

    public function up(): void
    {
        $now = Carbon::now();

        DB::table('permissions')->updateOrInsert(
            ['name' => self::PERMISSION['name']],
            self::PERMISSION + ['permission_group' => self::PERMISSION_GROUP, 'created_at' => $now, 'updated_at' => $now]
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
