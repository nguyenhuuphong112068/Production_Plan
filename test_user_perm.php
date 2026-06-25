<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

require_once __DIR__ . '/app/Authorization/PermissionHelper.php';

$has = user_has_permission(1, 'schedual_warning_propose', 'boolean');
echo "User 1 has propose: " . ($has ? "YES" : "NO") . "\n";

$hasApprove = user_has_permission(1, 'schedual_warning_approve', 'boolean');
echo "User 1 has approve: " . ($hasApprove ? "YES" : "NO") . "\n";
