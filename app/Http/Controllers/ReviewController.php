<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::with(['user:id,name,email,avatar', 'category']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('keyword')) {
            $query->where('gadget_name', 'like', '%' . $request->keyword . '%');
        }

        $reviews = $query->latest()->paginate(10);

        return response()->json($reviews);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'gadget_name' => 'required|string|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'content' => 'required|string',
        ], [
            'category_id.required' => 'กรุณาเลือกหมวดหมู่สินค้า',
            'gadget_name.required' => 'กรุณาระบุชื่ออุปกรณ์',
            'rating.required' => 'กรุณาให้คะแนนรีวิว',
            'rating.min' => 'คะแนนต้องอย่างน้อย 1 ดาว',
            'rating.max' => 'คะแนนต้องไม่เกิน 5 ดาว',
            'content.required' => 'กรุณากรอกเนื้อหารีวิว',
        ]);

        $review = new Review($validated);
        $review->user_id = $request->user()->id;
        $review->save();

        return response()->json([
            'message' => 'เพิ่มรีวิวสำเร็จ',
            'review' => $review->load(['user:id,name,email,avatar', 'category'])
        ], 201);
    }

    public function show($id)
    {
        $review = Review::with(['user:id,name,email,avatar', 'category'])->findOrFail($id);
        return response()->json($review);
    }

    public function update(Request $request, $id)
    {
        $review = Review::findOrFail($id);

        if ($review->user_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'คุณไม่มีสิทธิ์แก้ไขรีวิวนี้'], 403);
        }

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'gadget_name' => 'required|string|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'content' => 'required|string',
        ]);

        $review->update($validated);

        return response()->json([
            'message' => 'แก้ไขรีวิวเรียบร้อยแล้ว',
            'review' => $review->load(['user:id,name,email,avatar', 'category']),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $review = Review::findOrFail($id);

        if ($review->user_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'คุณไม่มีสิทธิ์ลบรีวิวนี้'], 403);
        }

        $review->delete();

        return response()->json(['message' => 'ลบรีวิวเรียบร้อยแล้ว']);
    }

    // [เพิ่มใหม่] ดึงรีวิวทั้งหมดของผู้ใช้ที่ล็อกอินอยู่ (สำหรับหน้าโปรไฟล์)
    public function myReviews(Request $request)
    {
        $reviews = Review::with('category')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return response()->json($reviews);
    }
}
