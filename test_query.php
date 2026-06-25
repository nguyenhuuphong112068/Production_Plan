<?php
$emp = DB::table('employees')->where('id', 9)->first();
if ($emp) {
    $assignments = DB::table('assignments as a')->join('assignment_personnel as ap', 'a.id', '=', 'ap.assignment_id')->where('ap.personnel_id', 9)->orderBy('a.start', 'desc')->limit(5)->get();
    print_r($assignments->toArray());
}

