<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // API ไม่มีหน้า login ให้ redirect ไป ถ้ายังไม่ล็อกอินให้ตอบ 401 JSON ตรง ๆ
        $middleware->redirectGuestsTo(fn () => null);

        // ตั้งชื่อเล่น (alias) ให้ Middleware ที่เราเขียน
        $middleware->alias([
            'is_admin'   => \App\Http\Middleware\IsAdminMiddleware::class,
            'not_banned' => \App\Http\Middleware\NotBannedMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // ทุก URL ที่ขึ้นต้น /api ให้ error ตอบเป็น JSON เสมอ
        $exceptions->shouldRenderJsonWhen(
            fn($request, $e) => $request->is('api/*') || $request->expectsJson()
        );
    })
    ->create();
