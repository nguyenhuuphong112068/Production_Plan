<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';
$app = app();
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$q = DB::table('plan_master')->where('id', function($q) {
    $q->select('plan_master_id')->from('stage_plan')->where('id', 47909);
})->first();

echo json_encode($q, JSON_PRETTY_PRINT);
