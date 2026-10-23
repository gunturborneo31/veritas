<?php

namespace Database\Factories;

use App\Models\Greeting;
use App\Models\InvitationEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvitationEvent>
 */
class InvitationEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->addDays(30)->setTime(10, 0);

        return [
            'greeting_id' => Greeting::factory()->invitation(),
            'name' => 'Resepsi',
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHours(3),
            'timezone' => 'Asia/Jakarta',
            'venue_name' => 'Gedung Serbaguna',
            'address' => fake()->address(),
            'latitude' => -6.2,
            'longitude' => 106.816666,
            'position' => 1,
        ];
    }

    /**
     * Acara yang sudah selesai sekian hari lalu.
     */
    public function finishedDaysAgo(int $days): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->subDays($days)->subHours(3),
            'ends_at' => now()->subDays($days),
        ]);
    }
}
