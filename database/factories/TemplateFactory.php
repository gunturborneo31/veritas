<?php

namespace Database\Factories;

use App\Enums\GreetingType;
use App\Enums\TemplateTier;
use App\Models\Category;
use App\Models\Component;
use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Template>
 */
class TemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'category_id' => Category::factory(),
            'slug' => Str::slug($name),
            'name' => Str::title($name),
            'tagline' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'tier' => TemplateTier::Interaktif,
            'price' => 29000,
            'is_published' => true,
            'sort_order' => 0,
        ];
    }

    public function invitation(): static
    {
        return $this->state(fn (array $attributes) => ['type' => GreetingType::Undangan]);
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => ['is_published' => false]);
    }

    /**
     * Pasang komponen ke template sesuai urutan.
     *
     * @param  array<string, Component>  $components  slot key => komponen
     */
    public function withComponents(array $components): static
    {
        return $this->afterCreating(function (Template $template) use ($components): void {
            $position = 1;

            foreach ($components as $key => $component) {
                $template->components()->create([
                    'component_id' => $component->id,
                    'key' => $key,
                    'position' => $position++,
                    'is_required' => true,
                ]);
            }
        });
    }
}
