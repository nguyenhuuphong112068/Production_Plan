<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$columns = DB::select('DESCRIBE stage_plan');
foreach ($columns as $column) {
    if ($column->Field === 'id') {
        print_r($column);
        exit;
    }
}
echo "ID column not found\n";
