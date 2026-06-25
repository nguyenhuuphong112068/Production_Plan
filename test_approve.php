<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$perm = DB::table('permissions')->where('name', 'schedual_warning_approve')->first();
echo 'Permission: ' . ($perm ? 'Exists (ID: ' . $perm->id . ')' : 'Not Found') . "\n";

if ($perm) {
    $roles = DB::table('role_permission')->where('permission_id', $perm->id)->pluck('role_id');
    echo 'Roles with approve: ' . json_encode($roles) . "\n";
}
