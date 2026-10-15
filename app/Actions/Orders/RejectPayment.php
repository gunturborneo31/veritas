<?php

namespace App\Actions\Orders;

use App\Enums\GreetingStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectPayment
{
    /**
     * Bukti tidak valid: pembeli bisa mengunggah ulang, dan draf kembali bisa diedit.
     */
    public function handle(Order $order, User $admin, string $note): Order
    {
        return DB::transaction(function () use ($order, $admin, $note): Order {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->status !== OrderStatus::MenungguKonfirmasi) {
                throw ValidationException::withMessages(['order' => 'Pesanan ini tidak sedang menunggu konfirmasi.']);
            }

            $order->latestPayment->update([
                'status' => PaymentStatus::Ditolak,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ]);

            $order->update(['status' => OrderStatus::Menunggu]);
            $order->greeting->forceFill(['status' => GreetingStatus::MenungguBayar])->save();

            return $order;
        });
    }
}
