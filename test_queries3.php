<?php
$production_code = 'PXV1';
$availableGroups = DB::table('employee_assignments as ea')
    ->join('employees as e', 'ea.employees_id', '=', 'e.id')
    ->where('e.active', 1)
    ->where(function ($q) {
        $q->whereNull('e.resign')->orWhere('e.resign', 0);
    })
    ->where('ea.production_code', $production_code)
    ->where('ea.active', 1)
    ->whereNotNull('ea.group_id')
    ->join('stage_groups as sg', 'ea.group_id', '=', 'sg.code')
    ->select('sg.code', 'sg.name', DB::raw('count(DISTINCT e.id) as personnel_count'))
    ->groupBy('sg.code', 'sg.name')
    ->get();
dump($availableGroups);
