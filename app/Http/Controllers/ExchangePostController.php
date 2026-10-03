<?php

namespace App\Http\Controllers;

use App\Models\ExchangePost;
use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExchangePostController extends Controller
{
    // 1. แสดงรายการโพสต์ + ค้นหาคีย์เวิร์ด + กรองหมวดหมู่ + แบ่งหน้า
    public function index(Request $request)
    {
        // route นี้เป็น public ใช้ guard('sanctum') เพื่อรู้ว่าถ้ามีคนแนบ token มา เขาเป็นใคร (ไว้เช็ค is_liked)
        $currentUser = $request->user('sanctum');

        $query = ExchangePost::with(['user:id,name,email,avatar', 'category', 'images'])
            ->withCount('likes') // [เพิ่มใหม่] นับจำนวนไลค์ของแต่ละโพสต์
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

        // [เพิ่มใหม่] ใส่ flag is_liked ให้แต่ละโพสต์ ว่าคนที่ล็อกอินอยู่กดไลค์ไปแล้วหรือยัง
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

        // [เพิ่มใหม่] สมาชิกทั่วไปโพสต์ใหม่ต้องรอแอดมินอนุมัติก่อน (pending)
        // แอดมินโพสต์เอง ให้ผ่านทันทีไม่ต้องรออนุมัติตัวเอง
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

    // 3. ดูรายละเอียดโพสต์รายตัว
    public function show(Request $request, $id)
    {
        $post = ExchangePost::with(['user:id,name,email,avatar', 'category', 'images'])
            ->withCount('likes') // [เพิ่มใหม่]
            ->findOrFail($id);

        // route นี้เป็น public ต้องใช้ guard('sanctum') ตรงๆ เพื่อรู้ว่าถ้ามีคนแนบ token มา เขาเป็นใคร
        $currentUser = $request->user('sanctum');

        // โพสต์ที่ไม่ใช่สถานะ open (pending/closed/hidden) ห้ามคนอื่นเห็น ต้องเป็นเจ้าของหรือแอดมินเท่านั้น
        if ($post->status !== 'open') {
            $isOwner = $currentUser && $currentUser->id === $post->user_id;
            $isAdmin = $currentUser && $currentUser->role === 'admin';

            if (!$isOwner && !$isAdmin) {
                abort(404);
            }
        }

        // [เพิ่มใหม่] เช็คว่าคนที่ล็อกอินอยู่ไลค์โพสต์นี้ไปแล้วหรือยัง
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
            // เจ้าของเปลี่ยนได้เฉพาะ open/closed เอง ห้ามแตะ pending/hidden (ต้องผ่านแอดมินเท่านั้น)
            'status' => 'nullable|in:open,closed',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048'
        ], [
            'condition_percent.min' => 'สภาพสินค้าต้องไม่ต่ำกว่า 0%',
            'condition_percent.max' => 'สภาพสินค้าต้องไม่เกิน 100%',
            'status.in' => 'สถานะไม่ถูกต้อง (เจ้าของเปลี่ยนได้เฉพาะ เปิด/ปิด การแลกเปลี่ยนเท่านั้น)',
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

    // 6. ดึงโพสต์ทั้งหมดของผู้ใช้ที่ล็อกอินอยู่ (สำหรับหน้าโปรไฟล์) ไม่กรองสถานะ
    public function myPosts(Request $request)
    {
        $posts = ExchangePost::with(['category', 'images'])
            ->withCount('likes')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return response()->json($posts);
    }

    // 7. [เพิ่มใหม่] กดไลค์ / ยกเลิกไลค์ (toggle) ต้องล็อกอิน
    public function toggleLike(Request $request, $id)
    {
        $post = ExchangePost::findOrFail($id);
        $userId = $request->user()->id;

        $existingLike = Like::where('user_id', $userId)->where('exchange_post_id', $post->id)->first();

        if ($existingLike) {
            // เคยไลค์แล้ว กดอีกครั้ง = ยกเลิกไลค์
            $existingLike->delete();
            $liked = false;
        } else {
            // ยังไม่เคยไลค์ กดครั้งนี้ = เพิ่มไลค์ใหม่
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
}
