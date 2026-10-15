<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Untuk admin: ada bukti transfer baru yang perlu diverifikasi.
 */
class PaymentProofSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = 'Rp'.number_format($this->order->amount, 0, ',', '.');

        return (new MailMessage)
            ->subject("Bukti bayar baru: {$this->order->code} ({$amount})")
            ->greeting("Halo {$notifiable->name}!")
            ->line("Pesanan {$this->order->code} sebesar {$amount} menunggu verifikasi.")
            ->action('Periksa Pesanan', route('admin.orders.show', $this->order));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'icon' => 'receipt_long',
            'title' => "Bukti bayar baru: {$this->order->code}",
            'body' => 'Rp'.number_format($this->order->amount, 0, ',', '.').' menunggu verifikasi.',
            'greeting_id' => null,
            'url' => route('admin.orders.show', $this->order),
        ];
    }
}
