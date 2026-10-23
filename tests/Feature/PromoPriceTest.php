<?php

namespace Tests\Feature;

use App\Models\Greeting;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesGreetingTemplates;
use Tests\TestCase;

/**
 * Harga promo global: semua template dibayar dengan harga promo, harga asli tetap tersimpan dan tampil dicoret.
 */
class PromoPriceTest extends TestCase
{
    use CreatesGreetingTemplates, RefreshDatabase;

    public function test_order_is_charged_the_promo_price_with_the_discount_shown_in_the_breakdown(): void
    {
        config(['kartukita.promo.price' => 15000, 'kartukita.promo.label' => 'Harga Promo']);
        $greeting = Greeting::factory()->for($this->createTemplate(price: 79000))->create(['content' => $this->completeContent()]);

        $this->actingAs($greeting->user)->post(route('checkout.store', $greeting), ['confirm_data' => true]);

        $order = Order::sole();
        $this->assertSame(15000, $order->amount);
        $this->assertSame([
            ['label' => "Paket 1 • {$greeting->template->name}", 'amount' => 79000],
            ['label' => 'Harga Promo', 'amount' => -64000],
        ], $order->price_breakdown);
        $this->assertSame(79000, $greeting->template->refresh()->price);
    }

    public function test_template_already_cheaper_than_the_promo_keeps_its_own_price(): void
    {
        config(['kartukita.promo.price' => 15000]);
        $greeting = Greeting::factory()->for($this->createTemplate(price: 10000))->create(['content' => $this->completeContent()]);

        $this->actingAs($greeting->user)->post(route('checkout.store', $greeting), ['confirm_data' => true]);

        $this->assertSame(10000, Order::sole()->amount);
        $this->assertCount(1, Order::sole()->price_breakdown);
    }

    public function test_catalog_shows_promo_price_with_original_price_for_strikethrough(): void
    {
        config(['kartukita.promo.price' => 15000, 'kartukita.promo.label' => 'Harga Promo']);
        $this->createTemplate(price: 29000);

        $this->get(route('catalog.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('templates.0.price', 15000)
                ->where('templates.0.original_price', 29000)
                ->where('templates.0.promo_label', 'Harga Promo')
                ->where('templates.0.designs.0.price', 15000));
    }

    public function test_without_promo_the_original_price_applies(): void
    {
        config(['kartukita.promo.price' => null]);
        $this->createTemplate(price: 29000);

        $this->get(route('catalog.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('templates.0.price', 29000)
                ->where('templates.0.promo_label', null));
    }
}
