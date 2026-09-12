<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Xác nhận của Lead trên lịch sản xuất (cột stage_plan.comfirm_of_lead).
 *
 * Lead bấm xác nhận ở trang "Lịch Sản Xuất Tuần" để cam kết sẽ chạy đúng lịch mà
 * người sắp lịch đặt ra. Cam kết đó chỉ còn giá trị với đúng khung giờ / phòng đã
 * được xác nhận, nên mỗi khi lịch bị dời (kéo thả trên Gantt, xếp lịch tự động,
 * xoá lịch, khôi phục bản sao lưu...) thì xác nhận phải bị gỡ để Lead xác nhận lại.
 *
 * Quy ước giống cờ stage_plan.submit: đổi lịch là reset về 0.
 */
class LeadConfirmation
{
    /** Chức năng xác nhận của Lead hiện chỉ áp dụng cho PX Viên 1. */
    public const PRODUCTION_CODE = 'PXV1';

    /** Nhóm người dùng được phép bấm xác nhận / bỏ xác nhận. */
    public const USER_GROUPS = ['Leader', 'Admin'];

    /**
     * Xưởng này có dùng chức năng xác nhận của Lead hay không.
     */
    public static function isScope($productionCode): bool
    {
        return $productionCode === self::PRODUCTION_CODE;
    }

    /**
     * Người đang đăng nhập có được bấm xác nhận hay không
     * (các user khác vẫn nhìn thấy dấu tick nhưng ở chế độ chỉ đọc).
     */
    public static function canConfirm($productionCode): bool
    {
        if (! self::isScope($productionCode)) {
            return false;
        }

        return in_array(session('user')['userGroup'] ?? '', self::USER_GROUPS, true);
    }

    /**
     * Gỡ xác nhận của Lead cho các stage_plan vừa bị đổi lịch.
     *
     * Lịch đã hoàn thành thì giữ nguyên dấu xác nhận để còn truy vết được ai đã
     * cam kết thực hiện; chỉ gỡ với lịch chưa chạy.
     *
     * @param  mixed  $ids  1 id, mảng id, hoặc collection id (chấp nhận mảng lồng)
     * @return int  Số dòng bị gỡ xác nhận
     */
    public static function reset($ids): int
    {
        $ids = collect($ids)
            ->flatten()
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            return 0;
        }

        return DB::table('stage_plan')
            ->whereIn('id', $ids)
            ->where('comfirm_of_lead', 1)
            ->where('finished', 0)
            ->update([
                'comfirm_of_lead' => 0,
                'comfirm_of_lead_by' => null,
                'comfirm_of_lead_at' => null,
            ]);
    }
}
