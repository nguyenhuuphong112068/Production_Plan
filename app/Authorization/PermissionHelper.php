<?php

use Illuminate\Support\Facades\DB;

if (! function_exists('user_permission_names')) {
    /**
     * Danh sách tên quyền user thực sự có = quyền từ nhóm quyền (role),
     * sau đó áp quyền cấp riêng cho user (user_permission) đè lên.
     * Kết quả cache theo user trong 1 request để tránh query lặp.
     */
    function user_permission_names($userId)
    {
        static $cache = [];

        if (array_key_exists($userId, $cache)) {
            return $cache[$userId];
        }

        $names = [];

        $fromRole = DB::table('permissions')
            ->join('role_permission', 'permissions.id', '=', 'role_permission.permission_id')
            ->join('user_role', 'role_permission.role_id', '=', 'user_role.role_id')
            ->where('user_role.user_id', $userId)
            ->pluck('permissions.name');

        foreach ($fromRole as $name) {
            $names[$name] = true;
        }

        // Quyền cấp riêng cho user ghi đè kết quả từ nhóm quyền
        $overrides = DB::table('permissions')
            ->join('user_permission', 'permissions.id', '=', 'user_permission.permission_id')
            ->where('user_permission.user_id', $userId)
            ->pluck('user_permission.is_denied', 'permissions.name');

        foreach ($overrides as $name => $isDenied) {
            if ($isDenied) {
                unset($names[$name]);
            } else {
                $names[$name] = true;
            }
        }

        $cache[$userId] = $names;

        return $names;
    }
}

if (! function_exists('user_has_permission')) {
    function user_has_permission($userId, $permissionName, $typeReturn)
    {
        $result = isset(user_permission_names($userId)[$permissionName]);

        if ($typeReturn == "boolean") {
            return $result;
        } elseif ($typeReturn == "disabled") {
            if ($result) {
                return "";
            } else {
                return "disabled";
            }
        }
    }
}
