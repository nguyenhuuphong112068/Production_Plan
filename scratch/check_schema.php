<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
echo "--- assignments ---\n";
print_r(DB::select('DESCRIBE assignments'));
echo "--- assignment_personnel ---\n";
print_r(DB::select('DESCRIBE assignment_personnel'));
