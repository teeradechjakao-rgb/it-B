<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\ExchangePost;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    // ดูคอมเมนต์ทั้งหมดของโพสต์ (ใครก็ดูได้ ถ้าโพสต์เป็น open)
    public function index(Request $request, $postId)
    {
        $post = ExchangePost::findOrFail($postId);

        // route นี้เป็น public เหมือนกัน ใช้ guard('sanctum') แบบเดียวกับ show()
        $currentUser = $request->user('sanctum');

        if ($post->status !== 'open') {
            $isOwner = $currentUser && $currentUser->id === $post->user_id;
            $isAdmin = $currentUser && $currentUser->role === 'admin';

            if (!$isOwner && !$isAdmin) {
                abort(404);
            }
        }

        $comments = $post->comments()
            ->with('user:id,name,avatar')
            ->latest()
            ->paginate(10);

        return response()->json($comments);
    }

    // เพิ่มคอมเมนต์ใต้โพสต์ (ต้องล็อกอิน และโพสต์ต้องเป็น open ยกเว้นเจ้าของ/แอดมิน)
    public function store(Request $request, $postId)
    {
        $post = ExchangePost::findOrFail($postId);

        // route นี้อยู่ใต้ auth:sanctum อยู่แล้ว $request->user() ใช้ได้ปกติ ไม่ null แน่นอน
        if ($post->status !== 'open') {
            $isOwner = $request->user()->id === $post->user_id;
            $isAdmin = $request->user()->role === 'admin';

            if (!$isOwner && !$isAdmin) {
                abort(404);
            }
        }

        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ], [
            'content.required' => 'กรุณากรอกข้อความคอมเมนต์',
            'content.max' => 'คอมเมนต์ต้องไม่เกิน 1000 ตัวอักษร',
        ]);

        $comment = new Comment($validated);
        $comment->user_id = $request->user()->id;
        $comment->exchange_post_id = $post->id;
        $comment->save();

        return response()->json([
            'message' => 'เพิ่มคอมเมนต์สำเร็จ',
            'comment' => $comment->load('user:id,name,avatar'),
        ], 201);
    }

    // แก้ไขคอมเมนต์ (เฉพาะเจ้าของเท่านั้น ตามใบงาน)
    public function update(Request $request, $id)
    {
        $comment = Comment::findOrFail($id);

        if ($comment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'คุณไม่มีสิทธิ์แก้ไขคอมเมนต์นี้'], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ], [
            'content.required' => 'กรุณากรอกข้อความคอมเมนต์',
            'content.max' => 'คอมเมนต์ต้องไม่เกิน 1000 ตัวอักษร',
        ]);

        $comment->update($validated);

        return response()->json([
            'message' => 'แก้ไขคอมเมนต์เรียบร้อยแล้ว',
            'comment' => $comment->load('user:id,name,avatar'),
        ]);
    }

    // ลบคอมเมนต์ (เจ้าของ หรือ Admin)
    public function destroy(Request $request, $id)
    {
        $comment = Comment::findOrFail($id);

        if ($comment->user_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'คุณไม่มีสิทธิ์ลบคอมเมนต์นี้'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'ลบคอมเมนต์เรียบร้อยแล้ว']);
    }
}
