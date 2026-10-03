<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class NotBannedMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // ถ้าล็อกอินอยู่และถูกแบน ให้หยุดทันที
        if (Auth::check() && Auth::user()->status === 'banned') {
            return response()->json([
                'message' => 'บัญชีของคุณถูกระงับการใช้งาน',
            ], 403);
        }

        return $next($request);
    }
}
