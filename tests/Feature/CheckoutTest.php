<?php

namespace Tests\Feature;

use App\Enums\GreetingStatus;
use App\Enums\OrderStatus;
use App\Models\Greeting;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesGreetingTemplates;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use CreatesGreetingTemplates, RefreshDatabase;

    public function test_guest_is_redirected_to_login_at_checkout(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->create();

        $this->get(route('checkout.show', $greeting))->assertRedirect(route('login'));
    }

    public function test_order_amount_is_computed_on_server_and_ignores_browser_price(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate(price: 35000))->create(['content' => $this->completeContent()]);

        $this->actingAs($greeting->user)
            ->post(route('checkout.store', $greeting), ['confirm_data' => true, 'amount' => 1000, 'price' => 1000])
            ->assertRedirect(route('orders.show', Order::sole()));

        $order = Order::sole();
        $this->assertSame(35000, $order->amount);
        $this->assertSame(OrderStatus::Menunggu, $order->status);
        $this->assertSame(GreetingStatus::MenungguBayar, $greeting->refresh()->status);
    }

    public function test_data_confirmation_checkbox_is_required(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->create(['content' => $this->completeContent()]);

        $this->actingAs($greeting->user)
            ->post(route('checkout.store', $greeting), [])
            ->assertSessionHasErrors(['confirm_data' => 'Centang konfirmasi bahwa nama dan tanggal sudah kamu periksa.']);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_incomplete_greeting_cannot_be_ordered(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->create(['content' => ['components' => []]]);

        $this->actingAs($greeting->user)
            ->post(route('checkout.store', $greeting), ['confirm_data' => true])
            ->assertSessionHasErrors('greeting');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_reordering_cancels_previous_pending_order(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->create(['content' => $this->completeContent()]);
        $this->actingAs($greeting->user)->post(route('checkout.store', $greeting), ['confirm_data' => true]);

        $this->actingAs($greeting->user)->post(route('checkout.store', $greeting), ['confirm_data' => true]);

        $this->assertSame(1, $greeting->orders()->where('status', OrderStatus::Menunggu)->count());
        $this->assertSame(1, $greeting->orders()->where('status', OrderStatus::Batal)->count());
    }

    public function test_user_cannot_checkout_someone_elses_greeting(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->create(['content' => $this->completeContent()]);

        $this->actingAs(User::factory()->create())
            ->post(route('checkout.store', $greeting), ['confirm_data' => true])
            ->assertNotFound();
    }
}
