<?php
// Fix encoding cho user_management và production
// Dùng PDO với charset latin1 để đọc raw bytes, sau đó encode lại đúng utf8mb4

$pdo_latin = new PDO("mysql:host=127.0.0.1;dbname=pms;charset=latin1", "root", "", [
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES latin1"
]);

$pdo_utf8 = new PDO("mysql:host=127.0.0.1;dbname=pms;charset=utf8mb4", "root", "", [
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
]);

function fixEncoding($str) {
    if (empty($str)) return $str;
    // Nếu string đã là UTF-8 hợp lệ thì không cần fix
    if (mb_detect_encoding($str, 'UTF-8', true)) {
        // Nhưng có thể là double-encoded - thử convert
        $decoded = utf8_decode($str);
        if (mb_detect_encoding($decoded, 'UTF-8', true)) {
            return $decoded;
        }
    }
    return $str;
}

// === Fix user_management ===
echo "=== Fixing user_management ===\n";
$stmt = $pdo_latin->query("SELECT id, fullName, deparment, groupName FROM user_management");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$update = $pdo_utf8->prepare("UPDATE user_management SET fullName=?, groupName=? WHERE id=?");

foreach ($rows as $r) {
    $fixedName = mb_convert_encoding($r['fullName'], 'UTF-8', 'latin1');
    $fixedGroup = mb_convert_encoding($r['groupName'] ?? '', 'UTF-8', 'latin1');
    
    echo "ID {$r['id']}: {$r['fullName']} -> $fixedName\n";
    $update->execute([$fixedName, $fixedGroup, $r['id']]);
}

// === Fix production ===
echo "\n=== Fixing production ===\n";
$stmt2 = $pdo_latin->query("SELECT id, name FROM production");
$rows2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

$update2 = $pdo_utf8->prepare("UPDATE production SET name=? WHERE id=?");

foreach ($rows2 as $r) {
    $fixedName = mb_convert_encoding($r['name'], 'UTF-8', 'latin1');
    echo "ID {$r['id']}: {$r['name']} -> $fixedName\n";
    $update2->execute([$fixedName, $r['id']]);
}

echo "\n✅ DONE! Vui lòng đăng xuất và đăng nhập lại để thấy tên đúng.\n";
