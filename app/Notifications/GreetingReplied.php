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
        $mail = (new MailMessage)
            ->subject("Balasan dari {$this->greeting->recipient_name} 💬")
            ->greeting("Halo {$notifiable->name}!");

        if (filled($this->reply->message)) {
            $mail->line("“{$this->reply->message}”");
        }

        if ($this->attachmentLabel() !== null) {
            $mail->line("Balasan ini disertai {$this->attachmentLabel()}. Buka Kartu Saya untuk melihatnya.");
        }

        return $mail
            ->line('— '.($this->reply->sender_name ?: $this->greeting->recipient_name))
            ->action('Buka Kartu Saya', route('dashboard'));
    }

    private function attachmentLabel(): ?string
    {
        return match ($this->reply->attachment_type) {
            Reply::ATTACHMENT_IMAGE => 'foto',
            Reply::ATTACHMENT_VIDEO => 'video',
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'icon' => 'forum',
            'title' => "{$this->greeting->recipient_name} membalas ucapanmu",
            'body' => filled($this->reply->message)
                ? Str::limit($this->reply->message, 90).($this->attachmentLabel() ? " (+ {$this->attachmentLabel()})" : '')
                : 'Mengirim '.$this->attachmentLabel().' 📎',
            'greeting_id' => $this->greeting->public_id,
        ];
    }
}
