<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Lịch nghỉ của công ty (bảng off_days), dùng để loại ngày nghỉ ra khỏi các phép
 * đếm "số ngày làm việc".
 *
 * Bảng này đã liệt kê đủ mọi Chủ nhật trong năm, cộng thêm các thứ Bảy nghỉ luân
 * phiên và các đợt nghỉ lễ / nghỉ công ty. Vẫn giữ quy ước "Chủ nhật luôn là ngày
 * nghỉ" bên cạnh bảng này để những năm chưa được nhập lịch (trước 2026 bảng gần
 * như trống) không bị đếm nhầm cả 7 ngày trong tuần.
 *
 * Nạp một lần cho mỗi request rồi giữ trong bộ nhớ: mỗi năm chỉ khoảng 90 dòng,
 * trong khi trang theo dõi hồ sơ KCS gọi tới hàng trăm lần khi tính lại cả lưới.
 */
class OffDays
{
    /** @var array<string, true>|null  Ngày nghỉ dạng ['Y-m-d' => true], null là chưa nạp */
    private static ?array $dates = null;

    /**
     * Toàn bộ ngày nghỉ theo lịch công ty.
     *
     * @return array<string, true>
     */
    public static function all(): array
    {
        if (self::$dates !== null) {
            return self::$dates;
        }

        self::$dates = DB::table('off_days')
            ->pluck('off_date')
            ->mapWithKeys(fn($date) => [substr((string) $date, 0, 10) => true])
            ->all();

        return self::$dates;
    }

    /** Ngày này có nghỉ không (Chủ nhật hoặc có trong lịch nghỉ công ty) */
    public static function isRestDay(Carbon $date): bool
    {
        return $date->dayOfWeek === Carbon::SUNDAY
            || isset(self::all()[$date->format('Y-m-d')]);
    }

    /**
     * Xoá bộ nhớ đệm, dùng khi lịch nghỉ vừa thay đổi trong cùng một tiến trình
     * (lệnh backfill chạy dài, test...).
     */
    public static function flush(): void
    {
        self::$dates = null;
    }
}
