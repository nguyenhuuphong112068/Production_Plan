<?php
$empId = "19289";

function getVal($val) {
    if (is_array($val)) return $val['shift'] ?? 'N/A';
    return $val ?: 'N/A';
}

function checkMonth($m) {
    global $empId;
    $data = file_get_contents("http://s-webdev:5070/api/shifts/by-department?month=$m&year=2026&department=15");
    $shifts = json_decode($data, true);
    foreach ($shifts as $person) {
        if (($person['employeeId'] ?? $person['code']) == $empId) {
            $d21 = getVal($person['days']['day21'] ?? null);
            $d22 = getVal($person['days']['day22'] ?? null);
            $d1 = getVal($person['days']['day1'] ?? null);
            echo "Month $m: Day1=$d1, Day21=$d21, Day22=$d22\n";
        }
    }
}

checkMonth(5);
checkMonth(6);
checkMonth(7);
checkMonth(8);
