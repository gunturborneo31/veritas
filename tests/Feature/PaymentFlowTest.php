<?php

namespace Tests\Feature;

use App\Enums\GreetingStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Greeting;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesGreetingTemplates;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use CreatesGreetingTemplates, RefreshDatabase;

    public function test_submitting_proof_locks_the_greeting_content(): void
    {
        Storage::fake('local');
        $order = $this->pendingOrder();

        $this->actingAs($order->user)
            ->post(route('orders.proof.store', $order), ['method' => 'transfer', 'proof' => UploadedFile::fake()->image('bukti.jpg')])
            ->assertRedirect(route('orders.show', $order));

        $this->assertSame(OrderStatus::MenungguKonfirmasi, $order->refresh()->status);
        $this->assertSame(GreetingStatus::Terkunci, $order->greeting->status);
        Storage::disk('local')->assertExists($order->latestPayment->proof_path);

        $this->actingAs($order->user)
            ->put(route('studio.update', $order->greeting), ['recipient_name' => 'Diubah'])
            ->assertSessionHas('error');
        $this->assertNotSame('Diubah', $order->greeting->refresh()->recipient_name);
    }

    public function test_admin_confirmation_publishes_greeting_with_permanent_snapshot(): void
    {
        $order = $this->orderAwaitingReview();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.orders.confirm', $order))->assertRedirect(route('admin.orders.show', $order));

        $greeting = $order->greeting->refresh();
        $this->assertSame(GreetingStatus::Terbit, $greeting->status);
        $this->assertSame(OrderStatus::Lunas, $order->refresh()->status);
        $this->assertSame(PaymentStatus::Diterima, $order->latestPayment->status);
        $this->assertMatchesRegularExpression('/^[a-z0-9]{10}$/', $greeting->slug);
        $this->assertSame($greeting->content['components'], $greeting->snapshot->content['components']);
        $this->assertSame([], $greeting->snapshot->content['contributions']);
        $this->assertSame('sampul', $greeting->snapshot->components[0]['key']);
    }

    public function test_non_admin_cannot_confirm_payment(): void
    {
        $order = $this->orderAwaitingReview();

        $this->actingAs($order->user)->post(route('admin.orders.confirm', $order))->assertForbidden();
        $this->assertSame(OrderStatus::MenungguKonfirmasi, $order->refresh()->status);
    }

    public function test_rejected_proof_reopens_the_order_and_draft(): void
    {
        $order = $this->orderAwaitingReview();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.orders.reject', $order), ['note' => 'Nominal tidak sesuai'])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertSame(OrderStatus::Menunggu, $order->refresh()->status);
        $this->assertSame(PaymentStatus::Ditolak, $order->latestPayment->status);
        $this->assertSame(GreetingStatus::MenungguBayar, $order->greeting->status);
    }

    public function test_payment_cannot_be_confirmed_twice(): void
    {
        $order = $this->orderAwaitingReview();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post(route('admin.orders.confirm', $order));

        $this->actingAs($admin)->post(route('admin.orders.confirm', $order))->assertSessionHasErrors('order');
        $this->assertDatabaseCount('greeting_snapshots', 1);
    }

    private function pendingOrder(): Order
    {
        $greeting = Greeting::factory()
            ->for($this->createTemplate())
            ->status(GreetingStatus::MenungguBayar)
            ->create(['content' => $this->completeContent()]);

        return Order::factory()->for($greeting)->create();
    }

    private function orderAwaitingReview(): Order
    {
        $order = $this->pendingOrder();
        $order->update(['status' => OrderStatus::MenungguKonfirmasi]);
        $order->greeting->update(['status' => GreetingStatus::Terkunci]);
        Payment::factory()->for($order)->create();

        return $order;
    }
}
