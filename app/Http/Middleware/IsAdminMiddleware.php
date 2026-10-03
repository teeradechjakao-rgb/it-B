<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // ล็อกอินแล้ว และ role เป็น admin ถึงปล่อยผ่าน
        if (Auth::check() && Auth::user()->role === 'admin') {
            return $next($request);
        }

        // ไม่ผ่าน: ตอบ JSON 403 (API ไม่มีหน้าให้ redirect)
        return response()->json([
            'message' => 'การเข้าถึงถูกปฏิเสธ: สงวนสิทธิ์เฉพาะผู้ดูแลระบบเท่านั้น',
        ], 403);
    }
}
