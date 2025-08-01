<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
       

        if (!session()->has('user') || empty(session('user.fullName'))) {
             return redirect()->route ('login')->with('error', 'Vui Lòng Đăng Nhập!');
            
        }
        return $next($request);
    }
}
