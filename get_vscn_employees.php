<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$codes = DB::table('employee_assignments as ea')
    ->join('employees as e', 'ea.employees_id', '=', 'e.id')
    ->where('ea.group_id', 9)
    ->where('ea.active', 1)
    ->pluck('e.code')
    ->toArray();

$depCodes = DB::table('employee_assignments')
    ->where('group_id', 9)
    ->where('active', 1)
    ->pluck('production_code')
    ->unique()
    ->toArray();

echo json_encode([
    'codes' => $codes,
    'departments' => $depCodes,
], JSON_PRETTY_PRINT);
