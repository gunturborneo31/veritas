<?php

namespace Tests\Feature;

use App\Enums\GreetingStatus;
use App\Models\Greeting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesGreetingTemplates;
use Tests\TestCase;

class PreviewTest extends TestCase
{
    use CreatesGreetingTemplates, RefreshDatabase;

    public function test_owner_receives_time_limited_watermarked_preview_link(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->create();

        $response = $this->actingAs($greeting->user)->post(route('studio.preview', $greeting));

        $greeting->refresh();
        $this->assertSame(GreetingStatus::Pratinjau, $greeting->status);
        $this->assertTrue($greeting->preview_expires_at->between(now()->addHours(23), now()->addHours(25)));
        $response->assertRedirectContains(route('preview.show', $greeting).'?token=');

        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Preview/Show'));
    }

    public function test_preview_is_forbidden_without_valid_token(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->create();
        $this->actingAs($greeting->user)->post(route('studio.preview', $greeting));

        $this->get(route('preview.show', $greeting))->assertForbidden();
        $this->get(route('preview.show', [$greeting, 'token' => 'tebakan']))->assertForbidden();
    }

    public function test_expired_preview_token_is_forbidden(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->create();
        $location = $this->actingAs($greeting->user)->post(route('studio.preview', $greeting))->headers->get('Location');

        $this->travel(25)->hours();

        $this->get($location)->assertForbidden();
    }

    public function test_other_user_cannot_issue_preview_for_someone_elses_greeting(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->create();

        $this->actingAs(User::factory()->create())->post(route('studio.preview', $greeting))->assertNotFound();
    }
}
