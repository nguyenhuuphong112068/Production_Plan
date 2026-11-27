<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Khóa tịnh tuyến lịch theo phân xưởng (khóa tên của MySQL / MariaDB: GET_LOCK), để các lần tịnh tuyến chạy lần lượt.
 *
 * Tịnh tuyến đọc lịch → tính → ghi đè giờ mới; 2 lần chạy song song (2 phòng Nhận / Trả phòng cùng lúc) đọc cùng lịch cũ
 * nên lần ghi sau đè mất phần dời của lần trước, và nhật ký "giờ cũ" sai làm Hoàn tác khôi phục sai. Lần sau phải chờ
 * lần trước xong rồi mới đọc lịch. Khóa gắn với kết nối DB của request: request chết thì MySQL tự nhả khóa.
 * Gọi lồng nhau trong cùng request (Trả phòng giữ khóa → ScheduleRerouteService::run) không chờ lại.
 */
class RerouteLock
{
    const WAIT_SECONDS = 60;

    /** @var array<string, int> phân xưởng => số lớp đang giữ trong request này */
    private static array $held = [];

    /**
     * Chạy $fn khi đang giữ khóa tịnh tuyến của phân xưởng. Chờ quá WAIT_SECONDS thì ném RerouteBusyException, $fn không chạy.
     * $waitSeconds = 0: không chờ, khóa đang bị giữ (vd. đang Nhận / Trả phòng) thì ném ngay (lệnh quét lô quá giờ).
     */
    public static function run(?string $deparmentCode, callable $fn, ?int $waitSeconds = null)
    {
        $wait = $waitSeconds ?? self::WAIT_SECONDS;
        $key = $deparmentCode ?: '_';

        if (!empty(self::$held[$key])) {
            self::$held[$key]++;
            try {
                return $fn();
            } finally {
                self::$held[$key]--;
            }
        }

        $name = 'pms_reroute:' . $key;
        // Windows tính giờ chờ vào max_execution_time: chừa đủ cho lúc chờ khóa + thời gian chạy
        @set_time_limit($wait + 300);

        if ((int) DB::selectOne('SELECT GET_LOCK(?, ?) AS l', [$name, $wait])->l !== 1) {
            throw new RerouteBusyException('⏳ Phân xưởng ' . $key . ' đang có thao tác khác tịnh tuyến lịch'
                . ($wait > 0 ? ' quá ' . $wait . ' giây' : '') . '. Thao tác của bạn CHƯA được lưu, vui lòng thực hiện lại.');
        }

        self::$held[$key] = 1;
        try {
            return $fn();
        } finally {
            unset(self::$held[$key]);
            DB::selectOne('SELECT RELEASE_LOCK(?) AS l', [$name]);
        }
    }
}
