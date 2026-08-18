<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    // dd ($request->all());

    public function showLogin()
    {

        //session()->put(['title' => 'KÊ HOẠCH SẢN XUẤT']);

        return view('login', []);
    }

    public function login(Request $request)
    {

        // $hash = Hash::make("Abc@123"); //  password_hash("Abc@123", PASSWORD_DEFAULT);

        $getUser = DB::table('user_management')->where('userName', '=', $request->username)->first();

        if (is_null($getUser)) {
            return redirect()->route('login')->with('error', 'User Không Tồn Tại, Vui Lòng Đăng Nhập Lại!')->with('activeForm', 'login');
        }

        if (! Hash::check($request->passWord, $getUser->passWord)) {

            return redirect()->route('login')->with('error', 'PassWord Không Chính Xác, Vui Lòng Đăng Nhập Lại!')->with('activeForm', 'login');
        }

        $production = DB::table('production')
            ->where('code', $getUser->deparment)
            ->first();

        if ($production) {
            $production_code = $production->code;
            $production_name = $production->name;
        } else {
            $production_code = 'PXV1';
            $production_name = 'PX Viên 1';
        }


        $request->session()->put('user', [
            'userId' => $getUser->id,
            'userName' => $getUser->userName,
            'fullName' => $getUser->fullName,
            'passWord' => $request->passWord,
            'userGroup' => $getUser->userGroup,
            'department' => $getUser->deparment,
            'group_name' => $getUser->groupName,
            'production_code' => $production_code,
            'production_name' => $production_name,
        ]);


        // Tự động đồng bộ nhân sự khi đăng nhập
        $this->syncEmployees($getUser->deparment);

        // Kích hoạt gửi thông báo nhắc lịch chưa sắp lúc 8h00 nếu chưa chạy trong ngày
        if ($getUser->userGroup === 'Schedualer') {
            $now = \Carbon\Carbon::now();
            if ($now->hour >= 8) {
                $today = $now->toDateString();
                $lastRun = \Illuminate\Support\Facades\Cache::get('last_unscheduled_notification_date');
                
                if ($lastRun !== $today) {
                    try {
                        \Illuminate\Support\Facades\Artisan::call('notify:unscheduled-batches');
                        \Illuminate\Support\Facades\Cache::put('last_unscheduled_notification_date', $today);
                    } catch (\Exception $e) {
                        // Bỏ qua lỗi nếu command chạy thất bại để không chặn luồng login
                    }
                }
            }
        }

        AuditTrialController::log('Login', 'NA', 0, 'NA', 'Đăng Nhập Thành Công');

        return redirect()->route('pages.general.home');
    }

    /**
     * Dong bo nhan su luc dang nhap - CHI doc cache, TUYET DOI khong goi API.
     *
     * May chu nguon mat ~9.5s (PXTN) den ~88s (PXV1) cho MOI request, nen moi
     * loi goi API o day deu lam nguoi dung cho. Phan goi API da duoc chuyen sang
     * command `employees:sync-roster` chay nen; o day chi ghi xuong DB tu cache
     * ma command do da nap san.
     */
    private function syncEmployees($departmentCode)
    {
        try {
            app(\App\Services\EmployeeRosterSync::class)->syncFromCache($departmentCode);
        } catch (\Throwable $e) {
            // Khong duoc lam gian doan qua trinh dang nhap
            \Illuminate\Support\Facades\Log::warning(
                "Dong bo nhan su luc dang nhap that bai: " . $e->getMessage(),
                ["department" => $departmentCode]
            );
        }
    }
    public function logout(Request $request)
    {
        AuditTrialController::log('Log Out', 'NA', 0, 'NA', 'Đăng Xuất');
        $request->session()->flush();

        return redirect()->route('login');
    }

    public function changePassword(Request $request)
    {
        // dd ($request->all());

        // 1️⃣ Kiểm tra dữ liệu nhập
        $validator = Validator::make($request->all(), [
            'newPassword' => [
                'required',
                'string',
                'min:6',
                'max:255',
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

        if ($request->oldPassword == $request->newPassword) {
            return redirect()->route('login')->with('error', 'PassWord mới trung PassWord hiện tại!')->with('activeForm', 'changePass');
        }

        // 2️⃣ Lấy thông tin người dùng trong DB
        $getUser = DB::table('user_management')->where('userName', '=', $request->username)->first();

        if (! $getUser) {
            return back()->with('error', 'User Không tồn tại');
        }

        // 3️⃣ Xác thực mật khẩu cũ
        if (! Hash::check($request->oldPassword, $getUser->passWord)) {
            return back()->with('error', 'Mật khẩu hiện tại không đúng.')->with('activeForm', 'changePass');
        }

        // 4️⃣ Cập nhật mật khẩu mới (hash)
        $newHash = Hash::make($request->newPassword);

        DB::table('user_management')
            ->where('id', $getUser->id)
            ->update(['passWord' => $newHash]);

        $production = DB::table('production')
            ->where('code', $getUser->deparment)
            ->first();

        if ($production) {
            $production_code = $production->code;
            $production_name = $production->name;
        } else {
            $production_code = 'PXV1';
            $production_name = 'PX Viên 1';
        }

        $request->session()->put('user', [
            'userId' => $getUser->id,
            'userName' => $getUser->userName,
            'fullName' => $getUser->fullName,
            'passWord' => $request->newPassword,
            'userGroup' => $getUser->userGroup,
            'department' => $getUser->deparment,
            'production_code' => $production_code,
            'production_name' => $production_name,
        ]);

        // 5️⃣ Ghi log và thông báo
        AuditTrialController::log('ChangePassword', 'NA', 0, 'NA', 'Đổi mật khẩu thành công');

        return redirect()->route('pages.general.home');
    }
}
