<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Starting optimized migration...\n";

$map = [];
foreach (['cal1', 'cal2'] as $conn) {
    echo "Loading data from {$conn}...\n";
    foreach ([1, 2, 3] as $suffix) {
        $type = ($suffix == 1) ? 'HC' : (($suffix == 2) ? 'BT' : 'TI');
        $block = ($conn === 'cal1') ? 'B1' : 'B2';
        $ids = DB::connection($conn)->table("Inst_Master_{$suffix}")->pluck('Inst_id')->toArray();
        foreach ($ids as $id) {
            $map[trim($id) . '_' . $block] = "{$type}-{$block}";
        }
    }
}

echo "Processing quota_maintenance...\n";
$quotas = DB::table('quota_maintenance')->whereIn('block', ['B1', 'B2'])->get();
$count = 0;
foreach ($quotas as $q) {
    $key = trim($q->inst_id) . '_' . $q->block;
    if (isset($map[$key])) {
        DB::table('quota_maintenance')->where('id', $q->id)->update(['block' => $map[$key]]);
        $count++;
    }
}

echo "Done! Updated {$count} records.\n";
