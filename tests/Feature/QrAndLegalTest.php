<?php

namespace Tests\Feature;

use App\Models\Greeting;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesGreetingTemplates;
use Tests\TestCase;

class QrAndLegalTest extends TestCase
{
    use CreatesGreetingTemplates, RefreshDatabase;

    public function test_owner_downloads_png_qr_for_published_greeting(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->published()->create(['recipient_name' => 'Sarah Clarissa']);

        $response = $this->actingAs($greeting->user)->get(route('result.qr', $greeting));

        $response->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertDownload('qr-kartukita-sarah-clarissa.png');
        $this->assertStringStartsWith("\x89PNG", $response->getContent());
    }

    public function test_qr_is_not_available_for_drafts_or_other_users(): void
    {
        $template = $this->createTemplate();
        $draft = Greeting::factory()->for($template)->create();
        $published = Greeting::factory()->for($template)->published()->create();

        $this->actingAs($draft->user)->get(route('result.qr', $draft))->assertNotFound();
        $this->actingAs(User::factory()->create())->get(route('result.qr', $published))->assertNotFound();
    }

    public function test_result_page_includes_scannable_svg_qr(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->published()->create();

        $this->actingAs($greeting->user)->get(route('result.show', $greeting))
            ->assertInertia(fn (Assert $page) => $page
                ->where('greeting.qr_svg', fn (string $svg) => str_starts_with($svg, '<svg'))
                ->where('greeting.qr_download_url', route('result.qr', $greeting)));
    }

    public function test_legal_pages_are_public(): void
    {
        $this->get(route('legal.privacy'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('Legal/Privacy'));
        $this->get(route('legal.terms'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('Legal/Terms'));
    }

    public function test_account_with_orders_cannot_self_delete(): void
    {
        $order = Order::factory()->create();

        $this->actingAs($order->user)
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertSessionHasErrors('password');

        $this->assertModelExists($order->user);
    }
}
