<?php
namespace App\Http\Controllers\Pages\Plan;
use Illuminate\Support\Facades\DB;

$effectiveStageCode = 7;
$scheduledCounts = DB::table('stage_plan')
    ->where('stage_code', $effectiveStageCode)
    ->where(function($query) {
        $query->whereNotNull('actual_start')
              ->orWhereNotNull('schedualed_at');
    })
    ->select('required_room_code', DB::raw('COUNT(*) as scheduled_count'))
    ->groupBy('required_room_code')
    ->pluck('scheduled_count', 'required_room_code')
    ->toArray();

var_dump($scheduledCounts['S48A'] ?? null);
var_dump($scheduledCounts['S48B'] ?? null);