<?php

namespace App\Http\Controllers\Pages\User;

use App\Http\Controllers\Controller;
use App\Support\PermissionGroups;
use Illuminate\Support\Facades\DB;

class PermissionContoller extends Controller
{
    public function index()
    {
        $datas = DB::table('permissions')
            ->orderBy('permission_group', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($permission) {
                $permission->group_name = PermissionGroups::label($permission->permission_group);

                return $permission;
            });

        session()->put(['title' => 'DANH SÁCH QUYỀN']);

        return view('pages.User.permission.list', ['datas' => $datas]);
    }
}
