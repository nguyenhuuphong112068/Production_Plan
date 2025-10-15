<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use Illuminate\Support\Facades\Validator;
class LoginController extends Controller
{

    // dd ($request->all());  

    public function showLogin()
    {

        session()->put(['title' => 'KÊ HOẠCH SẢN XUẤT']);
        return view('login');
    }


    public function login(Request $request){
        
        //$hash = Hash::make("Abc@123"); //  password_hash("Abc@123", PASSWORD_DEFAULT);
      
        $getUser = DB::table('user_management')->where('userName', '=', $request->username)->first();
       
        if (is_null($getUser)) {
            return redirect()->route('login')->with('error', 'User Không Tồn Tại, Vui Lòng Đăng Nhập Lại!')->with('activeForm', 'login');
        }
       
        if (!Hash::check($request->passWord, $getUser->passWord)) {
             
            return redirect()->route('login')->with('error', 'PassWord Không Chính Xác, Vui Lòng Đăng Nhập Lại!')->with('activeForm', 'login');
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

    public function logout(Request $request){
        AuditTrialController::log('Log Out', "NA", 0, 'NA', 'Đăng Xuất');
        $request->session()->flush();
        return redirect()->route('login');
    }

     public function changePassword(Request $request){
        //dd ($request->all());

        // 1️⃣ Kiểm tra dữ liệu nhập
        $validator = Validator::make($request->all(), [
            'newPassword' => [
                'required', 'string', 'min:6', 'max:255',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/',
            ],
            'confirmPassword' => 'required|same:newPassword',
        ], [
            'newPassword.min' => 'Mật khẩu mới phải có ít nhất 6 ký tự',
            'newPassword.regex' => 'Mật khẩu mới không đảm bảo độ phức tạp',
            'confirmPassword.required' => 'Vui lòng xác nhận mật khẩu mới',
            'confirmPassword.same' => 'Xác nhận mật khẩu không khớp',
        ]);

        if ($validator->fails()) {
              
                return redirect()->back()->withErrors($validator, 'changePasswordErrors')->with('activeForm', 'changePass');
        }

        $user = session('user');

        if (!$user) {
            return redirect()->route('login')->with('error', 'Phiên đăng nhập đã hết hạn.')->with('activeForm', 'changePass');;
        }

        // 2️⃣ Lấy thông tin người dùng trong DB
        $dbUser = DB::table('user_management')->where('userName', '=', $request->username)->first();

        if (!$dbUser) {
            return back()->with('error', 'User Không tồn tại');
        }

        // 3️⃣ Xác thực mật khẩu cũ
        if (!Hash::check($request->current_password, $dbUser->passWord)) {
            return back()->with('error', 'Mật khẩu hiện tại không đúng.')->with('activeForm', 'changePass');;
        }

        // 4️⃣ Cập nhật mật khẩu mới (hash)
        $newHash = Hash::make($request->passWord);

        DB::table('user_management')
            ->where('id', $user['userId'])
            ->update(['passWord' => $newHash]);

        // 5️⃣ Ghi log và thông báo
        AuditTrialController::log('ChangePassword', "NA", 0, 'NA', 'Đổi mật khẩu thành công');

        return redirect()->route('pages.general.home');
    }
}
