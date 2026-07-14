<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    Illuminate\Support\Facades\DB::statement('ALTER TABLE plan_master_history ADD COLUMN expired_packing_date DATE NULL, ADD COLUMN parkaging_before_date DATE NULL;');
    echo "Columns added successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
