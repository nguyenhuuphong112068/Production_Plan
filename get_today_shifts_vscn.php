<?php
$url = "http://s-webdev:5070/api/shifts/by-department?month=7&year=2026&department=15";
$data = file_get_contents($url);
$shifts = json_decode($data, true);

$codes = ["17016","19013","19197","19215","19289","19409","19491","19492","19708","19713","19733","19757","19892","19904","19948","23066","23067","23068","23121","19773","19836","19930","23049","23115"];
$day = 22; // hôm nay là ngày 22
$dayKey = 'day' . $day;

$result = [];
foreach ($shifts as $person) {
    if (in_array($person['employeeId'] ?? $person['code'], $codes)) {
        $shift = $person['days'][$dayKey] ?? 'Unknown';
        if (is_array($shift)) {
            $shift = $shift['shift'] ?? 'Unknown';
        }
        $result[] = [
            'Name' => $person['employeeName'] ?? 'Unknown',
            'Code' => $person['employeeId'] ?? $person['code'],
            'Shift' => $shift
        ];
    }
}

// Thêm department 17 (Kho)
$url17 = "http://s-webdev:5070/api/shifts/by-department?month=7&year=2026&department=17";
$data17 = file_get_contents($url17);
$shifts17 = json_decode($data17, true);
foreach ($shifts17 as $person) {
    if (in_array($person['employeeId'] ?? $person['code'], $codes)) {
        $shift = $person['days'][$dayKey] ?? 'Unknown';
        if (is_array($shift)) {
            $shift = $shift['shift'] ?? 'Unknown';
        }
        $result[] = [
            'Name' => $person['employeeName'] ?? 'Unknown',
            'Code' => $person['employeeId'] ?? $person['code'],
            'Shift' => $shift
        ];
    }
}


foreach ($result as $item) {
    echo $item['Code'] . " - " . $item['Name'] . ": " . $item['Shift'] . "\n";
}
