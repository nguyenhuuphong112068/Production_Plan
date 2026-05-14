<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$enEmployees = DB::table('employee_assignments as ea')
    ->join('employees as e', 'ea.employees_id', '=', 'e.id')
    ->where('ea.production_code', 'EN')
    ->select('e.id', 'e.name', 'e.code')
    ->get();

echo json_encode($enEmployees, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
