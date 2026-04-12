<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "Checking connection cal2...\n";
    echo "Checking PDP-177 quotas...\n";
    $quotas = DB::table('quota_maintenance')->where('inst_id', 'PDP-177')->get();
    $out = "";
    foreach ($quotas as $q) {
        $rooms = DB::table('quota_maintenance_rooms')->where('quota_maintenance_id', $q->id)->get();
        $out .= "Quota ID: {$q->id}, Dept: {$q->deparment_code}, Active: {$q->active}, Block: {$q->block}, Rooms count: " . $rooms->count() . "\n";
    }
    file_put_contents('result_pdp.txt', $out);
    echo "Done.\n";
} catch (\Exception $e) {
    echo "Error on cal2: " . $e->getMessage() . "\n";
}
