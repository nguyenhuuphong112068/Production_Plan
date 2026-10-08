<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Database\Dblib\DblibServiceProvider::class,
    // Plugin Kiểm soát tồn BTP khi sắp lịch tự động; bỏ dòng này là gỡ plugin
    App\Plugins\WipControl\WipControlServiceProvider::class,
    // Thử nghiệm dòng con Tồn chờ vào phòng trên Gantt; bỏ dòng này là gỡ plugin
    App\Plugins\RoomWip\RoomWipServiceProvider::class,
];
