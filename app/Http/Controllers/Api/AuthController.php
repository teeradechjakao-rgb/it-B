<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * สมัครสมาชิก
     */
    public function register(Request $request)
    {
        // ตรวจสอบความถูกต้องของข้อมูล (ไม่ผ่านจะตอบ 422 อัตโนมัติ)
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'name.required'      => 'กรุณากรอกชื่อ',
            'email.required'     => 'กรุณากรอกอีเมล',
            'email.email'        => 'รูปแบบอีเมลไม่ถูกต้อง',
            'email.unique'       => 'อีเมลนี้ถูกใช้แล้ว',
            'password.required'  => 'กรุณากรอกรหัสผ่าน',
            'password.min'       => 'รหัสผ่านต้องยาวอย่างน้อย 8 ตัวอักษร',
            'password.confirmed' => 'รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน',
        ]);

        // สร้างผู้ใช้จากข้อมูลที่ผ่านการตรวจแล้วเท่านั้น (ไม่ใช้ $request->all())
        $user = User::create($validated);

        // โหลดจากฐานข้อมูลอีกครั้ง เพื่อให้ได้ค่า default (role = user, status = active)
        $user->refresh();

        return response()->json([
            'user'  => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ], 201);
    }

    /**
     * เข้าสู่ระบบ
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        // อีเมลไม่มี หรือรหัสผ่านไม่ตรง ตอบข้อความเดียวกัน (ไม่บอกว่าอีเมลไหนมีในระบบ)
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง',
            ], 401);
        }

        // ตรวจสถานะแบน (ตรวจหลังรหัสผ่านถูก)
        if ($user->status === 'banned') {
            return response()->json([
                'message' => 'บัญชีของคุณถูกระงับการใช้งาน',
            ], 403);
        }

        return response()->json([
            'user'  => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ]);
    }

    /**
     * ออกจากระบบ
     */
    public function logout(Request $request)
    {
        // ลบ Token ที่ใช้อยู่ตอนนี้ออกจากฐานข้อมูล
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'ออกจากระบบแล้ว']);
    }

    /**
     * ดูข้อมูลผู้ใช้ที่ล็อกอินอยู่
     */
    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
