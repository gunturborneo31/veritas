<?php

namespace Tests\Feature;

use App\Enums\GreetingStatus;
use App\Enums\OrderStatus;
use App\Models\Greeting;
use App\Models\Order;
use App\Models\Template;
use App\Models\User;
use App\Notifications\PaymentProofSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesGreetingTemplates;
use Tests\TestCase;

/**
 * Notifikasi admin saat bukti bayar masuk dan pembatalan otomatis pesanan yang tidak dibayar.
 */
class OrderLifecycleTest extends TestCase
{
    use CreatesGreetingTemplates, RefreshDatabase;

    private ?Template $template = null;

    public function test_admins_are_notified_when_payment_proof_is_submitted(): void
    {
        Storage::fake('local');
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();
        $order = $this->pendingOrder();

        $this->actingAs($order->user)->post(route('orders.proof.store', $order), [
            'method' => 'transfer',
            'proof' => UploadedFile::fake()->image('bukti.jpg'),
        ]);

        Notification::assertSentTo($admin, PaymentProofSubmitted::class);
        Notification::assertNotSentTo($customer, PaymentProofSubmitted::class);
    }

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

    public function test_expired_order_rejects_late_payment_proof(): void
    {
        Storage::fake('local');
        $order = $this->pendingOrder();
        $order->update(['status' => OrderStatus::Batal]);

        $this->actingAs($order->user)
            ->post(route('orders.proof.store', $order), ['method' => 'transfer', 'proof' => UploadedFile::fake()->image('bukti.jpg')])
            ->assertSessionHasErrors(['proof' => 'Pesanan ini tidak sedang menunggu pembayaran.']);
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
