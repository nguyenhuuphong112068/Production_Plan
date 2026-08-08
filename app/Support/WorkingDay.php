<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Ngày công tác chạy từ 06:00 hôm nay đến 06:00 hôm sau.
 *
 * Trục timeline của giao diện phân công đã lấy mốc 06:00 (xem timeToOffset()
 * trong resources/views/pages/assignment/production/dataTable.blade.php), nên
 * backend phải quy ước giống hệt: một công tác thuộc ngày công tác D khi giờ
 * bắt đầu của nó nằm trong [D 06:00, D+1 06:00).
 *
 * Trước đây trang Lịch công tác lọc bằng whereDate('start', D) còn Dashboard
 * lọc theo khoảng giao nhau với [D 06:00, D+1 06:00), khiến các bản ghi có giờ
 * bắt đầu trước 06:00 được quy về hai ngày khác nhau ở hai trang.
 */
class WorkingDay
{
    /** Giờ mở đầu một ngày công tác */
    public const START_HOUR = 6;

    /** Mốc bắt đầu ngày công tác D */
    public static function start($date): Carbon
    {
        return Carbon::parse($date)->startOfDay()->setTime(self::START_HOUR, 0, 0);
    }

    /** Mốc kết thúc ngày công tác D (= mốc bắt đầu của D+1) */
    public static function end($date): Carbon
    {
        return self::start($date)->addDay();
    }

    /**
     * Ngày công tác chứa một thời điểm bất kỳ.
     * VD: 08/08 00:00 thuộc ngày công tác 07/08.
     */
    public static function of($dateTime): string
    {
        return Carbon::parse($dateTime)->subHours(self::START_HOUR)->format('Y-m-d');
    }

    /**
     * Ghép ngày công tác D với giờ trên giao diện thành datetime thực tế.
     * Giờ nhỏ hơn 06:00 là phần rạng sáng nên rơi vào ngày lịch kế tiếp.
     */
    public static function toDateTime($date, $time): string
    {
        $dt = Carbon::parse(Carbon::parse($date)->format('Y-m-d') . ' ' . $time);

        if ($dt->hour < self::START_HOUR) {
            $dt->addDay();
        }

        return $dt->format('Y-m-d H:i:s');
    }

    /**
     * Giới hạn truy vấn về đúng một ngày công tác, dựa trên cột giờ bắt đầu.
     */
    public static function scope($query, $column, $date)
    {
        return $query->where($column, '>=', self::start($date))
            ->where($column, '<', self::end($date));
    }
}
