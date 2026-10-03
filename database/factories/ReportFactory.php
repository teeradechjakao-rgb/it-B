<?php

namespace Database\Factories;

use App\Models\Report;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reporter_id' => \App\Models\User::factory(),
            'reported_user_id' => \App\Models\User::factory(),
            'reason' => fake()->randomElement([
                'ไม่ส่งของตามที่ตกลง',
                'สินค้าไม่ตรงกับที่โพสต์',
                'ขอให้โอนเงินก่อนโดยไม่มีหลักประกัน',
            ]),
            'status' => fake()->randomElement(['pending', 'resolved', 'dismissed']),
        ];
    }
}
