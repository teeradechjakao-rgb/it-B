<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    // $fillable ไม่รวม user_id และ exchange_post_id (กำหนดจากฝั่งเซิร์ฟเวอร์)
    protected $fillable = ['content'];

    // คอมเมนต์เป็นของผู้ใช้งาน 1 คน
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // คอมเมนต์อยู่ใต้โพสต์ 1 โพสต์
    public function exchangePost()
    {
        return $this->belongsTo(ExchangePost::class);
    }
}
