<?php

namespace Tests\Feature;

use App\Actions\Orders\StartManualPayment;
use App\Enums\GreetingStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Greeting;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Template;
use App\Models\User;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\ManualPaymentClaimed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesGreetingTemplates;
use Tests\TestCase;

/**
 * QRIS statis: nominal unik, unggah bukti bayar, notifikasi admin (foto bukti ke Telegram), dan konfirmasi satu klik.
 */
class ManualPaymentTest extends TestCase
{
    use CreatesGreetingTemplates, RefreshDatabase;

    private ?Template $template = null;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'kartukita.payment.mode' => 'manual',
            // Gambar yang pasti ada di public/ sebagai pengganti QRIS statis merchant.
            'kartukita.payment.manual.qris_image' => 'images/samples/buket.svg',
            'kartukita.payment.manual.merchant_name' => 'KartuKita Official',
            'services.telegram.bot_token' => null,
            'services.telegram.chat_id' => null,
            'kartukita.media.disk' => 'local',
        ]);

        Storage::fake('local');
    }

    public function test_checkout_creates_unique_amount_without_calling_the_qris_api(): void
    {
        Http::preventStrayRequests();
        $greeting = Greeting::factory()->for($this->createTemplate(29000))->create(['content' => $this->completeContent()]);

        $this->actingAs($greeting->user)->post(route('checkout.store', $greeting), ['confirm_data' => true]);

        $order = Order::sole();
        $payment = $order->latestPayment;
        $this->assertSame(Payment::METHOD_MANUAL_QRIS, $payment->method);
        $this->assertGreaterThan(29000, $payment->amount);
        $this->assertLessThanOrEqual(29000 + 499, $payment->amount);

        $this->get(route('orders.show', $order))
            ->assertInertia(fn (Assert $page) => $page
                ->where('order.manual.amount', $payment->amount)
                ->where('order.manual.unique_code', $payment->amount - 29000)
                ->where('order.manual.merchant_name', 'KartuKita Official')
                ->where('isQrisReady', true));
    }

    public function test_open_orders_with_the_same_price_get_different_amounts(): void
    {
        $first = app(StartManualPayment::class)->handle($this->pendingOrder());
        $second = app(StartManualPayment::class)->handle($this->pendingOrder());

        $this->assertNotSame($first->amount, $second->amount);
    }

    public function test_claim_moves_order_to_review_and_notifies_only_admins(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $order = $this->pendingOrderWithManualPayment();

        $this->actingAs($order->user)
            ->post(route('orders.manual.claim', $order), $this->proof())
            ->assertRedirect(route('orders.show', $order));

        $this->assertSame(OrderStatus::MenungguKonfirmasi, $order->refresh()->status);
        $this->assertSame(GreetingStatus::Terkunci, $order->greeting->refresh()->status);
        Storage::disk('local')->assertExists($order->latestPayment->proof_path);
        $this->assertSame('Budi Santoso', $order->latestPayment->sender_account_name);
        Notification::assertSentTo($admin, ManualPaymentClaimed::class);
        Notification::assertNotSentTo($order->user, ManualPaymentClaimed::class);
        Notification::assertSentOnDemandTimes(ManualPaymentClaimed::class, 0);
    }

    public function test_claim_also_pings_telegram_when_configured(): void
    {
        Notification::fake();
        config(['services.telegram.bot_token' => 'bot-token', 'services.telegram.chat_id' => '12345']);
        $order = $this->pendingOrderWithManualPayment();

        $this->actingAs($order->user)->post(route('orders.manual.claim', $order), $this->proof());

        Notification::assertSentOnDemand(
            ManualPaymentClaimed::class,
            fn (ManualPaymentClaimed $notification, array $channels, object $notifiable) => $notifiable->routes[TelegramChannel::class] === '12345',
        );
    }

    public function test_claim_without_proof_is_rejected_with_a_clear_message(): void
    {
        Notification::fake();
        $order = $this->pendingOrderWithManualPayment();

        $this->actingAs($order->user)
            ->post(route('orders.manual.claim', $order), [])
            ->assertSessionHasErrors(['proof' => 'Unggah bukti pembayaran (screenshot atau foto struk) dulu, ya.']);

        $this->assertSame(OrderStatus::Menunggu, $order->refresh()->status);
        Notification::assertNothingSent();
    }

    public function test_telegram_sends_the_proof_as_a_photo_with_caption(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        config(['services.telegram.bot_token' => 'bot-token', 'services.telegram.chat_id' => '12345']);
        $payment = $this->pendingOrderWithManualPayment()->latestPayment;
        Storage::disk('local')->put('payments/bukti.jpg', 'isi-gambar');
        $payment->update(['proof_disk' => 'local', 'proof_path' => 'payments/bukti.jpg', 'sender_account_name' => 'Budi Santoso']);

        Notification::route(TelegramChannel::class, '12345')->notify(new ManualPaymentClaimed($payment));

        Http::assertSent(function (Request $request) use ($payment): bool {
            // Permintaan multipart: setiap field adalah bagian bernama, bukan pasangan kunci-nilai.
            $parts = collect($request->data())->pluck('contents', 'name');

            return $request->url() === 'https://api.telegram.org/botbot-token/sendPhoto'
                && $parts['chat_id'] === '12345'
                && $parts['photo'] === 'isi-gambar'
                && str_contains($parts['caption'], 'Rp'.number_format($payment->amount, 0, ',', '.'))
                && str_contains($parts['caption'], 'Budi Santoso');
        });
    }

    public function test_telegram_falls_back_to_text_message_without_proof(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        config(['services.telegram.bot_token' => 'bot-token', 'services.telegram.chat_id' => '12345']);
        $payment = $this->pendingOrderWithManualPayment()->latestPayment;

        Notification::route(TelegramChannel::class, '12345')->notify(new ManualPaymentClaimed($payment));

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.telegram.org/botbot-token/sendMessage'
            && $request['chat_id'] === '12345'
            && str_contains($request['text'], 'Rp'.number_format($payment->amount, 0, ',', '.'))
            && str_contains($request['text'], route('admin.orders.show', $payment->order)));
    }

    public function test_buyer_cannot_claim_someone_elses_order_or_claim_twice(): void
    {
        Notification::fake();
        $order = $this->pendingOrderWithManualPayment();

        $this->actingAs(User::factory()->create())->post(route('orders.manual.claim', $order), $this->proof())->assertNotFound();

        $this->actingAs($order->user)->post(route('orders.manual.claim', $order), $this->proof());
        $this->actingAs($order->user)
            ->post(route('orders.manual.claim', $order), $this->proof())
            ->assertSessionHasErrors(['order' => 'Pesanan ini tidak sedang menunggu pembayaran.']);
    }

    public function test_admin_confirmation_publishes_and_buyer_page_moves_to_the_result(): void
    {
        Notification::fake();
        $order = $this->pendingOrderWithManualPayment();
        $this->actingAs($order->user)->post(route('orders.manual.claim', $order), $this->proof());

        $this->actingAs(User::factory()->admin()->create())->post(route('admin.orders.confirm', $order));

        $this->assertSame(OrderStatus::Lunas, $order->refresh()->status);
        $this->assertSame(GreetingStatus::Terbit, $order->greeting->refresh()->status);
        $this->assertNotNull($order->latestPayment->paid_at);

        $this->actingAs($order->user)
            ->get(route('orders.show', $order))
            ->assertRedirect(route('result.show', $order->greeting));
    }

    public function test_rejected_claim_gives_a_new_amount_and_shows_the_reason(): void
    {
        Notification::fake();
        $order = $this->pendingOrderWithManualPayment();
        $firstAmount = $order->latestPayment->amount;
        $this->actingAs($order->user)->post(route('orders.manual.claim', $order), $this->proof());

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.orders.reject', $order), ['note' => 'Dana belum masuk']);

        $this->actingAs($order->user)
            ->get(route('orders.show', $order))
            ->assertInertia(fn (Assert $page) => $page
                ->where('order.status', 'menunggu')
                ->where('order.rejection_note', 'Dana belum masuk')
                ->where('order.manual.amount', fn (int $amount) => $amount !== $firstAmount));

        $this->assertSame(2, $order->payments()->count());
        $this->assertSame(PaymentStatus::Ditolak, $order->payments()->oldest('id')->first()->status);
    }

    public function test_whatsapp_mode_points_buyer_to_admin_chat_with_order_details(): void
    {
        config(['kartukita.payment.manual.proof_channel' => 'whatsapp', 'kartukita.support_whatsapp' => '0812-9999-8888']);
        $order = $this->pendingOrderWithManualPayment();
        $amount = $order->latestPayment->amount;

        $this->actingAs($order->user)
            ->get(route('orders.show', $order))
            ->assertInertia(fn (Assert $page) => $page
                ->where('order.manual.proof_channel', 'whatsapp')
                ->where('order.manual.whatsapp_url', fn (string $url) => str_starts_with($url, 'https://wa.me/6281299998888?text=')
                    && str_contains(rawurldecode($url), $order->code)
                    && str_contains(rawurldecode($url), 'Rp'.number_format($amount, 0, ',', '.'))));
    }

    public function test_whatsapp_mode_claims_without_upload_and_tells_admin_to_check_whatsapp(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        config([
            'kartukita.payment.manual.proof_channel' => 'whatsapp',
            'kartukita.support_whatsapp' => '081299998888',
            'services.telegram.bot_token' => 'bot-token',
            'services.telegram.chat_id' => '12345',
        ]);
        $order = $this->pendingOrderWithManualPayment();
        $order->user->update(['phone' => '081211112222']);

        $this->actingAs($order->user)->post(route('orders.manual.claim', $order))->assertSessionHasNoErrors();

        $this->assertSame(OrderStatus::MenungguKonfirmasi, $order->refresh()->status);
        $this->assertNull($order->latestPayment->proof_path);
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/sendMessage')
            && str_contains($request['text'], 'Bukti dikirim lewat WhatsApp dari 081211112222')
            && str_contains($request['reply_markup'], "pay:ok:{$order->code}"));

        $this->get(route('orders.show', $order))
            ->assertInertia(fn (Assert $page) => $page->where('order.review_whatsapp_url', fn (?string $url) => str_starts_with((string) $url, 'https://wa.me/6281299998888')));
    }

    public function test_whatsapp_mode_without_admin_number_falls_back_to_upload(): void
    {
        config(['kartukita.payment.manual.proof_channel' => 'whatsapp', 'kartukita.support_whatsapp' => null]);
        $order = $this->pendingOrderWithManualPayment();

        $this->actingAs($order->user)
            ->post(route('orders.manual.claim', $order), [])
            ->assertSessionHasErrors('proof');

        $this->get(route('orders.show', $order))
            ->assertInertia(fn (Assert $page) => $page->where('order.manual.proof_channel', 'upload'));
    }

    public function test_payment_is_not_ready_until_the_static_qris_image_exists(): void
    {
        config(['kartukita.payment.manual.qris_image' => 'images/tidak-ada.png']);
        $order = $this->pendingOrder();

        $this->actingAs($order->user)
            ->get(route('orders.show', $order))
            ->assertInertia(fn (Assert $page) => $page->where('isQrisReady', false)->where('order.manual', null));
    }

    /**
     * @return array{proof: UploadedFile, sender_account_name: string}
     */
    private function proof(): array
    {
        return ['proof' => UploadedFile::fake()->image('bukti.jpg'), 'sender_account_name' => 'Budi Santoso'];
    }

    private function pendingOrder(): Order
    {
        $greeting = Greeting::factory()
            ->for($this->template ??= $this->createTemplate())
            ->status(GreetingStatus::MenungguBayar)
            ->create(['content' => $this->completeContent()]);

        return Order::factory()->for($greeting)->create();
    }

    private function pendingOrderWithManualPayment(): Order
    {
        $order = $this->pendingOrder();
        app(StartManualPayment::class)->handle($order);

        return $order;
    }
}
