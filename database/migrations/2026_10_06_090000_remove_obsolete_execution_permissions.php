<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Trang Thực Thi Sản Xuất tinh gọn còn 2 nút Nhận phòng / Trả phòng: các quyền theo nút của bản cũ
 * (mở phòng - bắt đầu - kết thúc, vệ sinh, kiểm tra vệ sinh, chuyển sang cần vệ sinh, hoàn tác) không còn nút nào dùng.
 * Còn giữ layout_production_execution (vào trang Thực Thi) và layout_production_record (vào trang Ghi Nhận).
 */
return new class extends Migration
{
    private const PERMISSION_GROUP = 6;

    private const OBSOLETE = [
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

    public function up(): void
    {
        $ids = DB::table('permissions')->whereIn('name', array_column(self::OBSOLETE, 'name'))->pluck('id');

        DB::transaction(function () use ($ids) {
            DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
            DB::table('user_permission')->whereIn('permission_id', $ids)->delete();
            DB::table('permissions')->whereIn('id', $ids)->delete();
        });
    }

    /** Khôi phục các quyền (cấp lại cho Admin như lúc cài đặt ban đầu) */
    public function down(): void
    {
        $now = Carbon::now();

        foreach (self::OBSOLETE as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name']],
                $permission + ['permission_group' => self::PERMISSION_GROUP, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $permissionIds = DB::table('permissions')->whereIn('name', array_column(self::OBSOLETE, 'name'))->pluck('id');
        foreach (DB::table('roles')->where('name', 'Admin')->pluck('id') as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permission')->updateOrInsert(['role_id' => $roleId, 'permission_id' => $permissionId], []);
            }
        }
    }
};
