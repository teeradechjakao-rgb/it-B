<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostImage extends Model
{
    use HasFactory;

    // ตารางนี้ไม่มี created_at / updated_at ต้องปิดไว้ ไม่งั้น Laravel พยายามเขียนแล้ว Error
    public $timestamps = false;

    protected $fillable = ['image_path'];

    // [เพิ่มใหม่] แนบ image_url ไปกับ JSON ทุกครั้ง (React ใช้แสดงรูปได้เลย)
    protected $appends = ['image_url'];

    public function exchangePost()
    {
        return $this->belongsTo(ExchangePost::class);
    }

    // [เพิ่มใหม่] สร้าง URL เต็มของรูปโพสต์ เช่น http://127.0.0.1:8000/storage/posts/xxx.jpg
    public function getImageUrlAttribute(): string
    {
        return asset('storage/' . $this->image_path);
    }
}
