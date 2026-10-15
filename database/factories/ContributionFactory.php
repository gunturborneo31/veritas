<?php

namespace Database\Factories;

use App\Models\Contribution;
use App\Models\Greeting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contribution>
 */
class ContributionFactory extends Factory
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
            'contributor_name' => fake()->firstName(),
            'message' => fake()->sentence(),
            'is_hidden' => false,
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => ['is_hidden' => true]);
    }
}
