<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$tables = ['notifications', 'notification_recipients'];
$results = [];
foreach ($tables as $table) {
    if (Schema::hasTable($table)) {
        $columns = Schema::getColumnListing($table);
        $details = [];
        foreach ($columns as $column) {
            $details[$column] = Schema::getColumnType($table, $column);
        }
        $results[$table] = $details;
    } else {
        $results[$table] = "NOT FOUND";
    }
}
file_put_contents('db_structure.json', json_encode($results, JSON_PRETTY_PRINT));
echo "DONE";
