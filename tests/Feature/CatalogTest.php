<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Component;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesGreetingTemplates;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use CreatesGreetingTemplates, RefreshDatabase;

    public function test_categories_report_only_published_templates_so_empty_ones_show_as_coming_soon(): void
    {
        $birthday = Category::factory()->create(['slug' => 'ulang-tahun', 'sort_order' => 0]);
        Category::factory()->create(['slug' => 'kangen', 'sort_order' => 1]);
        Template::factory()->for($birthday)->create();
        Template::factory()->for($birthday)->unpublished()->create();

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Index')
                ->has('templates', 1)
                ->where('categories.0.templates_count', 1)
                ->where('categories.1.templates_count', 0));
    }

    public function test_template_card_includes_theme_and_recipient_flow(): void
    {
        $template = $this->createTemplate();
        $template->update(['theme' => ['accent' => '#1f6b52', 'emoji' => '🌙']]);

        $this->get(route('catalog.index'))->assertInertia(fn (Assert $page) => $page
            ->where('templates.0.theme.accent', '#1f6b52')
            ->where('templates.0.theme.motif', Template::DEFAULT_THEME['motif'])
            ->where('templates.0.flow.0.key', 'sampul')
            ->has('templates.0.flow', 3));
    }

    public function test_demo_resolves_relative_sample_dates_so_countdowns_never_expire(): void
    {
        $this->freezeTime();
        $template = Template::factory()
            ->withComponents(['mundur' => Component::factory()->create([
                'key' => 'hitung_mundur',
                'schema' => [['name' => 'target_at', 'type' => 'datetime', 'label' => 'Menuju', 'required' => true]],
            ])])
            ->create(['sample_content' => ['components' => ['mundur' => ['target_at' => '+23 days 10:00']]]]);

        $this->get(route('catalog.demo', $template))->assertInertia(fn (Assert $page) => $page
            ->where('greeting.values.mundur.target_at', now()->addDays(23)->setTime(10, 0)->format('Y-m-d\TH:i')));
    }

    public function test_optional_steps_are_flagged_in_the_recipient_flow(): void
    {
        $this->createTemplate();

        $this->get(route('catalog.index'))->assertInertia(fn (Assert $page) => $page
            ->where('templates.0.flow.0.optional', false)
            ->where('templates.0.flow.1.optional', true));
    }

    public function test_search_matches_template_story(): void
    {
        Template::factory()->create(['name' => 'Kado Berlapis', 'story' => 'Setiap ketukan membuka kupon janji']);
        Template::factory()->create(['name' => 'Ketupat', 'story' => 'Doa lebaran']);

        $this->get(route('catalog.index', ['cari' => 'kupon']))->assertInertia(fn (Assert $page) => $page
            ->has('templates', 1)
            ->where('templates.0.name', 'Kado Berlapis'));
    }
}
