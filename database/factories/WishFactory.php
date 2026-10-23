<?php

namespace Database\Factories;

use App\Models\Greeting;
use App\Models\Wish;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wish>
 */
class WishFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'greeting_id' => Greeting::factory()->invitation(),
            'name' => fake()->name(),
            'message' => 'Selamat menempuh hidup baru, semoga sakinah mawaddah warahmah.',
            'is_hidden' => false,
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => ['is_hidden' => true]);
    }
}
