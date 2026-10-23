<?php

namespace Tests\Feature;

use App\Actions\Orders\FulfillOrder;
use App\Enums\GreetingStatus;
use App\Models\Greeting;
use App\Models\Order;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesGreetingTemplates;
use Tests\TestCase;

/**
 * Nama link pilihan pembeli (`/u/<nama>`): cek ketersediaan, dipesan saat checkout, dikunci saat terbit.
 */
class GreetingLinkTest extends TestCase
{
    use CreatesGreetingTemplates, RefreshDatabase;

    private ?Template $template = null;

    public function test_check_reports_taken_link_with_free_alternatives(): void
    {
        $this->greeting()->forceFill(['slug' => 'raka-dinda'])->save();
        $greeting = $this->greeting();

        $this->actingAs($greeting->user)
            ->getJson(route('checkout.slug.check', [$greeting, 'slug' => 'Raka Dinda']))
            ->assertOk()
            ->assertJson([
                'slug' => 'raka-dinda',
                'available' => false,
                'message' => 'Link ini sudah dipakai. Coba variasi lain.',
            ])
            ->assertJsonPath('suggestions.0', 'raka-dinda-'.now()->year);
    }

    public function test_check_reports_available_link(): void
    {
        $greeting = $this->greeting();

        $this->actingAs($greeting->user)
            ->getJson(route('checkout.slug.check', [$greeting, 'slug' => 'raka-dinda']))
            ->assertJson(['available' => true, 'url' => url('/u/raka-dinda')]);
    }

    public function test_own_reserved_link_counts_as_available(): void
    {
        $greeting = $this->greeting();
        $greeting->forceFill(['slug' => 'raka-dinda'])->save();

        $this->actingAs($greeting->user)
            ->getJson(route('checkout.slug.check', [$greeting, 'slug' => 'raka-dinda']))
            ->assertJson(['available' => true]);
    }

    public function test_reserved_and_too_short_names_are_rejected(): void
    {
        $greeting = $this->greeting();

        $this->actingAs($greeting->user)
            ->getJson(route('checkout.slug.check', [$greeting, 'slug' => 'admin']))
            ->assertJson(['available' => false, 'message' => 'Nama link ini tidak bisa dipakai.']);

        $this->actingAs($greeting->user)
            ->getJson(route('checkout.slug.check', [$greeting, 'slug' => 'ab']))
            ->assertJson(['available' => false, 'message' => 'Minimal 3 karakter.']);
    }

    public function test_other_user_cannot_check_links_for_someone_elses_card(): void
    {
        $greeting = $this->greeting();

        $this->actingAs(User::factory()->create())
            ->getJson(route('checkout.slug.check', [$greeting, 'slug' => 'raka-dinda']))
            ->assertNotFound();
    }

    public function test_chosen_link_is_reserved_at_checkout_and_kept_when_published(): void
    {
        $greeting = $this->greeting();

        $this->actingAs($greeting->user)
            ->post(route('checkout.store', $greeting), ['confirm_data' => true, 'slug' => 'Untuk Sarah Tersayang'])
            ->assertSessionHasNoErrors();

        $this->assertSame('untuk-sarah-tersayang', $greeting->refresh()->slug);

        app(FulfillOrder::class)->handle(Order::sole());

        $this->assertSame(GreetingStatus::Terbit, $greeting->refresh()->status);
        $this->assertSame('untuk-sarah-tersayang', $greeting->slug);
        $this->get('/u/untuk-sarah-tersayang')->assertOk();
    }

    public function test_checkout_rejects_a_link_that_is_already_taken(): void
    {
        $this->greeting()->forceFill(['slug' => 'raka-dinda'])->save();
        $greeting = $this->greeting();

        $this->actingAs($greeting->user)
            ->post(route('checkout.store', $greeting), ['confirm_data' => true, 'slug' => 'raka-dinda'])
            ->assertSessionHasErrors(['slug' => 'Link ini sudah dipakai. Coba variasi lain.']);

        $this->assertNull($greeting->refresh()->slug);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_without_a_chosen_link_a_random_one_is_created_on_publish(): void
    {
        $greeting = $this->greeting();
        $this->actingAs($greeting->user)->post(route('checkout.store', $greeting), ['confirm_data' => true]);

        app(FulfillOrder::class)->handle(Order::sole());

        $this->assertMatchesRegularExpression('/^[a-z0-9]{10}$/', $greeting->refresh()->slug);
    }

    private function greeting(): Greeting
    {
        return Greeting::factory()->for($this->template ??= $this->createTemplate())->create(['content' => $this->completeContent()]);
    }
}
