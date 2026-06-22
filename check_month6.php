<?php
$data = file_get_contents('http://s-webdev:5070/api/shifts/by-department?month=6&year=2026&department=15');
$shifts = json_decode($data, true);
$codes = ["17016","19013","19197","19215","19289","19409","19491","19492","19708","19713","19733","19757","19892","19904","19948","23066","23067","23068","23121","19773","19836","19930","23049","23115"];
$dayKey = 'day22';
foreach ($shifts as $person) {
    if (in_array($person['employeeId'] ?? $person['code'], $codes)) {
        $shift = $person['days'][$dayKey] ?? 'Unknown';
        if (is_array($shift)) {
            $shift = $shift['shift'] ?? 'Unknown';
        }
        echo ($person['employeeId'] ?? $person['code']) . " - " . ($person['employeeName'] ?? 'Unknown') . ": " . $shift . "\n";
    }
}
