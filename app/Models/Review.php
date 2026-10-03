<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    // $fillable ไม่รวม user_id
    protected $fillable = [
        'category_id',
        'gadget_name',
        'rating',
        'content',
    ];

    // รีวิวเป็นของผู้ใช้งาน 1 คน
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // รีวิวอยู่ในหมวดหมู่ 1 หมวดหมู่
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
