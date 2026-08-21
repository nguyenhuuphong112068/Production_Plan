<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tách quyền cập nhật Thông Báo Đóng Gói thành hai quyền riêng.
     *
     * Cột "Số PO" do bộ phận kế hoạch/đơn hàng nhập, các cột lấy mẫu do bộ phận khác nhập,
     * nên một quyền chung không đủ. Quyền cũ packaging_notification_update được thay thế:
     * ai đang có quyền cũ sẽ được cấp cả hai quyền mới để không ai mất quyền sau khi chạy.
     */
    private const PERMISSION_GROUP = 15;

    private const OLD_PERMISSION = 'packaging_notification_update';

    private const PERMISSIONS = [
        [
            'name' => 'packaging_notification_update_po',
            'display_name' => 'Nhập Số PO Thông Báo Đóng Gói',
            'description' => 'Nhập / sửa cột Số PO của thông báo đóng gói',
        ],
        [
            'name' => 'packaging_notification_update_sampling',
            'display_name' => 'Nhập Thông Tin Lấy Mẫu Thông Báo Đóng Gói',
            'description' => 'Nhập / sửa quy cách, số lần, số lượng, đơn vị lấy mẫu và lý do',
        ],
    ];

    /** Nhóm quyền được cấp sẵn khi cài đặt, dùng cho hệ thống chưa từng có quyền cũ */
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

        $newIds = DB::table('permissions')
            ->whereIn('name', array_column(self::PERMISSIONS, 'name'))
            ->pluck('id');

        $oldId = DB::table('permissions')->where('name', self::OLD_PERMISSION)->value('id');

        if ($oldId) {
            // Chuyển nguyên trạng phân quyền cũ sang cả hai quyền mới, giữ cả cờ is_denied
            $roleIds = DB::table('role_permission')->where('permission_id', $oldId)->pluck('role_id');

            foreach ($roleIds as $roleId) {
                foreach ($newIds as $newId) {
                    DB::table('role_permission')->updateOrInsert(
                        ['role_id' => $roleId, 'permission_id' => $newId],
                        []
                    );
                }
            }

            $userRows = DB::table('user_permission')->where('permission_id', $oldId)->get();

            foreach ($userRows as $row) {
                foreach ($newIds as $newId) {
                    DB::table('user_permission')->updateOrInsert(
                        ['user_id' => $row->user_id, 'permission_id' => $newId],
                        ['is_denied' => $row->is_denied]
                    );
                }
            }

            DB::table('role_permission')->where('permission_id', $oldId)->delete();
            DB::table('user_permission')->where('permission_id', $oldId)->delete();
            DB::table('permissions')->where('id', $oldId)->delete();
        } else {
            // Cài mới hoàn toàn: không có quyền cũ để kế thừa nên cấp cho Admin
            $roleIds = DB::table('roles')->whereIn('name', self::GRANT_TO_ROLES)->pluck('id');

            foreach ($roleIds as $roleId) {
                foreach ($newIds as $newId) {
                    DB::table('role_permission')->updateOrInsert(
                        ['role_id' => $roleId, 'permission_id' => $newId],
                        []
                    );
                }
            }
        }
    }

    public function down(): void
    {
        $now = Carbon::now();

        DB::table('permissions')->updateOrInsert(
            ['name' => self::OLD_PERMISSION],
            [
                'display_name' => 'Cập Nhật Thông Báo Đóng Gói',
                'description' => 'Nhập / sửa số PO và thông tin lấy mẫu của thông báo đóng gói',
                'permission_group' => self::PERMISSION_GROUP,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        $oldId = DB::table('permissions')->where('name', self::OLD_PERMISSION)->value('id');

        $newIds = DB::table('permissions')
            ->whereIn('name', array_column(self::PERMISSIONS, 'name'))
            ->pluck('id');

        // Gộp ngược: ai có bất kỳ quyền mới nào thì nhận lại quyền chung
        $roleIds = DB::table('role_permission')->whereIn('permission_id', $newIds)->distinct()->pluck('role_id');

        foreach ($roleIds as $roleId) {
            DB::table('role_permission')->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $oldId],
                []
            );
        }

        $userIds = DB::table('user_permission')->whereIn('permission_id', $newIds)->distinct()->pluck('user_id');

        foreach ($userIds as $userId) {
            DB::table('user_permission')->updateOrInsert(
                ['user_id' => $userId, 'permission_id' => $oldId],
                []
            );
        }

        DB::table('role_permission')->whereIn('permission_id', $newIds)->delete();
        DB::table('user_permission')->whereIn('permission_id', $newIds)->delete();
        DB::table('permissions')->whereIn('id', $newIds)->delete();
    }
};
