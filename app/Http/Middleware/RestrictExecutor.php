<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tài khoản có role chính (user_management.userGroup) là Executor - người thực thi - chỉ dùng được trang
 * "Ghi Nhận Sản Xuất": đăng nhập vào thẳng trang đó, mọi URL khác bị đưa về đó (AJAX trả 403).
 * Gắn vào nhóm middleware web nên áp cho cả các route không có CheckLogin.
 *
 * Lưu ý: tên role so sánh cứng như 'Admin' / 'Schedualer' - đổi tên role Executor ở /User/role thì phải sửa ROLE.
 */
class RestrictExecutor
{
    const ROLE = 'Executor';

    // Route được phép: trang Ghi Nhận + các thao tác của nó, đăng xuất / đổi mật khẩu, trang công khai
    const ALLOWED_ROUTES = ['pages.Schedual.record.*', 'login', 'logout', 'changePassword', 'pages.execution.public'];

    public static function active(): bool
    {
        return (session('user')['userGroup'] ?? null) === self::ROLE;
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Trang đăng nhập "/" (GET không có tên route) luôn mở được
        if (!self::active() || $request->routeIs(...self::ALLOWED_ROUTES) || $request->is('/')) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => '❌ Tài khoản người thực thi chỉ dùng được trang Nhận – Trả Phòng'], 403);
        }

        return redirect()->route('pages.Schedual.record.index');
    }
}
