<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $c = app('App\Http\Controllers\Pages\Schedual\SchedualController');
    $req = new \Illuminate\Http\Request();
    
    // Simulate the POST request parameters
    $req->merge([
        'startDate' => '2026-06-21T17:00:00.000Z',
        'endDate' => '2026-06-28T17:00:00.000Z',
        'multiStage' => false,
        'viewtype' => 'resourceTimelineWeek'
    ]);
    
    session(['user' => [
        'userName' => 'admin', 
        'production_code' => 'P1',
        'userId' => 1,
        'userGroup' => 'Schedualer'
    ]]);
    
    $res = $c->view($req);
    echo "NO ERROR!\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "FILE: " . $e->getFile() . "\n";
    echo "LINE: " . $e->getLine() . "\n";
}
