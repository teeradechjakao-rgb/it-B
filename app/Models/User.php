<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;   // HasApiTokens = ให้ User ออก Token ได้ (Sanctum)

    protected $fillable = ['name', 'email', 'password'];

    // ซ่อนไม่ให้หลุดไปใน JSON ที่ส่งให้ React
    protected $hidden = ['password', 'remember_token'];

    // [เพิ่มใหม่] แนบ avatar_url ไปกับ JSON ทุกครั้ง (React ใช้แสดงรูปได้เลย)
    protected $appends = ['avatar_url'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',   // hash รหัสผ่านให้อัตโนมัติทุกครั้งที่บันทึก
        ];
    }

    // 1 User มีได้หลายโพสต์
    public function exchangePosts()
    {
        return $this->hasMany(ExchangePost::class);
    }

    // 1 User เขียนได้หลายรีวิว
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // [เพิ่มใหม่] 1 User เขียนได้หลายคอมเมนต์
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    // ข้อความที่ส่งออก / ข้อความที่ได้รับ
    // ต้องระบุชื่อคอลัมน์เอง เพราะ Laravel จะเดาว่าเป็น user_id แต่ตารางเราใช้ sender_id / receiver_id
    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    // [เพิ่มใหม่] สร้าง URL รูปโปรไฟล์ หากไม่มีรูปให้ใช้ UI Avatars แทน (ระบบ Fallback)
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }

        return 'https://ui-avatars.com/api/?background=random&name=' . urlencode($this->name);
    }
}
