<?php

namespace App\Console\Commands;

use App\Actions\Orders\SettleQrisPayment;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('kartukita:check-qris-payments')]
#[Description('Cek status QRIS yang belum lunas (cadangan bila webhook tidak sampai) dan tandai QR yang kedaluwarsa.')]
class CheckQrisPayments extends Command
{
    /**
     * Dijalankan tiap menit. Sesuai panduan InterActive: tiap invoice dicek paling sering sekali per ±60 detik
     * dan maksimal `max_status_checks` kali, supaya API key tidak diblokir.
     */
    public function handle(SettleQrisPayment $settle): int
    {
        $maxChecks = config('kartukita.payment.qris.max_status_checks');
        $settled = 0;

        Payment::query()
            ->where('method', Payment::METHOD_QRIS)
            ->where('status', PaymentStatus::Menunggu)
            ->whereNotNull('provider_invoice_id')
            ->where('status_checks', '<', $maxChecks)
            ->where('created_at', '<=', now()->subMinute())
            ->where(fn ($query) => $query->whereNull('last_checked_at')->orWhere('last_checked_at', '<=', now()->subSeconds(55)))
            ->orderBy('id')
            ->limit(50)
            ->get()
            ->each(function (Payment $payment) use ($settle, &$settled): void {
                if ($settle->handle($payment)) {
                    $settled++;
                }
            });

        // QR yang sudah lewat masa berlaku dan habis jatah pengecekannya tidak akan dibayar lagi.
        $expired = Payment::query()
            ->where('method', Payment::METHOD_QRIS)
            ->where('status', PaymentStatus::Menunggu)
            ->where('expires_at', '<', now())
            ->where(fn ($query) => $query->where('status_checks', '>=', $maxChecks)->orWhere('expires_at', '<', now()->subHour()))
            ->update(['status' => PaymentStatus::Kedaluwarsa]);

        $this->info("{$settled} pembayaran QRIS lunas, {$expired} QR kedaluwarsa.");

        return self::SUCCESS;
    }
}
