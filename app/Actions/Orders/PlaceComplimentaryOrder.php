<?php

namespace App\Actions\Orders;

use App\Models\Greeting;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaceComplimentaryOrder
{
    public function __construct(
        private PlaceOrder $placeOrder,
        private FulfillOrder $fulfillOrder,
    ) {}

    /**
     * Admin menerbitkan undangannya sendiri tanpa bayar. Tetap lewat pesanan (Rp0, langsung lunas)
     * agar kelengkapan isi dicek dan riwayat pesanan tercatat seperti pesanan biasa.
     */
    public function handle(Greeting $greeting, User $admin): Order
    {
        if (! $admin->is_admin || ! $greeting->isInvitation()) {
            throw ValidationException::withMessages(['greeting' => 'Terbit gratis hanya untuk undangan yang dibuat admin.']);
        }

        return DB::transaction(function () use ($greeting, $admin): Order {
            $order = $this->placeOrder->handle($greeting, $admin);

            $order->update([
                'discount' => $order->discount + $order->amount,
                'price_breakdown' => [...$order->price_breakdown, ['label' => 'Gratis (admin)', 'amount' => -$order->amount]],
                'amount' => 0,
            ]);

            return $this->fulfillOrder->handle($order);
        });
    }
}
