<?php
$assignments = DB::table('assignments as a')->join('assignment_personnel as ap', 'a.id', '=', 'ap.assignment_id')->join('employees as e', 'e.id', '=', 'ap.personnel_id')->where('a.created_at', '>', '2026-06-25 07:30:00')->get();
print_r($assignments->toArray());

