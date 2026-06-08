<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$q = DB::table('assignments')->where('deparment_code', 'PXDN')->whereDate('start', '2026-06-08')->where('active', 1)->where('room_id', 80)->where('stage_groups_code', 3);
echo $q->toSql().PHP_EOL;
print_r($q->getBindings());
echo "Matches: ".$q->count().PHP_EOL;
$q->update(['active' => 0, 'updated_at' => now()]);
echo "Updated.\n";
