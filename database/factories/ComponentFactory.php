<?php

namespace Database\Factories;

use App\Models\Component;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Component>
 */
class ComponentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'icon' => 'widgets',
            'schema' => [
                ['name' => 'body', 'type' => 'textarea', 'label' => 'Isi', 'required' => true],
            ],
            'permissions' => [],
            'is_active' => true,
        ];
    }

    /**
     * Komponen gembok PIN (dipakai untuk menguji alur gerbang penerima).
     */
    public function pinGate(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'gembok_pin',
            'name' => 'Gembok PIN',
            'schema' => [
                ['name' => 'enabled', 'type' => 'toggle', 'label' => 'Aktifkan gembok'],
                ['name' => 'question', 'type' => 'text', 'label' => 'Pertanyaan rahasia'],
                ['name' => 'hint', 'type' => 'text', 'label' => 'Petunjuk'],
                ['name' => 'pin', 'type' => 'pin', 'label' => 'Kode PIN', 'min' => 4, 'max' => 6, 'required' => true],
            ],
        ]);
    }
}
