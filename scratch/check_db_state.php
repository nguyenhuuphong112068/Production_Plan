<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;

$groups = DB::table('groups')->get();
echo "Groups:\n";
foreach ($groups as $group) {
    echo "ID: {$group->id}, Name: {$group->name}\n";
}

$assignments = DB::table('employee_assignments')->limit(5)->get();
echo "\nAssignments Sample:\n";
print_r($assignments->toArray());

$employees = DB::table('employees')->whereIn('code', ['5058', '5234'])->get();
echo "\nEmployees Sample:\n";
print_r($employees->toArray());
