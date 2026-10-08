<?php

namespace App\Plugins\RoomWip;

use Illuminate\Support\ServiceProvider;

/**
 * Plugin THỬ NGHIỆM: dòng con "Tồn chờ vào" dưới mỗi phòng ĐH/BP/ĐG trên Gantt
 * (slot 1 ngày), ghi tồn lúc 06:00 từng ngày; bấm số thì làm sáng lô nguồn và
 * lô sẽ tiêu thụ (frontend: resources/js/Plugins/RoomWip).
 *
 * Gỡ plugin:
 *   0. php artisan migrate:rollback --path=app/Plugins/RoomWip/database/migrations/2026_10_08_120000_add_schedual_wip_rows_permission.php
 *      (xoá quyền schedual_wip_rows; server: xoá tay theo database/sql/2026_10_08_schedual_wip_rows_permission.sql)
 *   1. Bỏ dòng đăng ký provider này trong bootstrap/providers.php
 *   2. Xoá app/Plugins/RoomWip và resources/js/Plugins/RoomWip
 *   3. Bỏ các dòng có chú thích "RoomWip" trong resources/js/Pages/FullCalender.jsx
 *   (2 trường source_id / splits.id trong WipCoverageService để lại cũng không sao)
 */
class RoomWipServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/routes.php');
    }
}
