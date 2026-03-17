<?php
use Illuminate\Support\Facades\DB;
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Starting per-table migration...\n";

$connections = ['cal1', 'cal2'];
foreach ($connections as $conn) {
    $blockSuffix = ($conn === 'cal1') ? 'B1' : 'B2';
    foreach ([1, 2, 3] as $suffix) {
        $typePrefix = ($suffix == 1) ? 'HC' : (($suffix == 2) ? 'BT' : 'TI');
        $newBlock = "{$typePrefix}-{$blockSuffix}";
        
        echo "Processing {$newBlock} (Table {$suffix} from {$conn})...\n";
        
        // Get all IDs from source table
        $sourceIds = DB::connection($conn)->table("Inst_Master_{$suffix}")->pluck('Inst_id')->toArray();
        $sourceIds = array_map('trim', $sourceIds);
        
        if (empty($sourceIds)) continue;

        // Update records in quota_maintenance that match these IDs and current block B1/B2
        $affected = DB::table('quota_maintenance')
            ->whereIn('block', ['B1', 'B2'])
            ->whereIn('inst_id', $sourceIds)
            ->update(['block' => $newBlock]);
            
        echo "  Updated {$affected} records to {$newBlock}.\n";
    }
}
echo "Migration completed.\n";
