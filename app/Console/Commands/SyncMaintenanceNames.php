<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncMaintenanceNames extends Command
{
    protected $signature = 'sync:maintenance-names';
    protected $description = 'Sync Eqp_name from external DBs to quota_maintenance table';

    public function handle()
    {
        $this->info("Starting global maintenance name sync...");

        $conns = ['cal1', 'cal2'];
        $suffixes = [1, 2, 3]; // HC, BT, TI
        
        $updatedCount = 0;

        foreach ($conns as $conn) {
            foreach ($suffixes as $suffix) {
                try {
                    $this->info("Checking connection $conn, suffix $suffix...");
                    
                    // Lấy dữ liệu từ DB ngoại vi
                    $externalData = DB::connection($conn)
                        ->table("Inst_Master_{$suffix} as Ins")
                        ->leftJoin("Eqp_mst_{$suffix} as Eqp", 'Eqp.Eqp_ID', '=', 'Ins.Parent_Equip_id')
                        ->select('Ins.Inst_id', 'Eqp.Eqp_name', 'Ins.Inst_Name')
                        ->get();

                    $typePrefix = ($suffix == 1) ? 'HC' : (($suffix == 2) ? 'BT' : 'TI');

                    foreach ($externalData as $item) {
                        $name = $item->Eqp_name ?? $item->Inst_Name;
                        if (!$name) continue;

                        $rows = DB::table('quota_maintenance')
                            ->where('inst_id', $item->Inst_id)
                            ->where('block', 'like', "$typePrefix-%")
                            ->update(['Eqp_name' => $name]);
                        
                        if ($rows > 0) $updatedCount += $rows;
                    }
                } catch (\Exception $e) {
                    $this->error("Error on $conn/$suffix: " . $e->getMessage());
                }
            }
        }

        $this->info("Sync completed! Total records updated: $updatedCount");
    }
}
