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

        session()->put(['title' => 'KÊ HOẠCH SẢN XUẤT']);

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
        $this->syncEmployees($production_code);

        AuditTrialController::log('Login', 'NA', 0, 'NA', 'Đăng Nhập Thành Công');

        return redirect()->route('pages.general.home');
    }

    private function syncEmployees($departmentCode)
    {

        $depMapping = [
            'EN' => 3,
            'PXTN' => 6,
            'PXV1' => 15,
            'PXVH' => 30,
            'PXDN' => 34,
            'PXV2' => 31
        ];



        $depId = $depMapping[$departmentCode] ?? null;
        if (!$depId) return;

        $month = now()->month;
        $year = now()->year;
        $url = "http://s-webdev:5070/api/shifts/by-department?month={$month}&year={$year}&department={$depId}";
        //dd($url);
        try {
            $ctx = stream_context_create(['http' => ['timeout' => 5]]); // Timeout 5s
            $data = @file_get_contents($url, false, $ctx);
            if (!$data) return;

            $employees = json_decode($data);
            if (empty($employees) || !is_array($employees)) return;

            foreach ($employees as $emp) {
                if (empty($emp->employeeId)) continue;

                DB::transaction(function () use ($emp, $departmentCode) {
                    // 1. Đảm bảo nhân sự tồn tại trong bảng employees
                    $employee = DB::table('employees')->where('code', $emp->employeeId)->first();
                    $employeeId = null;

                    if (!$employee) {
                        $employeeId = DB::table('employees')->insertGetId([
                            'code' => $emp->employeeId,
                            'name' => $emp->employeeName ?? 'N/A',
                            'active' => 1,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    } else {
                        $employeeId = $employee->id;
                        // Cập nhật lại tên nếu có thay đổi từ hệ thống gốc
                        DB::table('employees')->where('id', $employeeId)->update([
                            'name' => $emp->employeeName ?? $employee->name,
                            'active' => 1, // Đảm bảo nhân sự được kích hoạt lại nếu có lịch trực
                            'updated_at' => now()
                        ]);
                    }

                    // 2. Đồng bộ vào bảng phân vùng sản xuất (employee_productions)
                    $prodAssignment = DB::table('employee_productions')
                        ->where('employees_id', $employeeId)
                        ->where('production_code', $departmentCode)
                        ->first();

                    if (!$prodAssignment) {
                        DB::table('employee_productions')->insert([
                            'employees_id' => $employeeId,
                            'production_code' => $departmentCode,
                            'is_main' => 1,
                            'active' => 1,
                            'created_by' => 'System Sync',
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    } else if ($prodAssignment->active == 0) {
                        DB::table('employee_productions')
                            ->where('id', $prodAssignment->id)
                            ->update([
                                'active' => 1,
                                'updated_at' => now()
                            ]);
                    }
                });
            }
        } catch (\Exception $e) {
            // Log lỗi nếu cần, nhưng không làm gián đoạn quá trình đăng nhập
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
