<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Support\Payments\QrisException;
use App\Support\Payments\QrisGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StartQrisPayment
{
    public function __construct(private QrisGateway $gateway) {}

    /**
     * Siapkan QR untuk pesanan. QR yang masih berlaku dipakai ulang; yang lewat masa berlaku ditandai kedaluwarsa
     * lalu dibuatkan QR baru dengan nomor transaksi berbeda.
     *
     * @throws QrisException
     */
    public function handle(Order $order): Payment
    {
        if ($order->status !== OrderStatus::Menunggu) {
            throw ValidationException::withMessages(['order' => 'Pesanan ini tidak sedang menunggu pembayaran.']);
        }

        if ($order->amount > config('kartukita.payment.qris.max_amount')) {
            throw ValidationException::withMessages(['order' => 'Nominal melebihi batas transaksi QRIS. Hubungi admin.']);
        }

        $active = $order->payments()
            ->where('method', Payment::METHOD_QRIS)
            ->where('status', PaymentStatus::Menunggu)
            ->where('expires_at', '>', now()->addMinute())
            ->latest('id')
            ->first();

        if ($active !== null) {
            return $active;
        }

        $attempt = $order->payments()->where('method', Payment::METHOD_QRIS)->count() + 1;
        $invoice = $this->gateway->createInvoice("{$order->code}-{$attempt}", $order->amount);

        return DB::transaction(function () use ($order, $invoice): Payment {
            $order->payments()
                ->where('method', Payment::METHOD_QRIS)
                ->where('status', PaymentStatus::Menunggu)
                ->where('expires_at', '<=', now()->addMinute())
                ->update(['status' => PaymentStatus::Kedaluwarsa]);

            return $order->payments()->create([
                'method' => Payment::METHOD_QRIS,
                'amount' => $order->amount,
                'provider_invoice_id' => $invoice['invoice_id'],
                'qris_content' => $invoice['content'],
                'requested_at' => $invoice['requested_at'],
                'expires_at' => $invoice['requested_at']->copy()->addMinutes(config('kartukita.payment.qris.expires_minutes')),
                'status' => PaymentStatus::Menunggu,
            ]);
        });
    }
}
