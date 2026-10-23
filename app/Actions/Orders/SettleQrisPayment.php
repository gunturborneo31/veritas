<?php

namespace App\Actions\Orders;

use App\Enums\GreetingStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Support\Payments\QrisException;
use App\Support\Payments\QrisGateway;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SettleQrisPayment
{
    public function __construct(
        private QrisGateway $gateway,
        private FulfillOrder $fulfillOrder,
    ) {}

    /**
     * Tanyakan status ke InterActive dan, bila sudah dibayar, luluskan pesanan & terbitkan kartu.
     * Satu-satunya jalan menuju lunas otomatis — webhook hanya memicu fungsi ini, tidak pernah dipercaya langsung.
     * Aman dipanggil berkali-kali (webhook ganda, cron, tombol "cek").
     *
     * @return bool apakah pembayaran ini sudah lunas
     */
    public function handle(Payment $payment): bool
    {
        if (! $payment->isQris()) {
            return false;
        }

        if ($payment->status !== PaymentStatus::Menunggu && $payment->status !== PaymentStatus::Kedaluwarsa) {
            return $payment->status === PaymentStatus::Diterima;
        }

        $payment->forceFill(['status_checks' => $payment->status_checks + 1, 'last_checked_at' => now()])->save();

        try {
            $result = $this->gateway->checkStatus($payment->provider_invoice_id, $payment->amount, $payment->requested_at);
        } catch (QrisException) {
            return false;
        }

        if (! $result['paid']) {
            return false;
        }

        DB::transaction(function () use ($payment, $result): void {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->status === PaymentStatus::Diterima) {
                return;
            }

            $payment->update([
                'status' => PaymentStatus::Diterima,
                'paid_at' => now(),
                'payer_name' => $result['payer_name'],
                'payer_method' => $result['payer_method'],
            ]);

            $order = $payment->order()->lockForUpdate()->firstOrFail();

            if ($order->status === OrderStatus::Lunas || $order->greeting->status === GreetingStatus::Terbit) {
                // Uang masuk dua kali untuk kartu yang sama (mis. dua QR dipindai). Catat untuk pengembalian dana manual.
                $payment->update(['review_note' => 'Pembayaran ganda — kartu sudah lunas lewat pembayaran lain. Perlu pengembalian dana.']);
                report(new RuntimeException("Pembayaran QRIS ganda untuk pesanan {$order->code} (invoice {$payment->provider_invoice_id})."));

                return;
            }

            $this->fulfillOrder->handle($order);
        });

        return true;
    }
}
