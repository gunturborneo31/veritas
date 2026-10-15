<?php

namespace App\Notifications;

use App\Models\Reminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Pengingat H-n momen orang tersayang, mengajak menyiapkan kejutan lebih awal.
 */
class MomentReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Reminder $reminder, public int $daysLeft) {}

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
            ->subject("{$this->reminder->occasion} {$this->reminder->person_name} {$this->when()} 🎁")
            ->greeting("Halo {$notifiable->name}!")
            ->line("{$this->reminder->occasion} {$this->reminder->person_name} tinggal {$this->when()}. Siapkan kejutan kecil yang bikin dia berhenti sejenak?")
            ->action('Pilih Template', route('catalog.index'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'icon' => 'event',
            'title' => "{$this->reminder->occasion} {$this->reminder->person_name} {$this->when()}",
            'body' => 'Siapkan kejutannya dari sekarang, biar tidak terburu-buru.',
            'greeting_id' => null,
        ];
    }

    private function when(): string
    {
        return $this->daysLeft === 0 ? 'hari ini' : "{$this->daysLeft} hari lagi";
    }
}
