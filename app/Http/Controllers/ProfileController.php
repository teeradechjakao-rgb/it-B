<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return response()->json($request->user());
    }

    // อัปเดตข้อมูลโปรไฟล์ (ชื่อ, อีเมล, รหัสผ่าน, รูปโปรไฟล์, ช่องทางติดต่อ)
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            // [เพิ่มใหม่] แก้อีเมลได้ แต่ต้องไม่ซ้ำกับคนอื่น (ยกเว้นอีเมลของตัวเอง)
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'phone' => 'nullable|string|max:20|regex:/^[0-9+\-\s]+$/',
            'line_id' => 'nullable|string|max:50',
            'facebook_contact' => 'nullable|string|max:255',
        ], [
            'name.required' => 'กรุณากรอกชื่อของคุณ',
            'name.max' => 'ชื่อต้องไม่เกิน 255 ตัวอักษร',
            'email.required' => 'กรุณากรอกอีเมล',
            'email.email' => 'รูปแบบอีเมลไม่ถูกต้อง',
            'email.unique' => 'อีเมลนี้ถูกใช้โดยบัญชีอื่นแล้ว',
            'password.min' => 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร',
            'password.confirmed' => 'รหัสผ่านยืนยันไม่ตรงกัน',
            'avatar.image' => 'ไฟล์ที่อัปโหลดต้องเป็นรูปภาพเท่านั้น',
            'avatar.mimes' => 'รองรับเฉพาะไฟล์รูปภาพ jpeg, png, jpg',
            'avatar.max' => 'ขนาดไฟล์รูปต้องไม่เกิน 2MB',
            'phone.regex' => 'เบอร์โทรศัพท์ต้องเป็นตัวเลขเท่านั้น',
            'phone.max' => 'เบอร์โทรศัพท์ต้องไม่เกิน 20 ตัวอักษร',
            'line_id.max' => 'Line ID ต้องไม่เกิน 50 ตัวอักษร',
            'facebook_contact.max' => 'ข้อมูล Facebook ต้องไม่เกิน 255 ตัวอักษร',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email']; // [เพิ่มใหม่]

        foreach (['phone', 'line_id', 'facebook_contact'] as $field) {
            if ($request->has($field)) {
                $user->$field = $validated[$field] ?? null;
            }
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }

        $user->save();

        return response()->json([
            'message' => 'อัปเดตโปรไฟล์เรียบร้อยแล้ว',
            'user' => $user
        ]);
    }

    // [เพิ่มใหม่] โปรไฟล์สาธารณะของผู้ใช้คนอื่น (ดูชื่อ รูป โพสต์ที่เปิดอยู่)
    // ช่องทางติดต่อ (phone/line_id/facebook_contact) แสดงเฉพาะคนที่ล็อกอินแล้วเท่านั้น กันถูกเก็บข้อมูลไปสแปม
    public function publicShow(Request $request, $id)
    {
        $user = User::with(['exchangePosts' => function ($query) {
            $query->where('status', 'open')->with('category', 'images');
        }])->findOrFail($id);

        $currentUser = $request->user('sanctum');

        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'avatar_url' => $user->avatar_url,
            'exchange_posts' => $user->exchangePosts,
        ];

        // เพิ่มช่องทางติดต่อเข้าไปเฉพาะตอนมีคนล็อกอินอยู่
        if ($currentUser) {
            $data['phone'] = $user->phone;
            $data['line_id'] = $user->line_id;
            $data['facebook_contact'] = $user->facebook_contact;
        }

        return response()->json($data);
    }
}