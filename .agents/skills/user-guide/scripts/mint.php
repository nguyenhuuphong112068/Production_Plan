<?php
/*
 * Tạo một phiên đăng nhập (session database) cho trình duyệt headless chụp màn hình hướng dẫn,
 * KHÔNG qua form đăng nhập: không ghi Lịch Sử Đăng Nhập / Audit Trail, không chạy đồng bộ nhân sự.
 *
 *   php mint.php <userName> [production_code]
 *   -> {"name": "<cookie>", "value": "<giá trị cookie đã mã hoá>", "id": "<session id>"}
 *
 * Session id được ghi thêm vào storage/app/userGuideShots/sessions.txt để cleanup.php xoá sau khi chụp.
 */
require __DIR__.'/../../../../vendor/autoload.php';
$app = require __DIR__.'/../../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$userName = $argv[1] ?? 'Admin';
$productionCode = $argv[2] ?? null;

$user = DB::table('user_management')->where('userName', $userName)->first() ?? exit("Không có tài khoản $userName\n");
$production = DB::table('production')->where('code', $productionCode ?: $user->deparment)->first();

$session = app('session')->driver();
$session->setId(Str::random(40));
$session->start();
// Giống LoginController::login (không lưu mật khẩu: các hộp thoại hỏi mật khẩu không dùng khi chụp)
$session->put('user', [
    'userId' => $user->id,
    'userName' => $user->userName,
    'fullName' => $user->fullName,
    'passWord' => '',
    'userGroup' => $user->userGroup,
    'department' => $user->deparment,
    'group_name' => $user->groupName,
    'production_code' => $production->code ?? 'PXV1',
    'production_name' => $production->name ?? 'PX Viên 1',
]);
$session->save();

@mkdir(storage_path('app/userGuideShots'), 0777, true);
file_put_contents(storage_path('app/userGuideShots/sessions.txt'), $session->getId()."\n", FILE_APPEND);

$name = config('session.cookie');
$encrypter = app('encrypter');
echo json_encode([
    'name' => $name,
    'value' => $encrypter->encrypt(CookieValuePrefix::create($name, $encrypter->getKey()).$session->getId(), false),
    'id' => $session->getId(),
]);
