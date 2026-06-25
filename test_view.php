<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
try {
    $c = app('App\Http\Controllers\Pages\Schedual\SchedualController');
    $req = new \Illuminate\Http\Request();
    session(['user' => ['userName' => 'admin', 'production_code' => 'P1']]);
    $res = $c->view($req);
    echo "OK\n";
} catch (\Exception $e) {
    echo $e->getMessage() . "\n" . $e->getTraceAsString();
}
