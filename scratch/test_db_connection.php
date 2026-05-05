<?php

use Illuminate\Support\Facades\DB;

try {
    $results = DB::connection('training')->select('SELECT TOP 10 * FROM Users');
    echo "Query Successful!\n";
    print_r($results);
} catch (\Exception $e) {
    echo "Connection Failed!\n";
    echo $e->getMessage() . "\n";
}
