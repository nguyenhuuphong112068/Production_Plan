<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Cùng nhóm với layout_finised / return_event - trang Thực Thi Sản Xuất thay trang Xác Nhận Hoàn Thành */
    private const PERMISSION_GROUP = 6;

    private const PERMISSIONS = [
        [
            'name' => 'layout_production_execution',
            'display_name' => 'Trang Thực Thi Sản Xuất',
            'description' => 'Vào trang Thực Thi Sản Xuất, xem bảng trạng thái phòng và lịch sử phòng của phân xưởng mình',
        ],
        [
            'name' => 'execution_production',
            'display_name' => 'Thực Thi SX: Mở Phòng - Bắt Đầu - Kết Thúc',
            'description' => 'Chọn lô mở phòng, bắt đầu / tạm dừng / tiếp tục / kết thúc sản xuất và ghi hoạt động phòng',
        ],
        [
            'name' => 'execution_cleaning',
            'display_name' => 'Thực Thi SX: Thực Hiện Vệ Sinh',
            'description' => 'Bắt đầu và kết thúc vệ sinh phòng (VS-I, VS-II, VS-III)',
        ],
        [
            'name' => 'execution_clean_check',
            'display_name' => 'Thực Thi SX: Kiểm Tra Vệ Sinh',
            'description' => 'Ký kết quả kiểm tra vệ sinh Đạt / Không đạt. Quyền xét trên tài khoản người kiểm tra nhập lại, không phải phiên đang đăng nhập',
        ],
        [
            'name' => 'execution_mark_dirty',
            'display_name' => 'Thực Thi SX: Chuyển Phòng Sang Cần Vệ Sinh',
            'description' => 'Chuyển phòng đang sạch sang Cần Vệ Sinh kèm lý do (sau bảo trì, sự cố, hết hạn phòng sạch)',
        ],
        [
            'name' => 'execution_undo',
            'display_name' => 'Thực Thi SX: Hoàn Tác',
            'description' => 'Hoàn tác thao tác vừa ghi trên phòng - xoá mốc thời gian đã lưu và trả phòng về trạng thái trước đó',
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
