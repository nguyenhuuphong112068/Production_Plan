<?php

use Illuminate\Support\Facades\DB;

if (! function_exists('user_has_permission')) {
    function user_has_permission($userId, $permissionName, $typeReturn)
    {
        $result = DB::table('permissions')
                ->join('role_permission', 'permissions.id', '=', 'role_permission.permission_id')
                ->join('user_role', 'role_permission.role_id', '=', 'user_role.role_id')
                ->where('user_role.user_id', $userId)
                ->where('permissions.name', $permissionName)
                ->exists();

        if ($typeReturn == "boolean"){
            return $result;
        }elseif ($typeReturn == "class") {
                if ($result){
                    return "";
                }else{
                    return "disabled";
                }
        }
    }
}
