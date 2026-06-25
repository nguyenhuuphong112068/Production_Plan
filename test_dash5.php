<?php
$assignments = DB::table('assignments as a')->join('assignment_personnel as ap', 'a.id', '=', 'ap.assignment_id')->join('employees as e', 'e.id', '=', 'ap.personnel_id')->where('e.code', '19389')->get();
print_r($assignments->toArray());

