<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Công tắc "Xác nhận và điều chỉnh lịch theo thời gian thực" theo phân xưởng (bảng schedule_reroute_settings).
 *
 * Chỉ user được phép (ScheduleRerouteService::canUse) mới bật/tắt; khi đang bật thì xác nhận vệ sinh của lô
 * do bất kỳ ai thực hiện ở phân xưởng đó đều tịnh tuyến lịch lý thuyết.
 */
class RealtimeRerouteSwitch
{
    public static function get(?string $deparmentCode): ?object
    {
        if (!$deparmentCode) {
            return null;
        }

        return DB::table('schedule_reroute_settings')->where('deparment_code', $deparmentCode)->first();
    }

    public static function enabled(?string $deparmentCode): bool
    {
        return (bool) (self::get($deparmentCode)->realtime_reroute ?? false);
    }

    public static function set(string $deparmentCode, bool $enabled, ?string $updatedBy): void
    {
        $now = now();

        DB::table('schedule_reroute_settings')->insertOrIgnore([
            'deparment_code' => $deparmentCode,
            'created_at'     => $now,
        ]);

        DB::table('schedule_reroute_settings')
            ->where('deparment_code', $deparmentCode)
            ->update([
                'realtime_reroute' => $enabled,
                'updated_by'       => $updatedBy,
                'updated_at'       => $now,
            ]);
    }

    /**
     * Mô tả lần đổi gần nhất, vd. "Nguyễn Văn A lúc 08:30 26/09/2026"; null nếu chưa ai đổi.
     */
    public static function lastChange(?object $setting): ?string
    {
        if (!$setting || !$setting->updated_at) {
            return null;
        }

        return ($setting->updated_by ?: '?') . ' lúc ' . Carbon::parse($setting->updated_at)->format('H:i d/m/Y');
    }
}
