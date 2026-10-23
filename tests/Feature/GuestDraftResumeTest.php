<?php

namespace Tests\Feature;

use App\Models\Greeting;
use App\Models\User;
use App\Support\Greetings\DraftToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesGreetingTemplates;
use Tests\TestCase;

class GuestDraftResumeTest extends TestCase
{
    use CreatesGreetingTemplates, RefreshDatabase;

    public function test_guest_sees_a_link_back_to_their_latest_device_draft(): void
    {
        $template = $this->createTemplate();
        Greeting::factory()->for($template)->anonymous(DraftToken::hash('perangkat-lain'))->create();
        $draft = Greeting::factory()->for($template)->anonymous(DraftToken::hash('perangkat-saya'))->create(['recipient_name' => 'Rina']);

        $this->withCookie('kk_draft', 'perangkat-saya')
            ->get(route('catalog.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('resume_draft.url', route('studio.edit', $draft))
                ->where('resume_draft.recipient_name', 'Rina'));
    }

    public function test_guest_without_device_draft_gets_no_resume_link(): void
    {
        $this->get(route('home'))->assertInertia(fn (Assert $page) => $page->where('resume_draft', null));
    }

    public function test_signed_in_users_use_kartu_saya_instead_of_the_resume_link(): void
    {
        $this->actingAs(User::factory()->create())
            ->withCookie('kk_draft', 'perangkat-saya')
            ->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page->where('resume_draft', null));
    }
}
