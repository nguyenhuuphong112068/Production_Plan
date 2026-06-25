<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
try {
    $c = app('App\Http\Controllers\Pages\Schedual\SchedualController');
    $c->getPlanWaiting('P1', false);
    echo "OK";
} catch (\Exception $e) {
    echo $e->getMessage();
}
