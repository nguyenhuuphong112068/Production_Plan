<?php

namespace App\Http\Middleware;

use App\Services\LoginHistory;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class UpdateUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (session()->has('user') && isset(session('user')['userId'])) {
            $userId = session('user')['userId'];

            // Tài khoản bị vô hiệu hoá khi đang đăng nhập -> kết thúc phiên ngay. Đặt ở middleware
            // toàn cục (nhóm web) chứ không ở CheckLogin để không route nào lọt.
            if (! DB::table('user_management')->where('id', $userId)->value('isActive')) {
                LoginHistory::end('inactive');
                session()->flush();
                $message = 'Tài Khoản Đã Bị Vô Hiệu Hoá. Vui Lòng Liên Hệ Quản Trị Viên!';

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['message' => $message, 'redirect' => route('login')], 401);
                }

                return redirect()->route('login')->with('error', $message);
            }

            // Cập nhật last_activity trong user_management
            DB::table('user_management')
                ->where('id', $userId)
                ->update(['last_activity' => now()]);

            // Thao tác cuối của phiên trong Lịch Sử Đăng Nhập (tối đa 1 lần/phút)
            LoginHistory::touch();
        }

        return $next($request);
    }
}
