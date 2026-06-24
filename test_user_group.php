<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$u = DB::table('user_management')->where('userName', '14032')->first();
echo "User 14032: " . json_encode($u, JSON_UNESCAPED_UNICODE) . "\n";
