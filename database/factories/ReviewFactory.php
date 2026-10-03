<?php

namespace Database\Factories;

use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'category_id' => \App\Models\Category::inRandomOrder()->value('id'),
            'gadget_name' => fake()->randomElement([
                'Logitech G102',
                'Keychron K2',
                'RTX 4060',
                'Sony WH-1000XM5',
                'Samsung 980 Pro',
            ]),
            'rating' => fake()->numberBetween(1, 5),
            'content' => fake()->sentence(12),
        ];
    }
}
