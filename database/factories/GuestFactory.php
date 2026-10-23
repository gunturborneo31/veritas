<?php

namespace Database\Factories;

use App\Models\Greeting;
use App\Models\Guest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Guest>
 */
class GuestFactory extends Factory
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
            'code' => Str::lower(Str::random(Guest::CODE_LENGTH)),
            'name' => fake()->name(),
            'phone' => '0812'.fake()->numerify('########'),
            'group' => null,
            'quota' => 2,
        ];
    }

    public function checkedIn(): static
    {
        return $this->state(fn (array $attributes) => ['checked_in_at' => now()->subMinutes(10)]);
    }
}
