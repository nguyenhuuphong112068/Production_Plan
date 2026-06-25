<?php
$user = DB::table('user_management')->where('userName', 'admin')->first();
echo "Admin user_management id: " . ($user ? $user->id : 'Not Found') . "\n";

if ($user) {
    $userRoleCount = DB::table('user_role')->where('user_id', $user->id)->count();
    echo "Count roles for this id in user_role: " . $userRoleCount . "\n";
}
