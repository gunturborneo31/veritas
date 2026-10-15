<?php

namespace Database\Factories;

use App\Models\MusicTrack;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MusicTrack>
 */
class MusicTrackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true),
            'artist' => 'Pustaka KartuKita',
            'mood' => 'Hangat',
            'duration_seconds' => 150,
            'path' => 'music/'.fake()->slug().'.mp3',
            'license_note' => 'Bebas royalti',
            'is_active' => true,
        ];
    }
}
