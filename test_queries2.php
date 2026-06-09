<?php
$production_code = 'PXV1';
$group_id = 5;

$personnelQuery = DB::table('employees as e')
    ->where('e.active', 1)
    ->where(function ($q) {
        $q->whereNull('e.resign')->orWhere('e.resign', 0);
    })
    ->join('employee_assignments as ea', 'e.id', '=', 'ea.employees_id')
    ->leftJoin('stage_groups as sg', 'ea.group_id', '=', 'sg.code')
    ->where('ea.production_code', $production_code)
    ->where('ea.active', 1);

if ($group_id) {
    $personnelQuery->where('ea.group_id', $group_id);
}

$personnelList = $personnelQuery
    ->select('e.id', 'e.code', 'e.name', DB::raw('GROUP_CONCAT(DISTINCT sg.name SEPARATOR ", ") as group_names'))
    ->groupBy('e.id', 'e.code', 'e.name')
    ->get();

dump('DashBoardController List count: ' . count($personnelList));
