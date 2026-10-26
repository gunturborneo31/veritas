<?php

namespace Tests\Feature;

use App\Http\Controllers\HomeController;
use App\Models\Greeting;
use App\Models\Series;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesGreetingTemplates;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use CreatesGreetingTemplates, RefreshDatabase;

    public function test_hero_plays_sample_content_of_the_first_featured_template(): void
    {
        $template = $this->createTemplate();
        $template->update(['sample_content' => ['components' => ['sampul' => ['title' => 'Selamat Ulang Tahun, Rina!']]]]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('showcase.template.slug', $template->slug)
                ->where('showcase.greeting.values.sampul.title', 'Selamat Ulang Tahun, Rina!')
                ->where('showcase.greeting.gate', null));
    }

    public function test_invitation_section_lists_published_invitations_one_per_series_while_hero_stays_on_greetings(): void
    {
        $greeting = Template::factory()->create(['sort_order' => 0]);
        $wedding = Series::factory()->create();
        $first = Template::factory()->invitation()->for($wedding)->create(['sort_order' => 1]);
        Template::factory()->invitation()->for($wedding)->create(['sort_order' => 2]);
        $aqiqah = Template::factory()->invitation()->create(['sort_order' => 3]);
        Template::factory()->invitation()->unpublished()->create(['sort_order' => 4]);

        $this->get(route('home'))->assertInertia(fn (Assert $page) => $page
            ->where('showcase.template.slug', $greeting->slug)
            ->has('featured', 1)
            ->has('invitations', 2)
            ->where('invitations.0.slug', $first->slug)
            ->where('invitations.1.slug', $aqiqah->slug));
    }

    public function test_hero_has_no_showcase_without_published_templates(): void
    {
        Template::factory()->unpublished()->create();

        $this->get(route('home'))->assertInertia(fn (Assert $page) => $page->where('showcase', null));
    }

    public function test_social_proof_stays_hidden_until_enough_greetings_are_sent(): void
    {
        $template = $this->createTemplate();
        Greeting::factory()->count(HomeController::SOCIAL_PROOF_MINIMUM - 1)->published()->for($template)->create();

        $this->get(route('home'))->assertInertia(fn (Assert $page) => $page->where('stats', null));

        Greeting::factory()->published()->for($template)->create();
        Greeting::factory()->for($template)->create();

        $this->get(route('home'))->assertInertia(fn (Assert $page) => $page
            ->where('stats.sent', HomeController::SOCIAL_PROOF_MINIMUM)
            ->where('stats.replies', 0));
    }
}
