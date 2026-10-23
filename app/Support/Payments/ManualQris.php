<?php

namespace App\Support\Payments;

use App\Models\Payment;

/**
 * Pembayaran QRIS statis (manual). Merchant cukup menaruh gambar QRIS statisnya di public/;
 * pencocokan pembayaran memakai nominal unik dan konfirmasi satu klik oleh admin.
 * Bukti bayar dikirim lewat web (unggah) atau, sementara, lewat WhatsApp admin.
 */
class ManualQris
{
    public const PROOF_UPLOAD = 'upload';

    public const PROOF_WHATSAPP = 'whatsapp';

    public function isEnabled(): bool
    {
        return config('kartukita.payment.mode') === 'manual';
    }

    /**
     * Siap dipakai bila gambar QRIS statis sudah diletakkan di public/.
     */
    public function isConfigured(): bool
    {
        return is_file(public_path($this->imagePath()));
    }

    public function imageUrl(): string
    {
        return asset($this->imagePath());
    }

    public function merchantName(): string
    {
        return (string) config('kartukita.payment.manual.merchant_name');
    }

    /**
     * Mode WhatsApp hanya berlaku bila nomor admin sudah diisi; selain itu bukti diunggah di web.
     */
    public function proofChannel(): string
    {
        return config('kartukita.payment.manual.proof_channel') === self::PROOF_WHATSAPP && $this->adminWhatsapp() !== null
            ? self::PROOF_WHATSAPP
            : self::PROOF_UPLOAD;
    }

    /**
     * Link wa.me ke admin berisi pesan yang sudah menyebut kode pesanan dan nominal unik.
     */
    public function whatsappUrl(Payment $payment): ?string
    {
        $number = $this->adminWhatsapp();

        if ($number === null) {
            return null;
        }

        $amount = 'Rp'.number_format($payment->amount, 0, ',', '.');
        $text = "Halo admin, saya sudah membayar pesanan {$payment->order->code} sebesar {$amount}. Berikut bukti pembayarannya:";

        return "https://wa.me/{$number}?text=".rawurlencode($text);
    }

    /**
     * Nomor WhatsApp admin dalam format internasional tanpa "+" (08xx → 628xx) sesuai syarat wa.me.
     */
    public function adminWhatsapp(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) config('kartukita.support_whatsapp'));

        if ($digits === '') {
            return null;
        }

        return str_starts_with($digits, '0') ? '62'.substr($digits, 1) : $digits;
    }

    private function imagePath(): string
    {
        return ltrim((string) config('kartukita.payment.manual.qris_image'), '/');
    }
}
