<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;

class LoginController extends Controller
{

    // dd ($request->all());  

    public function showLogin()
    {

        session()->put(['title' => 'KÊ HOẠCH SẢN XUẤT']);
        return view('login');
    }


    public function login(Request $request)
    {

        // $hash = password_hash("Abc@123", PASSWORD_DEFAULT);
        // dd($hash);1
        $getUser = DB::table('user_management')->where('userName', '=', $request->username)->first();
        if (is_null($getUser)) {
            return redirect()->route('login')->with('error', 'User Không Tồn Tại, Vui Lòng Đăng Nhập Lại!');
        }
        if (!password_verify($request->passWord, $getUser->passWord)) {
            return redirect()->route('login')->with('error', 'PassWord Không Chính Xác, Vui Lòng Đăng Nhập Lại!');
        }

        $production = DB::table('production')
            ->where('code', $getUser->deparment)
            ->first();

        if ($production) {
            $production_code = $production->code;
            $production_name = $production->name;
        } else {
            $production_code = "PXV1";
            $production_name = "PX Viên 1";
        }

        session()->put('fullCalender', [
                'mode' => "offical",
                'stage_plan_temp_list_id' => null
        ]);
        
        $request->session()->put('user', [
            'userId' => $getUser->id,
            'userName' => $getUser->userName,
            'fullName' => $getUser->fullName,
            'userGroup' => $getUser->userGroup,
            'department' => $getUser->deparment,
            'production_code' => $production_code,
            'production_name' => $production_name,
        ]);


        AuditTrialController::log('Login', "NA", 0, 'NA', 'Đăng Nhập Thành Công');

        return redirect()->route('pages.general.home');
    }

    public function logout(Request $request)
    {
        AuditTrialController::log('Log Out', "NA", 0, 'NA', 'Đăng Xuất');
        $request->session()->flush();
        return redirect()->route('login');
    }
}
