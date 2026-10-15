<?php

namespace App\Notifications;

use App\Models\Greeting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * "Sarah baru saja membuka kartumu" — dikirim hanya pada bukaan pertama.
 */
class GreetingOpened extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Greeting $greeting) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->greeting->recipient_name} membuka kartumu 💌")
            ->greeting("Halo {$notifiable->name}!")
            ->line("{$this->greeting->recipient_name} baru saja membuka ucapan {$this->greeting->occasion} darimu.")
            ->action('Lihat di Kartu Saya', route('dashboard'))
            ->line('Kalau dia membalas atau memberi reaksi, kami kabari lagi.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'icon' => 'drafts',
            'title' => "{$this->greeting->recipient_name} membuka kartumu",
            'body' => "Ucapan {$this->greeting->occasion} sudah sampai di hatinya.",
            'greeting_id' => $this->greeting->public_id,
        ];
    }
}
