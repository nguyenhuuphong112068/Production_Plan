<?php

use Illuminate\Support\Facades\DB;

// 1. Delete ALL System Manual Insert records to start clean
DB::table('employee_assignments')
    ->where('created_by', 'System Manual Insert')
    ->delete();

// 2. Full list of codes
$allCodes = [
    19173, 19552, 19151, 19386, 19479, 19567, 19587, 19716, 19822, 19826, 19828, 19866,
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
$mapping = DB::table('employees')->whereIn('code', $allCodes)->pluck('id', 'code')->toArray();

// 4. Room IDs
$group1RoomIds = DB::table('room')->where('group_code', 1)->where('stage_code', '<=', 7)->pluck('id')->toArray();
$group3RoomIds = DB::table('room')->where('group_code', 3)->where('stage_code', '<=', 7)->pluck('id')->toArray();

$inserts = [];
foreach ($mapping as $code => $id) {
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

// 5. Insert in chunks
foreach (array_chunk($inserts, 100) as $chunk) {

    DB::table('employee_assignments')->insert($chunk);
    echo "Inserted " . count($chunk) . " records...\n";
}


echo "SUCCESS: Inserted " . count($inserts) . " assignments for " . count($mapping) . " employees.\n";
