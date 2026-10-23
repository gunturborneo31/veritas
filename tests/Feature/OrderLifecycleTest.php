<?php

namespace Tests\Feature;

use App\Enums\GreetingStatus;
use App\Enums\OrderStatus;
use App\Models\Greeting;
use App\Models\Order;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesGreetingTemplates;
use Tests\TestCase;

/**
 * Pembatalan otomatis pesanan yang tidak dibayar.
 */
class OrderLifecycleTest extends TestCase
{
    use CreatesGreetingTemplates, RefreshDatabase;

    private ?Template $template = null;

    public function test_unpaid_orders_expire_and_greeting_returns_to_draft(): void
    {
        $stale = $this->pendingOrder();
        $stale->forceFill(['created_at' => now()->subHours(25)])->saveQuietly();
        $fresh = $this->pendingOrder();

        $this->artisan('kartukita:expire-orders')->assertSuccessful();

        $this->assertSame(OrderStatus::Batal, $stale->refresh()->status);
        $this->assertSame(GreetingStatus::Draf, $stale->greeting->status);
        $this->assertSame(OrderStatus::Menunggu, $fresh->refresh()->status);
    }

    public function test_orders_awaiting_review_are_never_expired(): void
    {
        $order = $this->pendingOrder();
        $order->forceFill(['status' => OrderStatus::MenungguKonfirmasi, 'created_at' => now()->subDays(3)])->saveQuietly();

        $this->artisan('kartukita:expire-orders')->assertSuccessful();

        $this->assertSame(OrderStatus::MenungguKonfirmasi, $order->refresh()->status);
    }

    public function test_cancelled_order_cannot_get_a_new_qr(): void
    {
        $order = $this->pendingOrder();
        $order->update(['status' => OrderStatus::Batal]);

        $this->actingAs($order->user)
            ->post(route('orders.qris.refresh', $order))
            ->assertSessionHasErrors(['order' => 'Pesanan ini tidak sedang menunggu pembayaran.']);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_login_failure_message_is_in_indonesian(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'salah'])
            ->assertSessionHasErrors(['email' => 'Email atau kata sandi tidak cocok.']);
    }

    private function pendingOrder(): Order
    {
        $greeting = Greeting::factory()
            ->for($this->createTemplateOnce())
            ->status(GreetingStatus::MenungguBayar)
            ->create(['content' => $this->completeContent()]);

        return Order::factory()->for($greeting)->create();
    }

    private function createTemplateOnce(): Template
    {
        return $this->template ??= $this->createTemplate();
    }
}
