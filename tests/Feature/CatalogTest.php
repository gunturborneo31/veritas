<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Component;
use App\Models\Series;
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

    public function test_greeting_catalog_hides_invitations_and_categories_that_only_hold_invitations(): void
    {
        $birthday = Category::factory()->create(['slug' => 'ulang-tahun', 'sort_order' => 0]);
        $wedding = Category::factory()->create(['slug' => 'pernikahan', 'sort_order' => 1]);
        Category::factory()->create(['slug' => 'kangen', 'sort_order' => 2]);
        Template::factory()->for($birthday)->create(['slug' => 'kue']);
        Template::factory()->for($wedding)->invitation()->create(['slug' => 'janji-suci']);

        $this->get(route('catalog.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.jenis', 'ucapan')
                ->has('templates', 1)
                ->where('templates.0.slug', 'kue')
                ->where('categories', fn ($categories) => collect($categories)->pluck('slug')->all() === ['ulang-tahun', 'kangen']));
    }

    public function test_invitation_catalog_lists_only_invitations_and_their_categories(): void
    {
        $birthday = Category::factory()->create(['slug' => 'ulang-tahun', 'sort_order' => 0]);
        $wedding = Category::factory()->create(['slug' => 'pernikahan', 'sort_order' => 1]);
        Template::factory()->for($birthday)->create(['slug' => 'kue']);
        Template::factory()->for($wedding)->invitation()->create(['slug' => 'janji-suci']);

        $this->get(route('catalog.index', ['jenis' => 'undangan']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.jenis', 'undangan')
                ->has('templates', 1)
                ->where('templates.0.slug', 'janji-suci')
                ->where('categories', fn ($categories) => collect($categories)->pluck('slug')->all() === ['pernikahan']));
    }

    public function test_unknown_catalog_type_falls_back_to_greetings(): void
    {
        Template::factory()->create(['slug' => 'kue']);

        $this->get(route('catalog.index', ['jenis' => 'ngawur']))
            ->assertInertia(fn (Assert $page) => $page->where('filters.jenis', 'ucapan')->has('templates', 1));
    }

    public function test_home_sends_invitation_only_categories_to_the_invitation_catalog(): void
    {
        $wedding = Category::factory()->create(['slug' => 'pernikahan']);
        Template::factory()->for($wedding)->invitation()->create();

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('categories.0.jenis', 'undangan')
                ->where('featured', []));
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

    public function test_bundled_cover_from_public_folder_and_uploaded_cover_both_resolve_to_urls(): void
    {
        $bundled = Template::factory()->create(['cover_path' => '/images/templates/tiup-lilin.webp']);
        $uploaded = Template::factory()->create(['cover_path' => 'templates/unggahan-admin.jpg']);

        $this->assertSame(asset('images/templates/tiup-lilin.webp'), $bundled->coverUrl());
        $this->assertSame(asset('storage/templates/unggahan-admin.jpg'), $uploaded->coverUrl());
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

    public function test_designs_of_one_series_share_a_single_catalog_card(): void
    {
        $series = Series::factory()->create(['name' => 'Tiup Lilin']);
        Template::factory()->for($series)->create(['slug' => 'pesta', 'design_name' => 'Pesta', 'price' => 35000, 'sort_order' => 0]);
        Template::factory()->for($series)->create(['slug' => 'pastel', 'design_name' => 'Pastel', 'price' => 29000, 'sort_order' => 1]);
        Template::factory()->create(['slug' => 'mandiri', 'sort_order' => 2]);

        $this->get(route('catalog.index'))->assertInertia(fn (Assert $page) => $page
            ->has('templates', 2)
            ->where('templates.0.slug', 'pesta')
            ->where('templates.0.series.name', 'Tiup Lilin')
            ->where('templates.0.designs.1.slug', 'pastel')
            ->where('templates.0.designs.1.price', 29000)
            ->has('templates.1.designs', 1));
    }

    public function test_template_detail_lists_only_published_designs_of_its_series(): void
    {
        $series = Series::factory()->create();
        $template = Template::factory()->for($series)->create(['sort_order' => 0]);
        Template::factory()->for($series)->create(['slug' => 'desain-lain', 'sort_order' => 1, 'cover_path' => '/images/templates/desain-lain.webp']);
        Template::factory()->for($series)->unpublished()->create();

        $this->get(route('catalog.show', $template))->assertInertia(fn (Assert $page) => $page
            ->has('template.designs', 2)
            ->where('template.designs.1.slug', 'desain-lain')
            ->where('template.designs.1.cover_url', asset('images/templates/desain-lain.webp')));
    }

    public function test_search_matches_series_name(): void
    {
        $series = Series::factory()->create(['name' => 'Lempar Toga']);
        Template::factory()->for($series)->create(['name' => 'Biru Akademik']);
        Template::factory()->create(['name' => 'Ketupat']);

        $this->get(route('catalog.index', ['cari' => 'toga']))->assertInertia(fn (Assert $page) => $page
            ->has('templates', 1)
            ->where('templates.0.name', 'Biru Akademik'));
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
