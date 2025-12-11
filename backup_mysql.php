<?php
// ==============================
// Cấu hình thông tin kết nối
// ==============================
$host = 'localhost';
$user = 'root';
$pass = 'Stell@123';
$db   = 'PMS';

// ==============================
// Đường dẫn tool và thư mục backup
// ==============================
$mysqldump = '/usr/bin/mysqldump';   // QUAN TRỌNG: đường dẫn tuyệt đối
$backupDir = '/var/backups/mysql/';
$logFile   = $backupDir . 'backup.log';

// Tạo thư mục nếu chưa có
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0777, true);
}

// ==============================
// Tên file backup
// ==============================
$date = date('Y-m-d_H-i-s');
$backupFile = $backupDir . "{$db}_{$date}.sql";

// ==============================
// Gọi mysqldump + ghi log lỗi
// ==============================
$command = "$mysqldump --user='{$user}' --password='{$pass}' --host='{$host}' {$db} > {$backupFile} 2>> {$logFile}";

exec($command, $output, $return_var);

// ==============================
// Ghi log kết quả
// ==============================
$logMessage = date('Y-m-d H:i:s') . " - Return code: {$return_var}\n";
file_put_contents($logFile, $logMessage, FILE_APPEND);

if ($return_var === 0) {
    echo "Backup thành công: {$backupFile}\n";
} else {
    echo "Lỗi khi backup DB! Xem log: {$logFile}\n";
}

// ==============================
// Xóa file cũ hơn 7 ngày
// ==============================
$files = glob($backupDir . '*.sql');
$now = time();

foreach ($files as $file) {
    if (is_file($file) && ($now - filemtime($file)) > 7 * 24 * 60 * 60) {
        unlink($file);
    }
}
?>
