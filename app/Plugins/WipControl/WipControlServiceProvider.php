<?php

namespace App\Plugins\WipControl;

use Illuminate\Support\ServiceProvider;

/**
 * Plugin Kiểm soát tồn bán thành phẩm khi sắp lịch tự động.
 *
 * Sau khi "Sắp lịch tự động" chạy xong, plugin đo tồn chờ Định hình / Bao phim /
 * Đóng gói theo lịch vừa sắp; nhóm nào vượt Max người dùng khai báo thì lùi đầu
 * nguồn (cả chuỗi lô từ Pha chế) của các lô có ngày cần hàng muộn nhất rồi sắp
 * lại, lặp tối đa N vòng.
 *
 * Gỡ plugin: bỏ dòng đăng ký provider này trong bootstrap/providers.php. Lõi sắp
 * lịch chỉ còn hook extraEarliestStart() trả về null nên chạy y như trước.
 */
class WipControlServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/wip_control.php', 'wip_control');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        if (! config('wip_control.enabled')) {
            return;
        }

        $this->loadRoutesFrom(__DIR__ . '/routes.php');
    }
}
