<?php

namespace App\Support;

/**
 * Cờ quốc gia của mã thị trường (bảng market.code): nhãn thị trường trên khối Đóng gói ở Gantt và modal Nhận phòng.
 * SVG lấy từ gói flag-icons (MIT), chép sẵn vào public/img/flags (không cần internet; cập nhật: chép lại từ
 * node_modules/flag-icons/flags/4x3). Mã ghép DK-FI / "VN, MY" ra nhiều cờ; mã không phải quốc gia
 * (Tender, ALDE, Asia, Không Áp Dụng...) không có cờ.
 */
class MarketFlag
{
    /** @return string[] URL các cờ theo thứ tự trong mã, bỏ phần không có cờ */
    public static function urls(?string $marketCode): array
    {
        static $cache = [];
        if (!$marketCode) {
            return [];
        }

        return $cache[$marketCode] ??= collect(preg_split('/[-,\s]+/', $marketCode))
            ->filter(fn($part) => preg_match('/^[A-Za-z]{2}$/', $part))
            ->map(fn($part) => strtolower($part))
            ->unique()
            ->filter(fn($iso) => is_file(public_path("img/flags/{$iso}.svg")))
            ->map(fn($iso) => asset("img/flags/{$iso}.svg"))
            ->values()
            ->all();
    }

    /** HTML các thẻ <img> cờ (cho Blade / chuỗi HTML trả về JS) */
    public static function html(?string $marketCode, string $class = 'market-flag'): string
    {
        return collect(self::urls($marketCode))
            ->map(fn($url) => '<img src="' . e($url) . '" alt="" class="' . e($class) . '">')
            ->implode('');
    }
}
