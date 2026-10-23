<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmPayment
{
    public function __construct(private FulfillOrder $fulfillOrder) {}

    /**
     * Admin menyatakan pembayaran sah: QRIS statis (nominal unik cocok dengan mutasi) atau bukti
     * transfer pesanan lama. Pesanan lunas dan kartu langsung terbit.
     */
    public function handle(Order $order, User $admin): Order
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
