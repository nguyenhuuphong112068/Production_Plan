<?php
$perm = DB::table('permissions')->where('name', 'schedual_warning_propose')->first();
echo 'Permission: ' . ($perm ? 'Exists (ID: ' . $perm->id . ')' : 'Not Found') . "\n";

if ($perm) {
    $roles = DB::table('role_permission')->where('permission_id', $perm->id)->pluck('role_id');
    echo 'Roles with this permission: ' . json_encode($roles) . "\n";
}

$userRoles = DB::table('user_role')->where('user_id', 1)->pluck('role_id');
echo 'Roles for User ID 1: ' . json_encode($userRoles) . "\n";
