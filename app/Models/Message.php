<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    // $fillable ไม่รวม sender_id และ is_read
    protected $fillable = [
        'receiver_id',
        'exchange_post_id',
        'message',
    ];

    // ผู้ส่งข้อความ
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // ผู้รับข้อความ
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    // โพสต์แลกเปลี่ยนที่เกี่ยวข้อง (nullable)
    public function exchangePost()
    {
        return $this->belongsTo(ExchangePost::class);
    }
}
