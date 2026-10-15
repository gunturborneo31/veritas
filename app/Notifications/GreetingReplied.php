<?php

namespace App\Notifications;

use App\Models\Greeting;
use App\Models\Reply;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class GreetingReplied extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Greeting $greeting, public Reply $reply) {}

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
            ->subject("Balasan dari {$this->greeting->recipient_name} 💬")
            ->greeting("Halo {$notifiable->name}!")
            ->line("“{$this->reply->message}”")
            ->line('— '.($this->reply->sender_name ?: $this->greeting->recipient_name))
            ->action('Buka Kartu Saya', route('dashboard'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'icon' => 'forum',
            'title' => "{$this->greeting->recipient_name} membalas ucapanmu",
            'body' => Str::limit($this->reply->message, 90),
            'greeting_id' => $this->greeting->public_id,
        ];
    }
}
