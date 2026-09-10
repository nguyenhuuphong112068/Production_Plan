<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use Carbon\Carbon;
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
            return redirect()->route('login')
                ->with('error', 'User Không Tồn Tại, Vui Lòng Đăng Nhập Lại!')
                ->with('activeForm', 'login')
                ->withInput($request->only('username'));
        }

        $maxAttempts = (int) config('security.max_login_attempts', 5);
        $lockoutMin  = (int) config('security.lockout_minutes', 15);

        // ── 1. Tự động mở khoá nếu đã quá thời gian khoá (mặc định 15 phút) ──
        if ($getUser->isLocked && ! empty($getUser->locked_at)) {
            $lockedUntil = Carbon::parse($getUser->locked_at)->addMinutes($lockoutMin);
            if (now()->greaterThanOrEqualTo($lockedUntil)) {
                DB::table('user_management')->where('id', $getUser->id)->update([
                    'isLocked'        => 0,
                    'failed_attempts' => 0,
                    'locked_at'       => null,
                ]);
                $getUser->isLocked = 0;
                $getUser->failed_attempts = 0;
                $getUser->locked_at = null;
            }
        }

        // ── 2. Tài khoản vẫn đang bị khoá -> chặn đăng nhập ──
        if ($getUser->isLocked) {
            $remain = $lockoutMin;
            if (! empty($getUser->locked_at)) {
                $remain = (int) ceil(
                    now()->diffInMinutes(Carbon::parse($getUser->locked_at)->addMinutes($lockoutMin), false)
                );
                $remain = max(1, $remain);
            }

            AuditTrialController::log(
                'Login Blocked', 'user_management', $getUser->id, 'NA',
                "Tài khoản đang bị khoá, còn khoảng {$remain} phút", $getUser->userName
            );

            return redirect()->route('login')
                ->with('error', "Tài Khoản Đã Bị Khoá Do Nhập Sai Nhiều Lần. Vui Lòng Thử Lại Sau Khoảng {$remain} Phút.")
                ->with('activeForm', 'login')
                ->withInput($request->only('username'));
        }

        // ── 3. Sai mật khẩu -> tăng bộ đếm, khoá tài khoản nếu vượt ngưỡng ──
        if (! Hash::check($request->passWord, $getUser->passWord)) {

            $attempts = (int) $getUser->failed_attempts + 1;
            $update   = ['failed_attempts' => $attempts];
            $locked   = false;

            if ($attempts >= $maxAttempts) {
                $update['isLocked']  = 1;
                $update['locked_at'] = now();
                $locked = true;
            }

            DB::table('user_management')->where('id', $getUser->id)->update($update);

            AuditTrialController::log(
                'Login Failed', 'user_management', $getUser->id, 'NA',
                $locked
                    ? "Sai mật khẩu lần {$attempts}/{$maxAttempts} - Tài khoản bị KHOÁ {$lockoutMin} phút"
                    : "Sai mật khẩu lần {$attempts}/{$maxAttempts}",
                $getUser->userName
            );

            if ($locked) {
                return redirect()->route('login')
                    ->with('error', "Nhập Sai Mật Khẩu {$maxAttempts} Lần. Tài Khoản Đã Bị Khoá {$lockoutMin} Phút.")
                    ->with('activeForm', 'login')
                    ->withInput($request->only('username'));
            }

            $left = $maxAttempts - $attempts;

            return redirect()->route('login')
                ->with('error', "PassWord Không Chính Xác. Còn {$left} Lần Thử Trước Khi Tài Khoản Bị Khoá.")
                ->with('activeForm', 'login')
                ->withInput($request->only('username'));
        }

        // ── 4. Mật khẩu đúng -> reset bộ đếm sai / trạng thái khoá ──
        if ($getUser->failed_attempts || $getUser->isLocked || ! empty($getUser->locked_at)) {
            DB::table('user_management')->where('id', $getUser->id)->update([
                'failed_attempts' => 0,
                'isLocked'        => 0,
                'locked_at'       => null,
            ]);
        }

        // ── 5. Bắt buộc đổi mật khẩu: lần đăng nhập đầu tiên hoặc mật khẩu quá hạn ──
        $mustChange = (bool) ($getUser->must_change_password ?? false);
        $expired    = ! empty($getUser->changePWdate)
            && Carbon::parse($getUser->changePWdate)->startOfDay()->lessThanOrEqualTo(now()->startOfDay());

        if ($mustChange || $expired) {
            AuditTrialController::log(
                'Login', 'user_management', $getUser->id, 'NA',
                $mustChange
                    ? 'Đăng nhập lần đầu - yêu cầu đặt mật khẩu mới'
                    : 'Mật khẩu đã quá hạn ' . config('security.password_expiry_days') . ' ngày - yêu cầu đổi',
                $getUser->userName
            );

            return redirect()->route('login')
                ->with('error', $mustChange
                    ? 'Đây là lần đăng nhập đầu tiên. Vui lòng đổi mật khẩu để tiếp tục.'
                    : 'Mật khẩu của bạn đã hết hạn. Vui lòng đổi mật khẩu để tiếp tục.')
                ->with('activeForm', 'changePass')
                ->withInput($request->only('username'));
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

        // 3b️⃣ Không cho đổi mật khẩu khi tài khoản đang bị khoá (còn trong 15 phút)
        $lockoutMin = (int) config('security.lockout_minutes', 15);
        if ($getUser->isLocked && ! empty($getUser->locked_at)
            && now()->lessThan(Carbon::parse($getUser->locked_at)->addMinutes($lockoutMin))) {
            return back()
                ->with('error', 'Tài Khoản Đang Bị Khoá. Vui Lòng Thử Lại Sau.')
                ->with('activeForm', 'changePass');
        }

        // 3c️⃣ Không được trùng mật khẩu hiện tại và 3 mật khẩu gần nhất
        if ($this->violatesPasswordHistory($request->newPassword, $getUser)) {
            return redirect()->back()
                ->withErrors(
                    ['newPassword' => 'Mật khẩu mới không được trùng ' . config('security.password_history_count', 3) . ' mật khẩu gần nhất.'],
                    'changePasswordErrors'
                )
                ->with('activeForm', 'changePass');
        }

        // 4️⃣ Cập nhật mật khẩu mới (hash) + dịch chuyển lịch sử + gia hạn 90 ngày
        $newHash = Hash::make($request->newPassword);

        DB::table('user_management')
            ->where('id', $getUser->id)
            ->update([
                'passWord'             => $newHash,
                'hisPW_3'              => $getUser->hisPW_2,
                'hisPW_2'              => $getUser->hisPW_1,
                'hisPW_1'              => $getUser->passWord, // hash cũ vừa bị thay
                'changePWdate'         => today()->addDays((int) config('security.password_expiry_days', 90)),
                'must_change_password' => 0,
                'failed_attempts'      => 0,
                'isLocked'             => 0,
                'locked_at'            => null,
                'updated_at'           => now(),
            ]);

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

    /**
     * Kiểm tra mật khẩu mới có trùng mật khẩu hiện tại hoặc các mật khẩu gần nhất không.
     */
    private function violatesPasswordHistory(string $newPassword, object $user): bool
    {
        $hashes = [
            $user->passWord ?? null,
            $user->hisPW_1 ?? null,
            $user->hisPW_2 ?? null,
            $user->hisPW_3 ?? null,
        ];

        foreach ($hashes as $hash) {
            // Bỏ qua null và dữ liệu rác cũ ("0"); hash bcrypt dài 60 ký tự.
            if (! is_string($hash) || strlen($hash) < 20) {
                continue;
            }

            if (Hash::check($newPassword, $hash)) {
                return true;
            }
        }

        return false;
    }
}
