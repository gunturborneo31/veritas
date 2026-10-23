<?php

namespace Tests\Feature;

use App\Actions\Orders\StartManualPayment;
use App\Enums\GreetingStatus;
use App\Enums\OrderStatus;
use App\Models\Greeting;
use App\Models\Order;
use App\Models\User;
use App\Support\Telegram\PaymentReviewActions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\CreatesGreetingTemplates;
use Tests\TestCase;

/**
 * Verifikasi pembayaran manual langsung dari tombol di Telegram.
 */
class TelegramPaymentReviewTest extends TestCase
{
    use CreatesGreetingTemplates, RefreshDatabase;

    private const ADMIN_CHAT = '903439699';

    private const SECRET = 'rahasia-webhook';

    /**
     * Update yang dikembalikan Telegram untuk getUpdates (dipakai test perintah polling).
     *
     * @var list<array<string, mixed>>
     */
    private array $pendingUpdates = [];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'kartukita.payment.mode' => 'manual',
            'kartukita.payment.manual.qris_image' => 'images/samples/buket.svg',
            'services.telegram.bot_token' => 'bot-token',
            'services.telegram.chat_id' => self::ADMIN_CHAT,
            'services.telegram.webhook_secret' => self::SECRET,
        ]);

        Http::fake(fn (Request $request) => Http::response([
            'ok' => true,
            'result' => str_ends_with($request->url(), '/getUpdates') ? $this->pendingUpdates : [],
        ]));
    }

    public function test_webhook_without_the_secret_header_is_rejected(): void
    {
        $order = $this->orderAwaitingReview();

        $this->postJson(route('webhooks.telegram'), $this->tap('pay:ok', $order))->assertNotFound();

        $this->assertSame(OrderStatus::MenungguKonfirmasi, $order->refresh()->status);
    }

    public function test_paid_button_confirms_the_order_and_marks_the_telegram_message(): void
    {
        User::factory()->admin()->create();
        $order = $this->orderAwaitingReview();

        $this->webhook($this->tap('pay:ok', $order))->assertOk();

        $this->assertSame(OrderStatus::Lunas, $order->refresh()->status);
        $this->assertSame(GreetingStatus::Terbit, $order->greeting->refresh()->status);
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/answerCallbackQuery') && str_contains($request['text'], 'Dikonfirmasi'));
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/editMessageCaption')
            && str_contains($request['caption'], 'DIKONFIRMASI')
            && $request['reply_markup'] === json_encode(['inline_keyboard' => []]));
    }

    public function test_buttons_from_another_chat_are_ignored(): void
    {
        User::factory()->admin()->create();
        $order = $this->orderAwaitingReview();

        $this->webhook($this->tap('pay:ok', $order, chatId: '111'))->assertOk();

        $this->assertSame(OrderStatus::MenungguKonfirmasi, $order->refresh()->status);
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/answerCallbackQuery') && $request['show_alert'] === 'true');
    }

    public function test_not_paid_button_asks_for_confirmation_before_rejecting(): void
    {
        User::factory()->admin()->create();
        $order = $this->orderAwaitingReview();

        $this->webhook($this->tap('pay:no', $order));

        $this->assertSame(OrderStatus::MenungguKonfirmasi, $order->refresh()->status);
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/editMessageReplyMarkup')
            && str_contains($request['reply_markup'], "pay:reject:{$order->code}"));
    }

    public function test_confirmed_rejection_reopens_the_order_with_a_note_for_the_buyer(): void
    {
        User::factory()->admin()->create();
        $order = $this->orderAwaitingReview();

        $this->webhook($this->tap('pay:reject', $order));

        $this->assertSame(OrderStatus::Menunggu, $order->refresh()->status);
        $this->assertSame(PaymentReviewActions::REJECTION_NOTE, $order->latestPayment->review_note);
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/editMessageCaption') && str_contains($request['caption'], 'DITOLAK'));
    }

    public function test_second_tap_on_a_processed_order_only_reports_its_status(): void
    {
        User::factory()->admin()->create();
        $order = $this->orderAwaitingReview();
        $this->webhook($this->tap('pay:ok', $order));

        $this->webhook($this->tap('pay:reject', $order));

        $this->assertSame(OrderStatus::Lunas, $order->refresh()->status);
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/answerCallbackQuery') && str_contains($request['text'], 'sudah diproses: Lunas'));
    }

    public function test_poll_command_processes_button_taps_and_remembers_the_offset(): void
    {
        User::factory()->admin()->create();
        $order = $this->orderAwaitingReview();
        $this->pendingUpdates = [['update_id' => 41, ...$this->tap('pay:ok', $order)]];

        $this->artisan('kartukita:telegram-poll', ['--once' => true])->assertSuccessful();

        $this->assertSame(OrderStatus::Lunas, $order->refresh()->status);
        $this->assertSame(42, Cache::get('telegram:update_offset'));
    }

    private function orderAwaitingReview(): Order
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->status(GreetingStatus::Terkunci)->create(['content' => $this->completeContent()]);
        $order = Order::factory()->for($greeting)->create();
        app(StartManualPayment::class)->handle($order);
        $order->update(['status' => OrderStatus::MenungguKonfirmasi]);

        return $order;
    }

    /**
     * Update Telegram untuk ketukan tombol pada pesan foto bukti bayar.
     *
     * @return array<string, mixed>
     */
    private function tap(string $action, Order $order, string $chatId = self::ADMIN_CHAT): array
    {
        return ['callback_query' => [
            'id' => 'cb-1',
            'data' => "{$action}:{$order->code}",
            'message' => ['message_id' => 7, 'chat' => ['id' => (int) $chatId], 'caption' => '💸 Bukti pembayaran masuk', 'photo' => [['file_id' => 'x']]],
        ]];
    }

    private function webhook(array $update): TestResponse
    {
        return $this->postJson(route('webhooks.telegram'), $update, ['X-Telegram-Bot-Api-Secret-Token' => self::SECRET]);
    }
}
