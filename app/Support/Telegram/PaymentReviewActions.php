<?php

namespace App\Support\Telegram;

use App\Actions\Orders\ConfirmPayment;
use App\Actions\Orders\RejectPayment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Tombol verifikasi pembayaran di Telegram: "✅ Sudah bayar" langsung mengonfirmasi pesanan,
 * "❌ Belum" meminta penegasan dulu agar tidak salah ketuk. Hanya chat admin yang diatur
 * (services.telegram.chat_id) yang boleh memicu aksi.
 *
 * Data tombol berformat `pay:<aksi>:<kode pesanan>` (maks. 64 byte sesuai batas Telegram).
 */
class PaymentReviewActions
{
    public const REJECTION_NOTE = 'Pembayaran belum kami temukan. Pastikan nominal sudah sesuai (termasuk kode unik), lalu kirim bukti lagi.';

    public function __construct(
        private TelegramBot $bot,
        private ConfirmPayment $confirmPayment,
        private RejectPayment $rejectPayment,
    ) {}

    /**
     * Tombol yang menyertai notifikasi bukti bayar.
     *
     * @return list<list<array<string, string>>>
     */
    public static function reviewKeyboard(Order $order): array
    {
        return [
            [
                ['text' => '✅ Sudah bayar', 'callback_data' => "pay:ok:{$order->code}"],
                ['text' => '❌ Belum', 'callback_data' => "pay:no:{$order->code}"],
            ],
            [['text' => '🔎 Lihat di web', 'url' => route('admin.orders.show', $order)]],
        ];
    }

    /**
     * Proses satu update dari Telegram (webhook atau getUpdates). Update selain tombol diabaikan.
     *
     * @param  array<string, mixed>  $update
     */
    public function handle(array $update): void
    {
        $callback = $update['callback_query'] ?? null;

        if (! is_array($callback) || ! isset($callback['id'])) {
            return;
        }

        if ((string) data_get($callback, 'message.chat.id') !== (string) config('services.telegram.chat_id')) {
            $this->bot->answerCallback($callback['id'], 'Akun ini tidak berhak memverifikasi pembayaran.', asAlert: true);

            return;
        }

        [$prefix, $action, $code] = array_pad(explode(':', (string) ($callback['data'] ?? ''), 3), 3, null);
        $order = $prefix === 'pay' && $code !== null ? Order::query()->where('code', $code)->first() : null;
        $admin = User::query()->where('is_admin', true)->orderBy('id')->first();
        $message = $callback['message'];

        if ($order === null || $admin === null) {
            $this->bot->answerCallback($callback['id'], $order === null ? 'Pesanan tidak ditemukan.' : 'Belum ada akun admin di aplikasi.', asAlert: true);

            return;
        }

        match ($action) {
            'ok' => $this->confirm($callback['id'], $message, $order, $admin),
            'no' => $this->askToReject($callback['id'], $message, $order),
            'reject' => $this->reject($callback['id'], $message, $order, $admin),
            'back' => $this->restore($callback['id'], $message, $order),
            default => $this->bot->answerCallback($callback['id'], 'Aksi tidak dikenal.'),
        };
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function confirm(string $callbackId, array $message, Order $order, User $admin): void
    {
        try {
            $this->confirmPayment->handle($order, $admin);
        } catch (ValidationException) {
            $this->alreadyProcessed($callbackId, $message, $order);

            return;
        }

        $this->bot->answerCallback($callbackId, '✅ Dikonfirmasi — kartu pembeli sudah terbit.');
        $this->bot->editMessage($message, $this->originalText($message)."\n\n✅ DIKONFIRMASI • ".now()->format('d/m/Y H:i'));
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function askToReject(string $callbackId, array $message, Order $order): void
    {
        $this->bot->answerCallback($callbackId, 'Yakin pembayaran belum masuk?');
        $this->bot->editKeyboard($message, [[
            ['text' => '❌ Ya, tolak', 'callback_data' => "pay:reject:{$order->code}"],
            ['text' => '↩️ Batal', 'callback_data' => "pay:back:{$order->code}"],
        ]]);
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function reject(string $callbackId, array $message, Order $order, User $admin): void
    {
        try {
            $this->rejectPayment->handle($order, $admin, self::REJECTION_NOTE);
        } catch (ValidationException) {
            $this->alreadyProcessed($callbackId, $message, $order);

            return;
        }

        $this->bot->answerCallback($callbackId, '❌ Ditolak — pembeli diminta mengirim bukti lagi.');
        $this->bot->editMessage($message, $this->originalText($message)."\n\n❌ DITOLAK • ".now()->format('d/m/Y H:i'));
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function restore(string $callbackId, array $message, Order $order): void
    {
        $this->bot->answerCallback($callbackId, 'Dibatalkan.');
        $this->bot->editKeyboard($message, self::reviewKeyboard($order));
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function alreadyProcessed(string $callbackId, array $message, Order $order): void
    {
        $this->bot->answerCallback($callbackId, "Pesanan ini sudah diproses: {$order->refresh()->status->label()}.", asAlert: true);
        $this->bot->editKeyboard($message, []);
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function originalText(array $message): string
    {
        return (string) ($message['caption'] ?? $message['text'] ?? '');
    }
}
