<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

$columns = Schema::getColumnListing('quota_maintenance');
file_put_contents('qm_cols.txt', implode(', ', $columns));
echo "Done write to qm_cols.txt\n";
