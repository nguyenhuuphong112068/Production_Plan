<?php
require __DIR__."/vendor/autoload.php";
$app = require_once __DIR__."/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// Campaign 7639_1, stage 4 (THT)
$pred_ends = [
    Carbon::parse("2026-06-23 17:45:00"),
    Carbon::parse("2026-06-24 00:15:00"),
    Carbon::parse("2026-06-24 06:45:00"),
    Carbon::parse("2026-06-24 13:15:00"),
    Carbon::parse("2026-06-24 19:45:00"),
    Carbon::parse("2026-06-25 02:15:00"),
    Carbon::parse("2026-06-25 08:45:00"),
    Carbon::parse("2026-06-25 15:15:00"),
];

// Quota for S9, stage 4
$m_time = 240; // 4h
$C1_time = 30; // 30 min
$p_time = 30;  // 30 min
$slot_per_batch = $m_time + $C1_time; // 270 min per batch after first

echo "=== So sanh cac cach tinh earliestStart ===\n\n";

// Cách 1 (hiện tại - SAI): max(pred_end) for all batches
$candidates_wrong = $pred_ends;
$max_wrong = collect($candidates_wrong)->max();
echo "Cach 1 (SAI - max toan bo pred_end): $max_wrong\n";

// Cách 2 (original subMinutes với avg_m_time): pred_end[N] - N*avg_m_time
$candidates_orig = [];
foreach ($pred_ends as $i => $pred_end) {
    $candidates_orig[] = $pred_end->copy()->subMinutes($i * $m_time);
}
$max_orig = collect($candidates_orig)->max();
echo "Cach 2 (subMinutes voi avg_m_time=$m_time): $max_orig\n";

// Cách 3 (đúng): pred_end[N] - p_time - N*(m_time + C1_time)
$candidates_correct = [];
foreach ($pred_ends as $i => $pred_end) {
    if ($i == 0) {
        $candidates_correct[] = $pred_end->copy();
    } else {
        $candidates_correct[] = $pred_end->copy()->subMinutes($p_time + $i * $slot_per_batch);
    }
}
$max_correct = collect($candidates_correct)->max();
echo "Cach 3 (DUNG - subMinutes p_time + N*(m+C1)=$slot_per_batch): $max_correct\n";

// Cách 4 (gần đúng): pred_end[N] - N*(m_time + C1_time)
$candidates_near = [];
foreach ($pred_ends as $i => $pred_end) {
    $candidates_near[] = $pred_end->copy()->subMinutes($i * $slot_per_batch);
}
$max_near = collect($candidates_near)->max();
echo "Cach 4 (gan dung - subMinutes N*(m+C1)=$slot_per_batch): $max_near\n";

echo "\n=== Neu campaign bat dau tu moi cach tinh ===\n";
foreach ([['Sai', $max_wrong], ['Orig', $max_orig], ['Correct', $max_correct], ['Near', $max_near]] as [$label, $t]) {
    echo "\n[$label] Bat dau tu: $t\n";
    $start = $t;
    foreach ($pred_ends as $i => $pred_end) {
        if ($i == 0) {
            $batch_start = $start->copy();
        } else {
            $batch_start = $start->copy()->addMinutes($p_time + $i * $slot_per_batch);
        }
        $ok = $batch_start->gte($pred_end) ? "OK" : "FAIL (pred_end=$pred_end)";
        echo "  Lo $i bat dau: $batch_start $ok\n";
    }
}
