<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "pms");
$mysqli->set_charset("utf8mb4");

echo "=== user_management ===\n";
$res = $mysqli->query("SELECT id, userName, fullName, deparment FROM user_management LIMIT 20");
if ($res) {
    while($r = $res->fetch_assoc()) {
        echo $r['id'] . " | " . $r['userName'] . " | " . $r['fullName'] . " | " . $r['deparment'] . "\n";
    }
}

echo "\n=== production ===\n";
$res2 = $mysqli->query("SELECT id, code, name FROM production");
if ($res2) {
    while($r = $res2->fetch_assoc()) {
        echo $r['id'] . " | " . $r['code'] . " | " . $r['name'] . "\n";
    }
}

echo "\n=== COLLATION user_management ===\n";
$res3 = $mysqli->query("SELECT COLUMN_NAME, CHARACTER_SET_NAME, COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='pms' AND TABLE_NAME='user_management' AND DATA_TYPE IN ('varchar','text')");
while($r = $res3->fetch_assoc()) {
    echo $r['COLUMN_NAME'] . " -> " . $r['CHARACTER_SET_NAME'] . "/" . $r['COLLATION_NAME'] . "\n";
}
