<?php
require __DIR__."/vendor/autoload.php";
use Carbon\Carbon;
$start = Carbon::parse("2026-06-27 15:15:00");
$bStart = Carbon::parse("2026-06-29 09:15:00");
$gap = $start->diffInMinutes($bStart);
$need = 2400;
$offDateList = [
    ["start" => Carbon::parse("2026-06-28 00:00:00"), "end" => Carbon::parse("2026-06-28 23:59:59")]
];
$offTime = 0;
do {
    $current_end = $start->copy()->addMinutes($need + $offTime);
    $newOffTime = 0;
    foreach ($offDateList as $off) {
        if ($off["end"] <= $start || $off["start"] >= $current_end) continue;
        $overlapStart = $off["start"]->greaterThan($start) ? $off["start"] : $start;
        $overlapEnd = $off["end"]->lessThan($current_end) ? $off["end"] : $current_end;
        $newOffTime += $overlapStart->diffInMinutes($overlapEnd);
    }
    $changed = ($newOffTime > $offTime);
    $offTime = $newOffTime;
} while ($changed);
echo "gap: $gap, need: $need, offTime: $offTime\n";
if ($gap >= $need + $offTime) echo "FOUND GAP\n";
else echo "NO GAP\n";
