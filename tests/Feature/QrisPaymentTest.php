<?php

namespace Tests\Feature;

use App\Enums\GreetingStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Greeting;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesGreetingTemplates;
use Tests\TestCase;

class QrisPaymentTest extends TestCase
{
    use CreatesGreetingTemplates, RefreshDatabase;

    private const CREATE_URL = 'qris.interactive.co.id/restapi/qris/show_qris.php*';

    private const CHECK_URL = 'qris.interactive.co.id/restapi/qris/checkpaid_qris.php*';

    private ?Template $template = null;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'kartukita.payment.qris.driver' => 'interactive',
            'kartukita.payment.qris.api_key' => 'kunci-rahasia',
            'kartukita.payment.qris.merchant_id' => '123456',
            'kartukita.payment.qris.webhook_token' => 'token-webhook-rahasia',
        ]);
    }

    public function test_checkout_creates_a_dynamic_qris_with_the_exact_order_amount(): void
    {
        $this->freezeSecond();
        Http::fake([self::CREATE_URL => $this->invoiceResponse('413255111', now()->setTimezone('Asia/Jakarta'))]);
        $greeting = Greeting::factory()->for($this->createTemplate(price: 35000))->create(['content' => $this->completeContent()]);

        $this->actingAs($greeting->user)->post(route('checkout.store', $greeting), ['confirm_data' => true])->assertRedirect();

        $order = Order::sole();
        $payment = $order->latestPayment;
        $this->assertSame('413255111', $payment->provider_invoice_id);
        $this->assertSame(PaymentStatus::Menunggu, $payment->status);
        $this->assertTrue($payment->expires_at->equalTo(now()->addMinutes(30)));
        Http::assertSent(fn (Request $request) => $request['do'] === 'create-invoice'
            && $request['apikey'] === 'kunci-rahasia'
            && $request['mID'] === '123456'
            && (int) $request['cliTrxAmount'] === 35000
            && $request['cliTrxNumber'] === "{$order->code}-1"
            && $request['useTip'] === 'no');

        $this->actingAs($greeting->user)->get(route('orders.show', $order))->assertInertia(fn (Assert $page) => $page
            ->component('Orders/Show')
            ->where('order.qris.is_scannable', true)
            ->where('order.qris.svg', fn (string $svg) => str_contains($svg, '<svg'))
            ->where('isQrisReady', true)
            ->where('canSimulate', false));
    }

    public function test_webhook_is_only_trusted_after_the_status_api_confirms_payment(): void
    {
        $payment = $this->pendingQrisPayment();
        Http::fake([self::CHECK_URL => Http::sequence()
            ->push(['status' => 'failed', 'data' => ['qris_status' => 'unpaid']])
            ->push(['status' => 'success', 'data' => ['qris_status' => 'paid', 'qris_payment_customername' => 'EDWIN PERDANA', 'qris_payment_methodby' => 'BCA']])]);

        $this->postJson(route('webhooks.qris', 'token-webhook-rahasia'), $this->webhookPayload($payment))->assertOk();
        $this->assertSame(OrderStatus::Menunggu, $payment->order->refresh()->status);

        $this->postJson(route('webhooks.qris', 'token-webhook-rahasia'), $this->webhookPayload($payment))->assertOk();

        $order = $payment->order->refresh();
        $this->assertSame(OrderStatus::Lunas, $order->status);
        $this->assertSame(GreetingStatus::Terbit, $order->greeting->status);
        $this->assertSame(PaymentStatus::Diterima, $payment->refresh()->status);
        $this->assertSame('EDWIN PERDANA', $payment->payer_name);
        $this->assertSame('BCA', $payment->payer_method);
        Http::assertSent(fn (Request $request) => $request['do'] === 'checkStatus' && $request['invid'] === $payment->provider_invoice_id && (int) $request['trxvalue'] === $payment->amount);
    }

    public function test_duplicate_webhooks_publish_the_card_only_once(): void
    {
        $payment = $this->pendingQrisPayment();
        Http::fake([self::CHECK_URL => $this->paidResponse()]);

        $this->postJson(route('webhooks.qris', 'token-webhook-rahasia'), $this->webhookPayload($payment))->assertOk();
        $this->postJson(route('webhooks.qris', 'token-webhook-rahasia'), $this->webhookPayload($payment))->assertOk();

        $this->assertDatabaseCount('greeting_snapshots', 1);
        Http::assertSentCount(1);
    }

    public function test_webhook_with_a_wrong_token_or_unknown_invoice_changes_nothing(): void
    {
        $payment = $this->pendingQrisPayment();
        Http::fake();

        $this->postJson(route('webhooks.qris', 'tebakan'), $this->webhookPayload($payment))->assertNotFound();
        $this->postJson(route('webhooks.qris', 'token-webhook-rahasia'), ['invoice' => '999', 'qris_status' => 'success'])->assertOk();

        Http::assertNothingSent();
        $this->assertSame(OrderStatus::Menunggu, $payment->order->refresh()->status);
    }

    public function test_scheduled_check_settles_paid_qris_within_the_providers_polling_limits(): void
    {
        $eligible = $this->pendingQrisPayment(['created_at' => now()->subMinutes(2)]);
        $checkedJustNow = $this->pendingQrisPayment(['created_at' => now()->subMinutes(2), 'last_checked_at' => now()->subSeconds(10)]);
        $outOfChecks = $this->pendingQrisPayment(['created_at' => now()->subMinutes(2), 'status_checks' => 30]);
        Http::fake([self::CHECK_URL => $this->paidResponse()]);

        $this->artisan('kartukita:check-qris-payments')->assertSuccessful();

        $this->assertSame(OrderStatus::Lunas, $eligible->order->refresh()->status);
        $this->assertSame(OrderStatus::Menunggu, $checkedJustNow->order->refresh()->status);
        $this->assertSame(OrderStatus::Menunggu, $outOfChecks->order->refresh()->status);
        Http::assertSentCount(1);
    }

    public function test_expired_qr_can_be_replaced_with_a_new_invoice(): void
    {
        $payment = $this->pendingQrisPayment(['expires_at' => now()->subMinute()]);
        Http::fake([self::CREATE_URL => $this->invoiceResponse('500000002', now()->setTimezone('Asia/Jakarta'))]);

        $this->actingAs($payment->order->user)->post(route('orders.qris.refresh', $payment->order))->assertRedirect(route('orders.show', $payment->order));

        $this->assertSame(PaymentStatus::Kedaluwarsa, $payment->refresh()->status);
        $this->assertSame('500000002', $payment->order->latestPayment->provider_invoice_id);
        Http::assertSent(fn (Request $request) => $request['cliTrxNumber'] === "{$payment->order->code}-2");
    }

    public function test_payment_made_right_before_expiry_is_still_honoured(): void
    {
        $payment = $this->pendingQrisPayment(['status' => PaymentStatus::Kedaluwarsa, 'expires_at' => now()->subMinutes(2)]);
        Http::fake([self::CHECK_URL => $this->paidResponse()]);

        $this->postJson(route('webhooks.qris', 'token-webhook-rahasia'), $this->webhookPayload($payment))->assertOk();

        $this->assertSame(OrderStatus::Lunas, $payment->order->refresh()->status);
    }

    public function test_second_payment_for_an_already_paid_card_is_flagged_for_refund(): void
    {
        $first = $this->pendingQrisPayment();
        $second = $first->order->payments()->create([
            'method' => Payment::METHOD_QRIS, 'amount' => $first->amount, 'provider_invoice_id' => '77777', 'qris_content' => 'X',
            'requested_at' => now(), 'expires_at' => now()->addMinutes(30), 'status' => PaymentStatus::Menunggu,
        ]);
        Http::fake([self::CHECK_URL => $this->paidResponse()]);

        $this->postJson(route('webhooks.qris', 'token-webhook-rahasia'), $this->webhookPayload($first))->assertOk();
        $this->postJson(route('webhooks.qris', 'token-webhook-rahasia'), $this->webhookPayload($second))->assertOk();

        $this->assertDatabaseCount('greeting_snapshots', 1);
        $this->assertStringContainsString('Perlu pengembalian dana', $second->refresh()->review_note);
    }

    public function test_i_have_paid_button_is_rate_limited_to_protect_the_api_key(): void
    {
        $payment = $this->pendingQrisPayment();
        Http::fake([self::CHECK_URL => ['status' => 'failed', 'data' => ['qris_status' => 'unpaid']]]);
        $owner = $payment->order->user;

        $this->actingAs($owner)->post(route('orders.qris.check', $payment->order))->assertSessionHas('error', fn (string $message) => str_contains($message, 'belum masuk'));
        $this->actingAs($owner)->post(route('orders.qris.check', $payment->order))->assertSessionHas('error', fn (string $message) => str_contains($message, 'Tunggu'));

        Http::assertSentCount(1);
    }

    public function test_paid_check_sends_the_buyer_to_their_published_card(): void
    {
        $payment = $this->pendingQrisPayment();
        Http::fake([self::CHECK_URL => $this->paidResponse()]);

        $this->actingAs($payment->order->user)
            ->post(route('orders.qris.check', $payment->order))
            ->assertRedirect(route('result.show', $payment->order->greeting));
    }

    public function test_buyer_can_download_the_qr_image_but_others_cannot(): void
    {
        $payment = $this->pendingQrisPayment();

        $this->actingAs($payment->order->user)
            ->get(route('orders.qris.image', $payment->order))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        $this->actingAs(Greeting::factory()->create()->user)->get(route('orders.qris.image', $payment->order))->assertNotFound();
    }

    public function test_simulated_payment_is_only_available_with_the_fake_driver(): void
    {
        $payment = $this->pendingQrisPayment();

        $this->actingAs($payment->order->user)->post(route('orders.qris.simulate', $payment->order))->assertNotFound();

        config(['kartukita.payment.qris.driver' => 'fake']);
        $payment->update(['provider_invoice_id' => 'FAKE123']);

        $this->actingAs($payment->order->user)
            ->post(route('orders.qris.simulate', $payment->order))
            ->assertRedirect(route('result.show', $payment->order->greeting));
        $this->assertSame(OrderStatus::Lunas, $payment->order->refresh()->status);
    }

    public function test_order_is_kept_when_qris_is_not_configured_yet(): void
    {
        config(['kartukita.payment.qris.api_key' => null]);
        Http::fake();
        $greeting = Greeting::factory()->for($this->createTemplate())->create(['content' => $this->completeContent()]);

        $this->actingAs($greeting->user)->post(route('checkout.store', $greeting), ['confirm_data' => true])->assertSessionHas('error');

        $this->actingAs($greeting->user)->get(route('orders.show', Order::sole()))->assertInertia(fn (Assert $page) => $page->where('isQrisReady', false));
        Http::assertNothingSent();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function pendingQrisPayment(array $attributes = []): Payment
    {
        $greeting = Greeting::factory()
            ->for($this->template ??= $this->createTemplate())
            ->status(GreetingStatus::MenungguBayar)
            ->create(['content' => $this->completeContent()]);
        $order = Order::factory()->for($greeting)->for($greeting->user)->create(['amount' => 29000]);

        $payment = $order->payments()->create([
            'method' => Payment::METHOD_QRIS,
            'amount' => 29000,
            'provider_invoice_id' => (string) fake()->unique()->numberBetween(100000, 999999),
            'qris_content' => '00020101021226670016COM.NOBUSERVICES.WWW',
            'requested_at' => now(),
            'expires_at' => now()->addMinutes(30),
            'status' => PaymentStatus::Menunggu,
        ]);

        $payment->forceFill($attributes)->save();

        return $payment->refresh();
    }

    private function invoiceResponse(string $invoiceId, \DateTimeInterface $requestedAt): array
    {
        return ['status' => 'success', 'data' => [
            'qris_content' => '00020101021226670016COM.NOBUSERVICES.WWW011893600503',
            'qris_request_date' => $requestedAt->format('Y-m-d H:i:s'),
            'qris_invoiceid' => $invoiceId,
            'qris_nmid' => 'ID1020021181745',
        ]];
    }

    private function paidResponse(): array
    {
        return ['status' => 'success', 'data' => ['qris_status' => 'paid', 'qris_payment_customername' => 'PEMBELI', 'qris_payment_methodby' => 'DANA']];
    }

    private function webhookPayload(Payment $payment): array
    {
        return ['rrn' => '000380970875', 'invoice' => $payment->provider_invoice_id, 'qris_status' => 'success', 'qris_paid_date' => now()->format('Y-m-d H:i:s')];
    }
}
