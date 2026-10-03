<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // unique() = สุ่มไม่ซ้ำ ใช้ชื่อหมวดจริงของเว็บเรา (มี 6 ชื่อ จึงสร้างได้ไม่เกิน 6 หมวด)
            'name' => fake()->unique()->randomElement([
                'การ์ดจอ',
                'เมาส์',
                'คีย์บอร์ด',
                'จอมอนิเตอร์',
                'หูฟัง',
                'อุปกรณ์จัดเก็บข้อมูล',
            ]),
        ];
    }
}
