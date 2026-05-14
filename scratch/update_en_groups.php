<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    // 1. Đảm bảo Tổ Tiện Ích tồn tại
    $utilityGroup = DB::table('stage_groups')->where('name', 'Tổ Tiện Ích')->first();
    $utilityId = $utilityGroup ? $utilityGroup->id : DB::table('stage_groups')->insertGetId([
        'code' => 17,
        'name' => 'Tổ Tiện Ích',
        'type' => 2,
        'create_by' => 'Antigravity',
        'created_at' => now(),
        'updated_at' => now()
    ]);

    // 2. Đảm bảo Nhóm KTBT-Kho 6 tồn tại
    $kho6Group = DB::table('stage_groups')->where('name', 'Nhóm KTBT-Kho 6')->first();
    $kho6Id = $kho6Group ? $kho6Group->id : DB::table('stage_groups')->insertGetId([
        'code' => 18,
        'name' => 'Nhóm KTBT-Kho 6',
        'type' => 2,
        'create_by' => 'Antigravity',
        'created_at' => now(),
        'updated_at' => now()
    ]);

    $groups = [
        'utility' => $utilityId,
        'maintenance_b1' => 8,
        'hvac_b1' => 9,
        'pw_b1' => 10,
        'hvac_pw_b2' => 11,
        'maintenance_pxv2_pxdn' => 12,
        'maintenance_pxvh' => 13,
        'kho6' => $kho6Id,
    ];

    $mapping = [
        // Tổ Tiện Ích
        'utility' => [
            'Nguyễn Hữu Lữ', 'Mạc Thọ Long', 'Nguyễn Đức Lộc', 'Lê Văn Tài', 'Lê Duy Tân', 'Phạm Đăng Long', 
            'Bùi Xuân Hậu', 'Nguyễn Thanh Sơn', 'Cao Thanh Việt', 'Mạc Văn Tăng', 'Lê Quốc Hội', 
            'Nguyễn Văn Dương', 'Nguyễn Văn Bình', 'Nguyễn Thành An', 'Lê Văn  Lil', 'Huỳnh Quốc Tiến'
        ],
        // Tổ Bảo trì (B1)
        'maintenance_b1' => [
            'Huỳnh Văn Kha', 'Lê Quang Nhàn', 'Phạm Trọng Kiền', 'Võ Đàng', 'Nguyễn Phú Quí', 'Phạm Văn Sớm', 
            'Trần Sót', 'Lê Viết Hạnh', 'Nguyễn Minh Tuấn', 'Trần Quốc Trí', 'Phạm Thanh Trường', 'Trần Văn Thành', 
            'Nguyễn Việt Hùng', 'Nguyễn Tuấn Anh', 'Huỳnh Quốc Thái', 'Lê Quang Nhân', 'Lê Cao Lương', 
            'Nguyễn Thành Hiếu', 'Lâm Thế Vũ', 'Hoàng Tuấn Vũ', 'Nguyễn Trung Tính', 'Trần Vũ Lợi', 
            'Nguyễn Phạm Ngọc Triều', 'Nguyễn Văn Sơn', 'Huỳnh Nhựt Lâm', 'Nguyễn Văn Long', 'Cao Trung Kiên', 
            'Trịnh Quang Thành', 'Đoàn Văn Hoài', 'Đoàn Văn Tính'
        ],
        // Tổ Điện lạnh (B1)
        'hvac_b1' => [
            'Lê Nguyễn Minh Hải', 'Huỳnh Văn Phong', 'Lê Trung Hiếu', 'Vương Quốc Chương', 'Nguyễn Phương Diện', 
            'Phạm Tài Thuấn', 'Lê Phúc Hậu', 'Vũ Văn Thắng', 'Võ Thanh Sang', 'Nguyễn Trường Sơn', 
            'Cao Thanh Nhân', 'Phan Trấn Dương', 'Nguyễn Tuấn Anh', 'Huỳnh Long Bá Phúc', 'Nguyễn Thanh Tùng', 'Trần Trường Long'
        ],
        // Tổ hệ thống nước tinh khiết (B1)
        'pw_b1' => [
            'Trương Minh Toàn', 'Đặng Quang Xuân Thiên', 'Nguyễn Ngọc Thọ'
        ],
        // Tổ Điện lạnh - Nước tinh khiết (B2)
        'hvac_pw_b2' => [
            'Nguyễn Hoàng Sơn', 'Nguyễn Hùng Trường', 'Võ Hoài Phong', 'Đỗ Văn Nguyên', 'Nguyễn Thanh Phương', 
            'Nguyễn Hoài Thanh', 'Phan Thái Thịnh', 'Lê Nguyễn Bá Duy', 'Dương Hải Đông', 'Nguyễn Tấn Phát', 
            'Lê Văn Nhật', 'Bùi Thương Thương', 'Nguyễn Khắc Phục', 'Nguyễn Văn Phát'
        ],
        // Tổ Bảo trì (PXV2-PXDN)
        'maintenance_pxv2_pxdn' => [
            'Nguyễn Hồng Hiếu', 'Nguyễn Trọng Nhân', 'Lưu Ngọc Anh', 'Ngô Văn Thận', 'Nguyễn Tiến Dũng', 'Nguyễn Thanh Triệu'
        ],
        // Tổ Bảo trì (PXVH)
        'maintenance_pxvh' => [
            'Vũ Mạnh Chính', 'Nguyễn Minh Chính', 'Lê Minh Tân', 'Huỳnh Trọng Huy', 'Nguyễn Thái Hưng'
        ],
        // Nhóm KTBT-Kho 6
        'kho6' => [
            'Phạm Hữu Vĩnh', 'Nguyễn Thanh Chương'
        ]
    ];

    $updatedCount = 0;
    foreach ($mapping as $groupKey => $names) {
        $groupId = $groups[$groupKey];
        foreach ($names as $name) {
            // Tìm nhân viên theo tên (ưu tiên khớp chính xác hoặc khớp có dấu cách)
            $employee = DB::table('employees')->where('name', $name)->first();
            
            if ($employee) {
                // Cập nhật group_id trong employee_assignments cho EN
                $affected = DB::table('employee_assignments')
                    ->where('employees_id', $employee->id)
                    ->where('production_code', 'EN')
                    ->update(['group_id' => $groupId, 'updated_at' => now()]);
                
                if ($affected > 0) {
                    $updatedCount++;
                } else {
                    echo "Info: Employee '$name' found but no EN assignment updated.\n";
                }
            } else {
                echo "Warning: Employee '$name' not found in database.\n";
            }
        }
    }

    echo "Successfully updated group_id for $updatedCount employees in EN department.\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
