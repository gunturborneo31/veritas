<?php

namespace Database\Factories;

use App\Models\Greeting;
use App\Models\Reaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reaction>
 */
class ReactionFactory extends Factory
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
            'kind' => Reaction::KIND_EMOJI,
            'value' => fake()->randomElement(Reaction::EMOJIS),
        ];
    }
}
