<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Starting migration of quota_maintenance data...\n";

$quotas = DB::table('quota_maintenance')->whereIn('block', ['B1', 'B2'])->get();
$total = count($quotas);
$updated = 0;

foreach ($quotas as $q) {
    $blockSuffix = $q->block; // B1 or B2
    $connection = ($blockSuffix == 'B1') ? 'cal1' : 'cal2';
    
    $found = false;
    foreach ([1, 2, 3] as $suffix) {
        if (DB::connection($connection)->table("Inst_Master_{$suffix}")->where('Inst_id', $q->inst_id)->exists()) {
            $typePrefix = ($suffix == 1) ? 'HC' : (($suffix == 2) ? 'BT' : 'TI');
            $newBlock = "{$typePrefix}-{$blockSuffix}";
            
            DB::table('quota_maintenance')->where('id', $q->id)->update(['block' => $newBlock]);
            echo "Updated ID {$q->id} [{$q->inst_id}]: {$blockSuffix} -> {$newBlock}\n";
            $updated++;
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        echo "Warning: Could not find instrument {$q->inst_id} in any table for block {$blockSuffix}\n";
    }
}

echo "Migration finished. Total checked: {$total}, Updated: {$updated}\n";
