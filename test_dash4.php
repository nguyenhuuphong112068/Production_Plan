<?php
$assignments = DB::table('assignments as a')->where('a.deparment_code', 'PXV1')->whereDate('a.created_at', '2026-06-25')->get();
print_r($assignments->toArray());

