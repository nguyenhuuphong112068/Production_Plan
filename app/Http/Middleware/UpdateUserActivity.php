<?php

namespace App\Http\Middleware;

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
            
            // Cập nhật last_activity trong user_management
            DB::table('user_management')
                ->where('id', $userId)
                ->update(['last_activity' => now()]);
        }

        return $next($request);
    }
}
