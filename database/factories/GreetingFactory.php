<?php

namespace Database\Factories;

use App\Enums\GreetingStatus;
use App\Models\Greeting;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Greeting>
 */
class GreetingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'template_id' => Template::factory(),
            'user_id' => User::factory(),
            'status' => GreetingStatus::Draf,
            'recipient_name' => fake()->firstName(),
            'sender_name' => fake()->firstName(),
            'occasion' => 'Ulang Tahun',
            'content' => ['components' => []],
            'last_edited_at' => now(),
        ];
    }

    /**
     * Draf anonim milik pemegang token tertentu.
     */
    public function anonymous(string $draftTokenHash): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'draft_token_hash' => $draftTokenHash,
        ]);
    }

    public function status(GreetingStatus $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status]);
    }

    /**
     * Ucapan yang sudah lunas dan terbit, lengkap dengan snapshot.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GreetingStatus::Terbit,
            'slug' => Str::lower(Str::random(10)),
            'published_at' => now(),
        ])->afterCreating(function (Greeting $greeting): void {
            $greeting->snapshot()->create([
                'template_id' => $greeting->template_id,
                'recipient_name' => $greeting->recipient_name,
                'sender_name' => $greeting->sender_name,
                'occasion' => $greeting->occasion,
                'components' => $greeting->template->manifest(),
                'content' => $greeting->content,
                'content_hash' => hash('sha256', json_encode($greeting->content)),
            ]);
        });
    }
}
