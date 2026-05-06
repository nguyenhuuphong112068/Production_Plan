<?php

use Illuminate\Support\Facades\DB;

try {
    DB::beginTransaction();

    echo "Updating is_main status based on workshop hierarchy...\n";

    // 1. Lấy danh sách các cặp (nhân viên, phân xưởng) đang là chính (is_main = 1 ở cấp workshop)
    $mainWorkshops = DB::table('employee_assignments')
        ->where('is_main', 1)
        ->where('group_id', 0)
        ->where('room_id', 0)
        ->select('employees_id', 'production_code')
        ->get();

    // 2. Với mỗi nhân viên, cập nhật is_main = 1 cho tất cả bản ghi (Tổ, Phòng) thuộc Phân xưởng chính đó
    foreach ($mainWorkshops as $mw) {
        DB::table('employee_assignments')
            ->where('employees_id', $mw->employees_id)
            ->where('production_code', $mw->production_code)
            ->update(['is_main' => 1]);
    }

    // 3. Đảm bảo các bản ghi thuộc Phân xưởng tạm thời thì is_main = 0
    // Tìm các phân xưởng tạm thời (is_main = 0 ở cấp workshop)
    $tempWorkshops = DB::table('employee_assignments')
        ->where('is_main', 0)
        ->where('group_id', 0)
        ->where('room_id', 0)
        ->select('employees_id', 'production_code')
        ->get();

    foreach ($tempWorkshops as $tw) {
        DB::table('employee_assignments')
            ->where('employees_id', $tw->employees_id)
            ->where('production_code', $tw->production_code)
            ->update(['is_main' => 0]);
    }

    DB::commit();
    echo "Successfully updated is_main status.\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
