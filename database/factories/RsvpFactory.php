<?php

namespace Database\Factories;

use App\Enums\RsvpAttendance;
use App\Models\Greeting;
use App\Models\Rsvp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rsvp>
 */
class RsvpFactory extends Factory
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
            'attendance' => RsvpAttendance::Hadir,
            'party_size' => 2,
        ];
    }

    public function notAttending(): static
    {
        return $this->state(fn (array $attributes) => ['attendance' => RsvpAttendance::TidakHadir, 'party_size' => 0]);
    }
}
