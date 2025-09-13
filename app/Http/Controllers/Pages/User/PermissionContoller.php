<?php

namespace App\Http\Controllers\Pages\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermissionContoller extends Controller
{
    public function index(){
                $datas = DB::table('permissions')->orderBy('permission_group','desc')->get();
                session()->put(['title'=> 'DANH SÁCH QUYỀN']);
                return view('pages.User.permission.list',['datas' => $datas]);
        }
    
}
