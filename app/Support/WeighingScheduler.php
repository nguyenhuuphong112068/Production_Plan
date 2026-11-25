<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Nhóm quyền "Weighing Scheduler" - người sắp lịch trung tâm cân.
 *
 * Nhóm quyền CHÍNH (user_management.userGroup) là Weighing Scheduler thì trên Lịch Sản Xuất Chart chỉ được sắp lịch
 * công đoạn cân (stage_code 1 Cân NL, 2 Cân NL khác), không được đổi lịch các công đoạn khác, và Submit chỉ submit
 * lịch cân. Điều kiện phân xưởng giống Schedualer: phân xưởng đang xem = phòng ban của tài khoản.
 *
 * Chặn 2 lớp: RestrictWeighingScheduler (route nào được gọi) + guard trong từng hàm ghi (id nào được sửa).
 * Tên role so sánh cứng như 'Admin' / 'Schedualer' - đổi tên role ở /User/role thì phải sửa ROLE (và FullCalender.jsx).
 */
final class WeighingScheduler
{
    public const ROLE = 'Weighing Scheduler';

    /** stage_code lớn nhất của công đoạn cân */
    public const MAX_STAGE = 2;

    public const DENIED = '❌ Nhóm quyền Weighing Scheduler chỉ được sắp lịch công đoạn cân';

    public static function active(): bool
    {
        return (session('user')['userGroup'] ?? null) === self::ROLE;
    }

    public static function isWeighingStage($stageCode): bool
    {
        return is_numeric($stageCode) && (int) $stageCode >= 1 && (int) $stageCode <= self::MAX_STAGE;
    }

    /**
     * Các id stage_plan không phải công đoạn cân trong danh sách (id không tồn tại cũng tính là sai).
     * Nhận id dạng số hoặc chuỗi "12,13" / "12-cleaning" như Gantt gửi lên.
     */
    public static function foreignIds(iterable $rawIds): array
    {
        $ids = [];
        foreach ($rawIds as $raw) {
            foreach (explode(',', explode('-', (string) $raw)[0]) as $id) {
                if (is_numeric(trim($id))) {
                    $ids[] = (int) trim($id);
                }
            }
        }
        $ids = array_values(array_unique($ids));
        if (! $ids) {
            return [];
        }

        $weighing = DB::table('stage_plan')
            ->whereIn('id', $ids)
            ->whereBetween('stage_code', [1, self::MAX_STAGE])
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();

        return array_values(array_diff($ids, $weighing));
    }

    /** true nếu người đăng nhập là Weighing Scheduler và danh sách có lịch ngoài công đoạn cân */
    public static function blocks(iterable $rawIds): bool
    {
        return self::active() && self::foreignIds($rawIds) !== [];
    }

    public static function deny(?string $message = null)
    {
        return response()->json(['message' => $message ?? self::DENIED, 'error' => $message ?? self::DENIED], 403);
    }
}
