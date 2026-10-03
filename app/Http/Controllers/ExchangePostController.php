<?php

namespace App\Http\Controllers;

use App\Models\ExchangePost;
use App\Models\Like;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExchangePostController extends Controller
{
    // 1. แสดงรายการโพสต์ + ค้นหาคีย์เวิร์ด + กรองหมวดหมู่ + แบ่งหน้า
    public function index(Request $request)
    {
        $currentUser = $request->user('sanctum');

        $query = ExchangePost::with(['user:id,name,email,avatar', 'category', 'images'])
            ->withCount('likes')
            ->where('status', 'open');

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', '%' . $keyword . '%')
                    ->orWhere('description', 'like', '%' . $keyword . '%')
                    ->orWhere('looking_for', 'like', '%' . $keyword . '%');
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $posts = $query->latest()->paginate(10);

        if ($currentUser) {
            $likedPostIds = Like::where('user_id', $currentUser->id)->pluck('exchange_post_id')->toArray();
            $posts->getCollection()->transform(function ($post) use ($likedPostIds) {
                $post->is_liked = in_array($post->id, $likedPostIds);
                return $post;
            });
        } else {
            $posts->getCollection()->transform(function ($post) {
                $post->is_liked = false;
                return $post;
            });
        }

        return response()->json($posts);
    }

    // 2. สร้างโพสต์แลกเปลี่ยนใหม่ + อัปโหลดรูปภาพหลายรูป
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'condition_percent' => 'required|integer|min:0|max:100',
            'looking_for' => 'nullable|string',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048'
        ], [
            'category_id.required' => 'กรุณาเลือกหมวดหมู่สินค้า',
            'category_id.exists' => 'ไม่พบหมวดหมู่ที่ระบุ',
            'title.required' => 'กรุณากรอกชื่อหัวข้อโพสต์',
            'description.required' => 'กรุณากรอกรายละเอียดสินค้า',
            'condition_percent.required' => 'กรุณาระบุสภาพสินค้า',
            'condition_percent.min' => 'สภาพสินค้าต้องไม่ต่ำกว่า 0%',
            'condition_percent.max' => 'สภาพสินค้าต้องไม่เกิน 100%',
            'images.*.image' => 'ต้องอัปโหลดเป็นไฟล์รูปภาพเท่านั้น',
            'images.*.max' => 'ขนาดรูปภาพต้องไม่เกิน 2MB'
        ]);

        $post = new ExchangePost($validated);
        $post->user_id = $request->user()->id;
        $post->status = $request->user()->role === 'admin' ? 'open' : 'pending';
        $post->save();

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('posts', 'public');
                $post->images()->create([
                    'image_path' => $path
                ]);
            }
        }

        return response()->json([
            'message' => $post->status === 'pending'
                ? 'ส่งโพสต์เรียบร้อยแล้ว กรุณารอแอดมินตรวจสอบและอนุมัติ'
                : 'สร้างโพสต์แลกเปลี่ยนสำเร็จ',
            'post' => $post->load(['user:id,name,email,avatar', 'category', 'images'])
        ], 201);
    }

    // 3. ดูรายละเอียดโพสต์รายตัว (ปรับปรุงการดึงคอมเมนต์หลักและคอมเมนต์ตอบกลับพร้อมข้อมูล User)
    public function show(Request $request, $id)
    {
        $post = ExchangePost::with([
                'user:id,name,email,avatar',
                'category',
                'images',
                // ดึงเฉพาะคอมเมนต์หลัก (parent_id เป็น null) และพ่วง replies พร้อม user ของฝั่ง replies ด้วย
                'comments' => function ($query) {
                    $query->with('user:id,name,email,avatar')
                          ->latest();
                }
            ])
            ->withCount('likes')
            ->findOrFail($id);

        $currentUser = $request->user('sanctum');

        if ($post->status !== 'open') {
            $isOwner = $currentUser && $currentUser->id === $post->user_id;
            $isAdmin = $currentUser && $currentUser->role === 'admin';

            if (!$isOwner && !$isAdmin) {
                abort(404);
            }
        }

        $post->is_liked = $currentUser
            ? Like::where('user_id', $currentUser->id)->where('exchange_post_id', $post->id)->exists()
            : false;

        return response()->json($post);
    }

    // 4. แก้ไขข้อมูลโพสต์
    public function update(Request $request, $id)
    {
        $post = ExchangePost::findOrFail($id);

        if ($post->user_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'คุณไม่มีสิทธิ์แก้ไขโพสต์นี้'], 403);
        }

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'condition_percent' => 'required|integer|min:0|max:100',
            'looking_for' => 'nullable|string',
            'status' => 'nullable|in:open,closed',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048'
        ], [
            'condition_percent.min' => 'สภาพสินค้าต้องไม่ต่ำกว่า 0%',
            'condition_percent.max' => 'สภาพสินค้าต้องไม่เกิน 100%',
            'status.in' => 'สถานะไม่ถูกต้อง',
        ]);

        $post->update($validated);

        if ($request->filled('status')) {
            $post->status = $request->status;
            $post->save();
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('posts', 'public');
                $post->images()->create([
                    'image_path' => $path
                ]);
            }
        }

        return response()->json([
            'message' => 'อัปเดตข้อมูลโพสต์สำเร็จ',
            'post' => $post->load(['user:id,name,email,avatar', 'category', 'images'])
        ]);
    }

    // 5. ลบโพสต์
    public function destroy(Request $request, $id)
    {
        $post = ExchangePost::with('images')->findOrFail($id);

        if ($post->user_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'คุณไม่มีสิทธิ์ลบโพสต์นี้'], 403);
        }

        foreach ($post->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $post->delete();

        return response()->json(['message' => 'ลบโพสต์เรียบร้อยแล้ว']);
    }

    // 6. ดึงโพสต์ทั้งหมดของผู้ใช้ที่ล็อกอินอยู่
    public function myPosts(Request $request)
    {
        $posts = ExchangePost::with(['category', 'images'])
            ->withCount('likes')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return response()->json($posts);
    }

    // 7. กดไลค์ / ยกเลิกไลค์ (toggle)
    public function toggleLike(Request $request, $id)
    {
        $post = ExchangePost::findOrFail($id);
        $userId = $request->user()->id;

        $existingLike = Like::where('user_id', $userId)->where('exchange_post_id', $post->id)->first();

        if ($existingLike) {
            $existingLike->delete();
            $liked = false;
        } else {
            Like::create([
                'user_id' => $userId,
                'exchange_post_id' => $post->id,
            ]);
            $liked = true;
        }

        return response()->json([
            'message' => $liked ? 'กดไลค์แล้ว' : 'ยกเลิกไลค์แล้ว',
            'liked' => $liked,
            'likes_count' => $post->likes()->count(),
        ]);
    }

    // 8. จัดการคอมเมนต์และตอบกลับ
    public function storeComment(Request $request, $id)
    {
        $request->validate([
            'content'   => 'required|string',
            'rating'    => 'nullable|integer|min:1|max:5',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        $comment = Comment::create([
            'exchange_post_id' => $id,
            'user_id'          => $request->user()->id,
            'content'          => $request->content,
            'rating'           => $request->rating ?? 5,
            'parent_id'        => $request->parent_id ?? null,
        ]);

        // โหลดข้อมูล user และ replies (ถ้ามี) กลับไปให้ฝั่ง React
        $comment->load(['user:id,name,email,avatar', 'replies.user:id,name,email,avatar']);

        return response()->json([
            'message' => 'เพิ่มความคิดเห็นสำเร็จ',
            'comment' => $comment
        ], 201);
    }

    // 9. ลบความคิดเห็น
    public function destroyComment(Request $request, $commentId)
    {
        $comment = Comment::findOrFail($commentId);

        if ($comment->user_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'คุณไม่มีสิทธิ์ลบความคิดเห็นนี้'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'ลบความคิดเห็นสำเร็จ']);
    }
}
