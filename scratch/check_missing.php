<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$missing = ['Lê Văn Lìl', 'Lê Cao Lương', 'Vương Quốc Chương', 'Võ Thanh Sang', 'Nguyễn Trường Sơn', 'Nguyễn Tấn Phát', 'Nguyễn Hồng Hiếu', 'Lê Minh Tân', 'Nguyễn Thái Hưng'];

foreach ($missing as $name) {
    // Search by partial name
    $parts = explode(' ', $name);
    $lastName = end($parts);
    $matches = DB::table('employees')->where('name', 'like', "%$lastName%")->get();
    echo "Searching for '$name':\n";
    foreach ($matches as $m) {
        echo "  - ID: {$m->id}, Code: {$m->code}, Name: {$m->name}\n";
    }
    echo "\n";
}
