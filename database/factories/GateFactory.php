<?php

namespace Database\Factories;

use App\Enums\GateType;
use App\Models\Gate;
use App\Models\Greeting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Gate>
 */
class GateFactory extends Factory
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
            'type' => GateType::Pin,
            'question' => 'Tanggal kita pertama bertemu?',
            'hint' => 'Formatnya DDMM',
            'answer_hash' => Hash::make('1402'),
            'answer_length' => 4,
            'max_attempts' => 5,
        ];
    }
}
