<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Ngày nhận bao bì của lịch Đóng Gói (stage_code = 7).
 *
 * Bản dùng chung của syncPackagingDate() đang bị chép ở SchedualController,
 * AutoSchedualController và ReceivePackagingController.
 */
class PackagingDate
{
    /**
     * Ngày nhận bao bì = ngày liền trước ngày bắt đầu đóng gói, lùi qua các ngày nghỉ.
     *
     * @param  array<int, string>  $offDays  Danh sách ngày nghỉ dạng Y-m-d
     */
    public static function receiveDateFor($start, array $offDays): string
    {
        $date = Carbon::parse($start)->subDay();

        while (in_array($date->toDateString(), $offDays, true)) {
            $date->subDay();
        }

        return $date->toDateString();
    }

    /**
     * Ghi phiên bản mới vào packaging_issuance_date nếu ngày nhận bao bì thay đổi.
     *
     * @param  int  $type  0 = bao bì cấp 1, 1 = bao bì cấp 2
     */
    public static function sync($stagePlanId, $date, int $type, ?string $updateType = null): void
    {
        $plan = DB::table('stage_plan')->where('id', $stagePlanId)->first(['received', 'received_second_packaging']);

        if ($plan) {
            if ($type == 0 && $plan->received == 1) {
                return;
            }
            if ($type == 1 && $plan->received_second_packaging == 1) {
                return;
            }
        }

        $latest = DB::table('packaging_issuance_date')
            ->where('stage_plane_id', $stagePlanId)
            ->where('type_packaging', $type)
            ->orderBy('ver', 'desc')
            ->first();

        $latestDateStr = ($latest && $latest->receive_packaging_date) ? Carbon::parse($latest->receive_packaging_date)->format('Y-m-d') : null;
        $newDateStr = $date ? Carbon::parse($date)->format('Y-m-d') : null;

        if (! $latest || $latestDateStr !== $newDateStr) {
            DB::table('packaging_issuance_date')->insert([
                'stage_plane_id' => $stagePlanId,
                'type_packaging' => $type,
                'receive_packaging_date' => $date,
                'ver' => ($latest->ver ?? 0) + 1,
                'type' => $updateType,
                'created_at' => now(),
                'created_by' => session('user')['fullName'] ?? 'System',
            ]);
        }
    }
}
