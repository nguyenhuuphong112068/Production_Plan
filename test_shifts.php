<?php
$json = @file_get_contents('http://s-webdev:5070/api/shifts/by-department?month=7&year=2026&department=15');
$data = json_decode($json, true);
$codes = ['19197', '19409', '19708', '19733'];
foreach($data as $person) {
    $empId = isset($person['employeeId']) ? (string)$person['employeeId'] : '';
    if (in_array($empId, $codes)) {
        echo 'Name: ' . $person['employeeName'] . ' (' . $empId . ')' . PHP_EOL;
        echo '  Day 22: ';
        echo json_encode($person['days']['day22'] ?? 'NO DATA');
        echo PHP_EOL;
    }
}
