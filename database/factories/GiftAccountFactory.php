<?php

namespace Database\Factories;

use App\Enums\GiftKind;
use App\Models\GiftAccount;
use App\Models\Greeting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GiftAccount>
 */
class GiftAccountFactory extends Factory
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
            'kind' => GiftKind::Bank,
            'provider' => 'BCA',
            'account_number' => fake()->numerify('##########'),
            'account_holder' => fake()->name(),
            'position' => 1,
        ];
    }
}
