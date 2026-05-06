<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/../../../../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../../../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$userName = 'Random_Assignment';

// 1. Lấy danh sách nhân viên và Tổ tương ứng
$employeeGroups = DB::table('employee_groups as eg')
    ->join('stage_groups as sg', 'eg.group_id', '=', 'sg.id')
    ->select('eg.employees_id', 'sg.code as group_code')
    ->where('eg.active', 1)
    ->get();

$count = 0;
$skipped = 0;

// 1.5 Xóa các bản ghi đã gán ngẫu nhiên trước đó để thực hiện lại
DB::table('employee_rooms')->where('created_by', $userName)->delete();

foreach ($employeeGroups as $eg) {
    // 1.7 Lấy danh sách các phân xưởng nhân viên được phép công tác
    $allowedProductions = DB::table('employee_productions')
        ->where('employees_id', $eg->employees_id)
        ->where('active', 1)
        ->pluck('production_code')
        ->toArray();

    if (empty($allowedProductions)) continue;

    // 2. Tìm tất cả các phòng thuộc Tổ này, thỏa mãn stage_code và thuộc phân xưởng cho phép
    $rooms = DB::table('room')
        ->where('group_code', $eg->group_code)
        ->where('active', 1)
        ->where('stage_code', '<=', 7)
        ->whereIn('deparment_code', $allowedProductions)
        ->pluck('id')
        ->toArray();

    if (empty($rooms)) continue;

    // 3. Chọn ngẫu nhiên tối đa 3 phòng
    $countToPick = min(3, count($rooms));
    $pickedRoomKeys = array_rand($rooms, $countToPick);
    
    // array_rand returns a single key if picking 1, or an array of keys if picking > 1
    if (!is_array($pickedRoomKeys)) {
        $pickedRoomKeys = [$pickedRoomKeys];
    }

    foreach ($pickedRoomKeys as $key) {
        $roomId = $rooms[$key];

        // 4. Kiểm tra xem đã tồn tại chưa
        $exists = DB::table('employee_rooms')
            ->where('employees_id', $eg->employees_id)
            ->where('room_id', $roomId)
            ->exists();

        if (!$exists) {
            DB::table('employee_rooms')->insert([
                'employees_id' => $eg->employees_id,
                'room_id' => $roomId,
                'level' => 4,
                'active' => 1,
                'created_by' => $userName,
                'created_at' => now()
            ]);
            $count++;
        } else {
            $skipped++;
        }
    }
}

echo "Random assignment completed.\n";
echo "Inserted: $count records.\n";
echo "Skipped (already exists): $skipped records.\n";
