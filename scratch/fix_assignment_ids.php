<?php
ob_start();


use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Pages\Assignment\PersonnelController;

// 1. Delete the incorrect records
$deleted = DB::table('employee_assignments')
    ->where('created_by', 'System Manual Insert')
    ->delete();

echo "Deleted $deleted incorrect assignments.\n";

// 2. Define the employee codes (Full list from both requests)
$allCodes = [
    // List 1
    19173, 19552, 19151, 19386, 19479, 19567, 19587, 19716, 19822, 19826, 19828, 19866,
    // List 2
    19011, 19034, 19487, 19287, 19501, 19910, 
    19016, 19044, 19047, 19049, 19143, 19167, 19191, 19242, 19248, 
    19332, 19347, 19351, 19374, 19376, 19380, 19381, 19382, 19383, 
    19481, 19480, 19490, 19496, 19534, 19538, 19539, 19555, 19556, 
    19571, 19620, 19622, 19630, 19631, 19149, 19687, 19692, 19693, 
    19720, 19727, 19735, 19747, 19753, 19766, 19781, 19785, 
    19830, 19827, 19846, 19848, 19876, 19871, 19872, 19873, 19874, 19875, 19878, 
    19159, 19559, 19842, 19885, 19886, 19888, 19891, 19894, 19895, 19896, 19898, 19899, 
    19900, 19901, 19906, 19907
];

// 3. Map codes to IDs
$mapping = DB::table('employees')
    ->whereIn('code', $allCodes)
    ->pluck('id', 'code')
    ->toArray();

echo "Mapped " . count($mapping) . " codes to IDs.\n";

// 4. Define target rooms
// Group 1 rooms
$group1RoomIds = DB::table('room')->where('group_code', 1)->where('stage_code', '<=', 7)->pluck('id')->toArray();
// Group 3 rooms
$group3RoomIds = DB::table('room')->where('group_code', 3)->where('stage_code', '<=', 7)->pluck('id')->toArray();

$inserts = [];
foreach ($mapping as $code => $id) {
    // Insert for Group 1
    foreach ($group1RoomIds as $roomId) {
        $inserts[] = [
            'employees_id' => $id,
            'production_code' => 'PXV1',
            'is_main' => 1,
            'group_id' => 1,
            'room_id' => $roomId,
            'level' => 1,
            'active' => 1,
            'created_by' => 'System Manual Insert',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
    // Insert for Group 3
    foreach ($group3RoomIds as $roomId) {
        $inserts[] = [
            'employees_id' => $id,
            'production_code' => 'PXV1',
            'is_main' => 1,
            'group_id' => 3,
            'room_id' => $roomId,
            'level' => 1,
            'active' => 1,
            'created_by' => 'System Manual Insert',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

// 5. Perform batch insert
if (!empty($inserts)) {
    // Using chunks to avoid large query errors
    foreach (array_chunk($inserts, 500) as $chunk) {
        DB::table('employee_assignments')->insert($chunk);
    }
    echo "Inserted " . count($inserts) . " correct assignments.\n";
    
    // 6. Recalculate room counts
    /*
    try {
        $controller = new PersonnelController();
        if (method_exists($controller, 'recalculateRoomCounts')) {
             $reflection = new ReflectionMethod($controller, 'recalculateRoomCounts');
             $reflection->setAccessible(true);
             $reflection->invoke($controller, 'PXV1');
             echo "Room counts recalculated successfully.\n";
        }
    } catch (\Exception $e) {
        echo "Warning: Could not automatically recalculate room counts: " . $e->getMessage() . "\n";
    }
    */

}

$output = ob_get_clean();
file_put_contents('scratch/fix_log.txt', $output);
echo $output;
