<?php

namespace App\Notifications;

use App\Models\Greeting;
use App\Models\Reaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Respons penerima: reaksi emoji, jawaban "Satu Pertanyaan", atau video reaksi.
 * Jawaban dan video juga dikirim lewat email karena itulah momen yang paling ditunggu.
 */
class GreetingReacted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Greeting $greeting, public Reaction $reaction) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->reaction->kind === Reaction::KIND_EMOJI ? ['database'] : ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $this->greeting->recipient_name;

        return (new MailMessage)
            ->subject($this->reaction->kind === Reaction::KIND_VIDEO ? "{$name} mengirim video reaksi! 🎥" : "{$name} sudah menjawab! 💘")
            ->greeting("Halo {$notifiable->name}!")
            ->line($this->reaction->kind === Reaction::KIND_VIDEO
                ? "{$name} merekam reaksinya saat membuka ucapanmu. Tonton di Kartu Saya."
                : "Jawaban {$name}: “{$this->reaction->value}”")
            ->action('Lihat di Kartu Saya', route('dashboard'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $name = $this->greeting->recipient_name;

        [$icon, $title] = match ($this->reaction->kind) {
            Reaction::KIND_ANSWER => ['how_to_vote', "{$name} menjawab: {$this->reaction->value}"],
            Reaction::KIND_VIDEO => ['videocam', "{$name} mengirim video reaksi 🎥"],
            default => ['add_reaction', "{$name} bereaksi {$this->reaction->value}"],
        };

        return [
            'icon' => $icon,
            'title' => $title,
            'body' => "Pada ucapan {$this->greeting->occasion} darimu.",
            'greeting_id' => $this->greeting->public_id,
        ];
    }
}
