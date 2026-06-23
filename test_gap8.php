<?php
require __DIR__."/vendor/autoload.php";
use Carbon\Carbon;
$offEnd = Carbon::parse("2026-06-28 23:59:59");
$current_start = Carbon::parse("2026-06-27 15:15:00");
$offStart = Carbon::parse("2026-06-28 00:00:00");
$current_end = Carbon::parse("2026-06-29 09:15:00");
echo "offEnd <= current_start: " . ($offEnd <= $current_start ? "true" : "false") . "\n";
echo "offStart >= current_end: " . ($offStart >= $current_end ? "true" : "false") . "\n";
