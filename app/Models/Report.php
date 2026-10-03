<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = ['reported_user_id', 'exchange_post_id', 'reason'];

    // คนแจ้ง (ระบุชื่อคอลัมน์เองเพราะไม่ใช่ user_id)
    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    // คนถูกแจ้ง
    public function reportedUser()
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

    public function exchangePost()
    {
        return $this->belongsTo(ExchangePost::class);
    }
}
