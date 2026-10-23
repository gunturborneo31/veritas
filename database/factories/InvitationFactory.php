<?php

namespace Database\Factories;

use App\Models\Greeting;
use App\Models\Invitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invitation>
 */
class InvitationFactory extends Factory
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
            'rsvp_enabled' => true,
            'wishes_enabled' => true,
            'gifts_enabled' => true,
            'default_guest_quota' => 2,
        ];
    }
}
