<?php
$production_code = 'PXV1';
$group_id = 5;

$q1 = DB::table('employees as e')
    ->where('e.active', 1)
    ->whereExists(function ($q) use ($production_code) {
        $q->select(DB::raw(1))
            ->from('employee_assignments as ea')
            ->whereColumn('ea.employees_id', 'e.id')
            ->where('ea.production_code', $production_code)
            ->where('ea.active', 1);
    })
    ->whereExists(function ($q) use ($group_id) {
        $q->select(DB::raw(1))
            ->from('employee_assignments')
            ->whereColumn('employees_id', 'e.id')
            ->where('group_id', $group_id);
    })->count();

$q2 = DB::table('employees as e')
    ->where('e.active', 1)
    ->join('employee_assignments as ea', 'e.id', '=', 'ea.employees_id')
    ->where('ea.production_code', $production_code)
    ->where('ea.active', 1)
    ->where('ea.group_id', $group_id)
    ->distinct('e.id')
    ->count('e.id');

dump('PersonnelController count: ' . $q1);
dump('DashBoardController count: ' . $q2);
