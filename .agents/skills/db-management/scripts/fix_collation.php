<?php
/**
 * Script sửa lỗi collation cho file SQL lớn
 * Cách dùng: php fix_collation.php [file_nguon.sql] [file_dich.sql]
 */

if ($argc < 3) {
    die("Cách dùng: php fix_collation.php [file_nguon.sql] [file_dich.sql]\n");
}

$inputFile = $argv[1];
$outputFile = $argv[2];

if (!file_exists($inputFile)) {
    die("Lỗi: Không tìm thấy file nguồn $inputFile\n");
}

$handle = fopen($inputFile, 'r');
$newHandle = fopen($outputFile, 'w');

echo "Đang xử lý: $inputFile -> $outputFile...\n";

if ($handle && $newHandle) {
    $count = 0;
    while (($line = fgets($handle)) !== false) {
        // Thay thế các collation không tương thích
        $line = str_replace('utf8mb4_0900_ai_ci', 'utf8mb4_unicode_ci', $line);
        $line = str_replace('utf8mb4_general_ci', 'utf8mb4_unicode_ci', $line);
        
        fwrite($newHandle, $line);
        
        if (++$count % 10000 == 0) {
            echo "Đã xử lý $count dòng...\n";
        }
    }
    fclose($handle);
    fclose($newHandle);
    echo "Hoàn tất! File đã được sửa lưu tại: $outputFile\n";
} else {
    echo "Lỗi: Không thể mở file để đọc/ghi.\n";
}
