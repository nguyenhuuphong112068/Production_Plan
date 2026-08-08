<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
echo "--- intermediate_category cols ---\n";
foreach (DB::select("SHOW COLUMNS FROM intermediate_category") as $r) echo '  '.$r->Field.' ('.$r->Type.")\n";
echo "--- counts ---\n";
echo 'ic active=1,cancel=0: '.DB::table('intermediate_category')->where('active',1)->where('cancel',0)->count()."\n";
echo 'ic total: '.DB::table('intermediate_category')->count()."\n";
echo 'fpc active=1,cancel=0: '.DB::table('finished_product_category')->where('active',1)->where('cancel',0)->count()."\n";
echo 'fpc total: '.DB::table('finished_product_category')->count()."\n";
