<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ExchangePostController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

// --- 1. เส้นทางสาธารณะ ---
// [เพิ่มใหม่] throttle:5,1 = จำกัด 5 ครั้งต่อ 1 นาที กัน Brute Force
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/exchange-posts', [ExchangePostController::class, 'index']);
Route::get('/exchange-posts/{id}', [ExchangePostController::class, 'show']);
Route::get('/exchange-posts/{id}/comments', [CommentController::class, 'index']);

Route::get('/reviews', [ReviewController::class, 'index']);
Route::get('/reviews/{id}', [ReviewController::class, 'show']);

// [เพิ่มใหม่] โปรไฟล์สาธารณะของผู้ใช้คนอื่น
Route::get('/users/{id}', [ProfileController::class, 'publicShow']);

// --- 2. เส้นทางที่ต้องยืนยันตัวตน ---
Route::middleware(['auth:sanctum', 'not_banned'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/profile', [ProfileController::class, 'update']);

    Route::post('/exchange-posts', [ExchangePostController::class, 'store']);
    Route::post('/exchange-posts/{id}', [ExchangePostController::class, 'update']);
    Route::delete('/exchange-posts/{id}', [ExchangePostController::class, 'destroy']);
    // [เพิ่มใหม่] กดไลค์ / ยกเลิกไลค์
    Route::post('/exchange-posts/{id}/like', [ExchangePostController::class, 'toggleLike']);

    Route::post('/exchange-posts/{id}/comments', [CommentController::class, 'store']);
    Route::put('/comments/{id}', [CommentController::class, 'update']);
    Route::delete('/comments/{id}', [CommentController::class, 'destroy']);

    Route::post('/reviews', [ReviewController::class, 'store']);
    // [เพิ่มใหม่] แก้ไขรีวิวตัวเอง
    Route::put('/reviews/{id}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);

    Route::get('/my/posts', [ExchangePostController::class, 'myPosts']);
    Route::get('/my/reviews', [ReviewController::class, 'myReviews']);

    Route::get('/messages', [MessageController::class, 'index']);
    Route::post('/messages', [MessageController::class, 'store']);
    Route::get('/conversations', [MessageController::class, 'conversations']);

    Route::post('/reports', [ReportController::class, 'store']);

    // --- 3. เส้นทางเฉพาะผู้ดูแลระบบ ---
    Route::middleware('is_admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/users', [AdminController::class, 'users']);
        Route::patch('/users/{id}/toggle-status', [AdminController::class, 'toggleUserStatus']);
        Route::get('/reports', [AdminController::class, 'reports']);
        Route::patch('/reports/{id}/status', [AdminController::class, 'updateReportStatus']);
        Route::patch('/exchange-posts/{id}/status', [AdminController::class, 'updatePostStatus']);

        // [เพิ่มใหม่] จัดการหมวดหมู่
        Route::get('/categories', [CategoryController::class, 'adminIndex']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

        // [เพิ่มใหม่] ดูโพสต์/คอมเมนต์ทั้งหมดในระบบ
        Route::get('/exchange-posts', [AdminController::class, 'posts']);
        Route::get('/comments', [AdminController::class, 'comments']);
    });
});