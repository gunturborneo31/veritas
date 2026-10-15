<?php

namespace App\Notifications;

use App\Models\PromoCode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Teman yang diajak sudah membayar — pemilik referral mendapat voucher.
 */
class ReferralRewarded extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public PromoCode $reward) {}

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
            ->subject("Kamu dapat voucher {$this->reward->describe()} 🎁")
            ->greeting("Halo {$notifiable->name}!")
            ->line("Temanmu baru saja memakai kode referral-mu. Sebagai terima kasih, ini voucher {$this->reward->describe()}: {$this->reward->code}")
            ->line('Berlaku sampai '.$this->reward->ends_at?->translatedFormat('j F Y').'.')
            ->action('Buat Ucapan Baru', route('catalog.index'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'icon' => 'redeem',
            'title' => "Voucher {$this->reward->describe()} untukmu: {$this->reward->code}",
            'body' => 'Temanmu memakai kode referral-mu. Pakai vouchernya di pesanan berikutnya.',
            'greeting_id' => null,
        ];
    }
}
