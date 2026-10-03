<?php

namespace Database\Factories;

use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sender_id' => \App\Models\User::factory(),
            'receiver_id' => \App\Models\User::factory(),
            'message' => fake()->randomElement([
                'สนใจครับ ยังอยู่ไหม',
                'ยังอยู่ครับ สนใจแลกกับอะไรครับ',
                'ขอดูรูปเพิ่มได้ไหมครับ',
            ]),
            'is_read' => false,
        ];
    }
}
