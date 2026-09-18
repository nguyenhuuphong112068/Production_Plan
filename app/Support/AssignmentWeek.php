<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Tiện ích dùng chung cho các trang Lịch công tác theo tuần
 * (sản xuất và bảo trì/hiệu chuẩn).
 */
class AssignmentWeek
{
    /**
     * Job_description được nhập bằng contenteditable nên nội dung là HTML
     * (<div>, <br>, &nbsp;, thậm chí cả bảng dán từ Excel). Tách thành từng
     * dòng công việc dạng text thuần để hiển thị trên bảng tuần.
     */
    public static function jobLines($html): array
    {
        $text = (string) $html;

        // Ô trong bảng dán từ Excel: ngăn cách bằng khoảng trắng, không xuống dòng
        $text = preg_replace('#<\s*/\s*(td|th)\s*>#i', ' ', $text);

        // Mở hoặc đóng một block đều là xuống dòng (kể cả <div style="...">)
        $text = preg_replace('#<\s*/?\s*(div|p|li|ul|ol|table|tbody|thead|tr|br)\b[^>]*>#i', "\n", $text);

        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xC2\xA0", ' ', $text);

        $lines = preg_split('/\r\n|\r|\n/', $text);

        return array_values(array_filter(array_map(function ($line) {
            return trim(preg_replace('/[ \t]+/', ' ', $line));
        }, $lines), function ($line) {
            return $line !== '';
        }));
    }

    /**
     * Số giờ công giữa hai mốc thời gian.
     * Ca 1, 2, 3, 6 chạy liên tục; các ca còn lại (HC) bị trừ 45 phút nghỉ trưa.
     */
    public static function hours($start, $end, $shift = null): ?float
    {
        if (!$start || !$end) return null;

        $s = Carbon::parse($start);
        $e = Carbon::parse($end);
        if ($e < $s) $e = $e->copy()->addDay();

        $minutes = $s->diffInMinutes($e);

        if (!in_array((string) $shift, ['1', '2', '3', '6'], true)) {
            $lunchStart = $s->copy()->startOfDay()->addMinutes(11 * 60 + 30);
            $lunchEnd = $s->copy()->startOfDay()->addMinutes(12 * 60 + 15);

            $overlapStart = $s->greaterThan($lunchStart) ? $s : $lunchStart;
            $overlapEnd = $e->lessThan($lunchEnd) ? $e : $lunchEnd;
            if ($overlapStart < $overlapEnd) {
                $minutes -= $overlapStart->diffInMinutes($overlapEnd);
            }
        }

        return round(max(0, $minutes) / 60, 2);
    }
}
