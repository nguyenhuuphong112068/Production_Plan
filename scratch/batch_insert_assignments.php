<?php

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Pages\Assignment\PersonnelController;

// Employee IDs from the user's image
$employeeIds = [
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


// Room IDs matching the criteria: group_code = 3 and stage_code <= 7
$roomIds = DB::table('room')->where('group_code', 3)->where('stage_code', '<=', 7)->pluck('id')->toArray();

// Map codes to actual IDs

$employeeMapping = DB::table('employees')->whereIn('code', $employeeIds)->pluck('id', 'code')->toArray();

$inserts = [];
foreach ($employeeMapping as $code => $empId) {

    foreach ($roomIds as $roomId) {
        $exists = DB::table('employee_assignments')
            ->where('employees_id', $empId)
            ->where('room_id', $roomId)
            ->where('production_code', 'PXV1')
            ->exists();
            
        if (!$exists) {
            $inserts[] = [
                'employees_id' => $empId,
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
}


if (!empty($inserts)) {
    DB::table('employee_assignments')->insert($inserts);
    echo "Inserted " . count($inserts) . " assignments.\n";
} else {
    echo "No assignments to insert.\n";
}

// Recalculate room counts to sync the dashboard
try {
    $controller = new PersonnelController();
    if (method_exists($controller, 'recalculateRoomCounts')) {
         // We need to call it via reflection if it's protected
         $reflection = new ReflectionMethod($controller, 'recalculateRoomCounts');
         $reflection->setAccessible(true);
         $reflection->invoke($controller, 'PXV1');

         echo "Room counts recalculated successfully.\n";
    }
} catch (\Exception $e) {
    echo "Warning: Could not automatically recalculate room counts: " . $e->getMessage() . "\n";
}
