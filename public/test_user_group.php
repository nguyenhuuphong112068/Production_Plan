<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$user = DB::table('user_management')->where('employee_id', 14032)->orWhere('username', '14032')->first();
echo "User: " . json_encode($user, JSON_UNESCAPED_UNICODE) . "\n";
