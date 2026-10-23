<?php

namespace Tests\Feature;

use App\Enums\GreetingType;
use App\Models\Template;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_seeded_template_offers_memory_map_and_song_as_optional_sections(): void
    {
        $this->seed(CatalogSeeder::class);

        $templates = Template::with('components.component')->get();

        $this->assertNotEmpty($templates);

        foreach ($templates as $template) {
            $keys = $template->components->sortBy('position')->map(fn ($slot) => $slot->component->key)->values();

            foreach (['jejak_lokasi', 'lagu_kita'] as $componentKey) {
                $slots = $template->components->filter(fn ($slot) => $slot->component->key === $componentKey);

                $this->assertCount(1, $slots, "{$template->slug} harus punya tepat satu {$componentKey}");
                $this->assertFalse($slots->first()->is_required, "{$template->slug}: {$componentKey} harus opsional");

                if ($keys->contains('penutup')) {
                    $this->assertLessThan($keys->search('penutup'), $keys->search($componentKey), "{$template->slug}: {$componentKey} harus sebelum penutup");
                }
            }
        }
    }

    public function test_every_seeded_invitation_template_has_a_working_demo_with_sample_events(): void
    {
        $this->seed(CatalogSeeder::class);

        $invitations = Template::query()->where('type', GreetingType::Undangan)->get();

        $this->assertGreaterThanOrEqual(45, $invitations->count());

        foreach ($invitations as $template) {
            $this->get(route('catalog.demo', $template))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('greeting.invitation.events', fn ($events) => count($events) > 0 && collect($events)->every(fn ($event) => filled($event['name']) && filled($event['venue_name']))));
        }
    }

    public function test_design_that_moves_the_venue_does_not_inherit_the_old_map_coordinates(): void
    {
        $this->seed(CatalogSeeder::class);

        $this->get(route('catalog.demo', 'janji-suci-kapel-putih'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('greeting.invitation.events.0.venue_name', 'Gereja Santa Maria')
                ->where('greeting.invitation.events.0.latitude', null)
                ->where('greeting.invitation.events.0.directions_url', null)
                ->where('greeting.invitation.events.1.directions_url', fn (?string $url) => $url !== null));
    }

    public function test_wedding_template_is_seeded_as_invitation_with_optional_extras_before_rsvp(): void
    {
        $this->seed(CatalogSeeder::class);

        $template = Template::query()->where('slug', 'janji-suci')->with('components.component')->sole();
        $keys = $template->components->sortBy('position')->map(fn ($slot) => $slot->component->key)->values();

        $this->assertSame(GreetingType::Undangan, $template->type);
        $this->assertLessThan($keys->search('rsvp'), $keys->search('lagu_kita'));
        $this->assertNotEmpty($template->sample_content['invitation']['events']);
    }
}
