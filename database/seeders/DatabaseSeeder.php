<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\ExchangePost;
use App\Models\Message;
use App\Models\PostImage;
use App\Models\Report;
use App\Models\Review;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1) admin และผู้ใช้ที่ถูกแบน (ไว้ทดสอบตอนล็อกอิน)
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);
        User::factory()->create([
            'name' => 'Banned User',
            'email' => 'banned@example.com',
            'status' => 'banned',
        ]);

        // 2) สร้าง 6 หมวดหมู่ (ต้องมาก่อนโพสต์ เพราะโพสต์ต้องอ้างอิงหมวดที่มีอยู่)
        Category::factory(6)->create();

        // 3) สร้าง 10 ผู้ใช้ -> แต่ละคนมี 2 โพสต์ -> แต่ละโพสต์มี 1-3 รูป
        //    และแต่ละคนเขียน 1-2 รีวิว
        $users = User::factory(10)->create()->each(function ($user) {
            ExchangePost::factory(2)->create(['user_id' => $user->id])
                ->each(function ($post) {
                    PostImage::factory(rand(1, 3))->create(['exchange_post_id' => $post->id]);
                });

            Review::factory(rand(1, 2))->create(['user_id' => $user->id]);
        });

        // 4) Report 5 อัน (คนแจ้งต้องไม่ใช่เจ้าของโพสต์)
        ExchangePost::inRandomOrder()->take(5)->get()->each(function ($post) use ($users) {
            Report::factory()->create([
                'reporter_id' => $users->where('id', '!=', $post->user_id)->random()->id,
                'reported_user_id' => $post->user_id,
                'exchange_post_id' => $post->id,
            ]);
        });

        // 5) แชท: ผู้ซื้อทักเจ้าของโพสต์ แล้วเจ้าของตอบกลับ
        ExchangePost::take(5)->get()->each(function ($post) use ($users) {
            $buyer = $users->where('id', '!=', $post->user_id)->random();

            Message::factory()->create([
                'sender_id' => $buyer->id,
                'receiver_id' => $post->user_id,
                'exchange_post_id' => $post->id,
            ]);
            Message::factory()->create([
                'sender_id' => $post->user_id,
                'receiver_id' => $buyer->id,
                'exchange_post_id' => $post->id,
                'is_read' => true,
            ]);
        });
    }
}
