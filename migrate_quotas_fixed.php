<?php
use Illuminate\Support\Facades\DB;
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Starting chunked migration (avoiding SQL Server limit)...\n";

$connections = ['cal1', 'cal2'];
foreach ($connections as $conn) {
    $blockSuffix = ($conn === 'cal1') ? 'B1' : 'B2';
    foreach ([1, 2, 3] as $suffix) {
        $typePrefix = ($suffix == 1) ? 'HC' : (($suffix == 2) ? 'BT' : 'TI');
        $newBlock = "{$typePrefix}-{$blockSuffix}";
        
        echo "Processing {$newBlock}...\n";
        
        try {
            $sourceIds = DB::connection($conn)->table("Inst_Master_{$suffix}")->pluck('Inst_id')->toArray();
            $sourceIds = array_map('trim', $sourceIds);
            
            if (empty($sourceIds)) continue;

            // Chunk the IDs to avoid 2100 parameters limit in SQL Server
            $chunks = array_chunk($sourceIds, 1000);
            $totalAffected = 0;
            foreach ($chunks as $chunk) {
                $affected = DB::table('quota_maintenance')
                    ->whereIn('block', ['B1', 'B2'])
                    ->whereIn('inst_id', $chunk)
                    ->update(['block' => $newBlock]);
                $totalAffected += $affected;
            }
                
            echo "  Updated {$totalAffected} records to {$newBlock}.\n";
        } catch (\Exception $e) {
            echo "  Error processing {$newBlock}: " . $e->getMessage() . "\n";
        }
    }
}
echo "Migration completed.\n";
