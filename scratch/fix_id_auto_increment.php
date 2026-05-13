<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;

try {
    echo "Attempting to add AUTO_INCREMENT to assignments.id...\n";
    DB::statement('ALTER TABLE assignments MODIFY COLUMN id BIGINT UNSIGNED AUTO_INCREMENT');
    echo "Success!\n";
    
    echo "New schema for assignments:\n";
    print_r(DB::select('DESCRIBE assignments'));
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
