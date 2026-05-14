<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $stageGroup = DB::table('stage_groups')->where('code', 18)->first();
    $groupId = $stageGroup ? $stageGroup->id : 17; // Default to 17 based on previous check

    $employees = [
        ['name' => 'Lý Kim Phong', 'code' => '16166'],
        ['name' => 'Trần Hữu Toản', 'code' => '16172'],
        ['name' => 'Nguyễn Thế Bình', 'code' => '16189'],
        ['name' => 'Nguyễn Trung Kiên', 'code' => '16143'],
        ['name' => 'Phan Văn Công Thành', 'code' => '15150'],
        ['name' => 'Trần Thanh Triều', 'code' => '16118'],
        ['name' => 'Bùi Minh Tân', 'code' => '19014'],
    ];

    foreach ($employees as $empData) {
        // 1. Đảm bảo nhân sự tồn tại trong employees
        $employee = DB::table('employees')->where('code', $empData['code'])->first();
        $employeeId = null;

        if (!$employee) {
            $employeeId = DB::table('employees')->insertGetId([
                'code' => $empData['code'],
                'name' => $empData['name'],
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            echo "Inserted employee: {$empData['name']}\n";
        } else {
            $employeeId = $employee->id;
            DB::table('employees')->where('id', $employeeId)->update([
                'name' => $empData['name'],
                'active' => 1,
                'updated_at' => now()
            ]);
            echo "Updated employee: {$empData['name']}\n";
        }

        // 2. Đảm bảo phân công QA tồn tại
        $hasAssignment = DB::table('employee_assignments')
            ->where('employees_id', $employeeId)
            ->where('production_code', 'QA')
            ->exists();

        if (!$hasAssignment) {
            DB::table('employee_assignments')->insert([
                'employees_id' => $employeeId,
                'production_code' => 'QA',
                'group_id' => $groupId,
                'is_main' => 1,
                'room_id' => 0,
                'active' => 1,
                'created_by' => 'Manual Add',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            echo "Created QA assignment for: {$empData['name']}\n";
        } else {
            DB::table('employee_assignments')
                ->where('employees_id', $employeeId)
                ->where('production_code', 'QA')
                ->update([
                    'group_id' => $groupId,
                    'active' => 1,
                    'updated_at' => now()
                ]);
            echo "Updated QA assignment for: {$empData['name']}\n";
        }
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
