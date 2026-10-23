<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Notifications\Channels\TelegramChannel;
use App\Support\Telegram\PaymentReviewActions;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Pembeli mengunggah bukti bayar QRIS statis. Admin cukup mencocokkan nominal unik di mutasi
 * merchant (bukti ikut dikirim sebagai foto di Telegram) lalu menekan Konfirmasi.
 */
class ManualPaymentClaimed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payment $payment) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable instanceof AnonymousNotifiable ? [TelegramChannel::class] : ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->payment->order;

        return (new MailMessage)
            ->subject("💸 Cek pembayaran {$this->amount()} • {$order->code}")
            ->greeting('Ada bukti pembayaran yang perlu dikonfirmasi')
            ->line("{$order->user->name} menyatakan sudah membayar **{$this->amount()}** (kode unik {$this->payment->uniqueCode()}) untuk pesanan {$order->code}.")
            ->line($this->payment->proof_path === null ? 'Bukti dikirim lewat WhatsApp'.(filled($order->user->phone) ? " dari {$order->user->phone}" : '').'.' : 'Bukti pembayaran terlampir di halaman pesanan.')
            ->line('Cocokkan nominal itu dengan mutasi di dashboard merchant QRIS, lalu konfirmasi.')
            ->action('Lihat Bukti & Konfirmasi', route('admin.orders.show', $order));
    }

    /**
     * Foto bukti + tombol "✅ Sudah bayar" / "❌ Belum" yang langsung memproses pesanan.
     *
     * @return array{text: string, url: string, buttons: list<list<array<string, string>>>, photo: array{disk: string, path: string}|null}
     */
    public function toTelegram(object $notifiable): array
    {
        $order = $this->payment->order;

        $lines = [
            '💸 Bukti pembayaran masuk',
            '',
            "Nominal: {$this->amount()} (kode unik {$this->payment->uniqueCode()})",
            "Pesanan: {$order->code}",
            "Pembeli: {$order->user->name}",
            ...(filled($this->payment->sender_account_name) ? ["A.n. pengirim: {$this->payment->sender_account_name}"] : []),
            "Produk: {$order->greeting->template->name}",
            ...($this->payment->proof_path === null ? ['', '📱 Bukti dikirim lewat WhatsApp'.(filled($order->user->phone) ? " dari {$order->user->phone}" : '').'.'] : []),
            '',
            'Cocokkan dengan mutasi QRIS, lalu pilih tombol di bawah.',
        ];

        return [
            'text' => implode("\n", $lines),
            'url' => route('admin.orders.show', $order),
            'buttons' => PaymentReviewActions::reviewKeyboard($order),
            'photo' => $this->payment->proof_path === null ? null : ['disk' => $this->payment->proof_disk, 'path' => $this->payment->proof_path],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $order = $this->payment->order;

        return [
            'icon' => 'payments',
            'title' => "Bukti bayar {$this->amount()} masuk",
            'body' => "{$order->code} • {$order->user->name}",
            'url' => route('admin.orders.show', $order),
        ];
    }

    private function amount(): string
    {
        return 'Rp'.number_format($this->payment->amount, 0, ',', '.');
    }
}
