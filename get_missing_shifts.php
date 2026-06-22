<?php
$url34 = "http://s-webdev:5070/api/shifts/by-department?month=7&year=2026&department=34";
$data34 = file_get_contents($url34);
$shifts34 = json_decode($data34, true);

$unknownCodes = ["19197", "19409", "19708", "19733", "19773", "19836"];
$dayKey = 'day22';

foreach ($shifts34 as $person) {
    if (in_array($person['employeeId'] ?? $person['code'], $unknownCodes)) {
        $shift = $person['days'][$dayKey] ?? 'Unknown';
        if (is_array($shift)) {
            $shift = $shift['shift'] ?? 'Unknown';
        }
        echo ($person['employeeId'] ?? $person['code']) . " - " . ($person['employeeName'] ?? 'Unknown') . ": " . $shift . "\n";
    }
}
