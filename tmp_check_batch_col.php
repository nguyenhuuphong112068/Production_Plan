<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

foreach (['plan_master', 'plan_master_history'] as $table) {
    $column = DB::select("SHOW COLUMNS FROM `$table` LIKE 'batch'");
    if ($column) {
        echo "Table $table Column batch: " . $column[0]->Type . "\n";
    } else {
        echo "Table $table Column batch NOT FOUND\n";
    }
}
