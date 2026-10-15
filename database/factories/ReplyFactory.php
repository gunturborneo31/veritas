<?php

namespace Database\Factories;

use App\Models\Greeting;
use App\Models\Reply;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reply>
 */
class ReplyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'greeting_id' => Greeting::factory(),
            'sender_name' => fake()->firstName(),
            'message' => fake()->sentence(),
        ];
    }
}
