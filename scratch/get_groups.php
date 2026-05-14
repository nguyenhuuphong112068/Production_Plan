<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$groups = DB::table('stage_groups')->get();
echo json_encode($groups, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
