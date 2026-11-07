<?php
/*
 * Xoá các phiên đăng nhập do mint.php tạo (bảng sessions), chạy sau mỗi lần chụp.
 *
 *   php cleanup.php
 */
require __DIR__.'/../../../../vendor/autoload.php';
$app = require __DIR__.'/../../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$file = storage_path('app/userGuideShots/sessions.txt');
$ids = is_file($file) ? array_filter(array_map('trim', file($file))) : [];
$deleted = $ids ? Illuminate\Support\Facades\DB::table(config('session.table', 'sessions'))->whereIn('id', $ids)->delete() : 0;
@unlink($file);
echo "Đã xoá $deleted phiên chụp ảnh\n";
