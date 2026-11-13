<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use App\Support\WhatsApp\OrderWhatsAppNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmPayment
{
    /** Hasil kirim WA terakhir (null = tidak dikirim karena pembeli tidak mengisi nomor). */
    public ?bool $whatsappSent = null;

    public function __construct(
        private FulfillOrder $fulfillOrder,
        private OrderWhatsAppNotifier $whatsapp,
    ) {}

    /**
     * Admin menyatakan pembayaran sah: QRIS statis (nominal unik cocok dengan mutasi) atau bukti
     * transfer pesanan lama. Pesanan lunas, kartu langsung terbit, lalu pembeli dikabari lewat
     * WhatsApp (bila mengisi nomor) setelah transaksi tersimpan.
     */
    public function handle(Order $order, User $admin): Order
    {
        $order = $this->settle($order, $admin);
        $this->whatsappSent = $this->whatsapp->paymentConfirmed($order);

        return $order;
    }

    private function settle(Order $order, User $admin): Order
    {
        return DB::transaction(function () use ($order, $admin): Order {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->status !== OrderStatus::MenungguKonfirmasi) {
                throw ValidationException::withMessages(['order' => 'Pesanan ini tidak sedang menunggu konfirmasi.']);
            }

            $order->latestPayment->update([
                'status' => PaymentStatus::Diterima,
                'paid_at' => $order->latestPayment->paid_at ?? now(),
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            return $this->fulfillOrder->handle($order);
        });
    }
}
