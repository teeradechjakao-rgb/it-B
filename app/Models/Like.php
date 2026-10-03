<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    use HasFactory;

    // บอก Laravel ว่าตารางจริงชื่อ post_likes (ไม่ใช่ likes ตามที่เดาจากชื่อคลาส)
    protected $table = 'post_likes';

    // $fillable ไม่รวม user_id เพราะต้องกำหนดจากฝั่งเซิร์ฟเวอร์เท่านั้น
    protected $fillable = ['exchange_post_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function exchangePost()
    {
        return $this->belongsTo(ExchangePost::class);
    }
}
