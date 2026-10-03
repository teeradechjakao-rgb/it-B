<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'is_active'];

    // แปลง 0/1 จากฐานข้อมูลเป็น true/false ให้ React
    protected $casts = [
        'is_active' => 'boolean',
    ];

    // 1 Category มีได้หลาย ExchangePost (One-to-Many)
    public function exchangePosts()
    {
        return $this->hasMany(ExchangePost::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
