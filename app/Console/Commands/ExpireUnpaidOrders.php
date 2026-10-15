<?php

namespace App\Console\Commands;

use App\Enums\GreetingStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('kartukita:expire-orders')]
#[Description('Batalkan pesanan yang tidak dibayar melewati batas waktu dan kembalikan ucapannya ke draf.')]
class ExpireUnpaidOrders extends Command
{
    public function handle(): int
    {
        $deadline = now()->subHours(config('kartukita.payment.order_expires_hours'));
        $expired = 0;

        Order::query()
            ->with('greeting')
            ->where('status', OrderStatus::Menunggu)
            ->where('created_at', '<', $deadline)
            ->lazyById()
            ->each(function (Order $order) use (&$expired): void {
                DB::transaction(function () use ($order): void {
                    $order->update(['status' => OrderStatus::Batal, 'cancelled_at' => now()]);

                    if ($order->greeting->status === GreetingStatus::MenungguBayar) {
                        $order->greeting->forceFill(['status' => GreetingStatus::Draf])->save();
                    }
                });

                $expired++;
            });

        $this->info("{$expired} pesanan kedaluwarsa dibatalkan.");

        return self::SUCCESS;
    }
}
