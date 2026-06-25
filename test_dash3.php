<?php
$assignments = DB::table('assignments as a')->join('assignment_personnel as ap', 'a.id', '=', 'ap.assignment_id')->join('employees as e', 'e.id', '=', 'ap.personnel_id')->whereIn('e.code', ['19389', '19407', '19455'])->where('a.active', 1)->get();
print_r($assignments->toArray());

