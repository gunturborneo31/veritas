<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class StartManualPayment
{
    /**
     * Siapkan pembayaran QRIS statis dengan nominal unik (harga + 1..499) agar admin bisa
     * mencocokkan mutasi tanpa bukti transfer. Pembayaran yang masih terbuka dipakai ulang;
     * setelah ditolak admin, pembeli mendapat nominal unik baru.
     */
    public function handle(Order $order): ?Payment
    {
        if ($order->status !== OrderStatus::Menunggu) {
            return null;
        }

        return DB::transaction(function () use ($order): Payment {
            $active = $order->payments()
                ->where('method', Payment::METHOD_MANUAL_QRIS)
                ->where('status', PaymentStatus::Menunggu)
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($active !== null) {
                return $active;
            }

            return $order->payments()->create([
                'method' => Payment::METHOD_MANUAL_QRIS,
                'amount' => $order->amount + $this->freeUniqueCode($order->amount),
                'status' => PaymentStatus::Menunggu,
            ]);
        });
    }

    /**
     * Kode unik yang belum dipakai pembayaran manual lain yang masih terbuka dengan harga yang sama.
     */
    private function freeUniqueCode(int $baseAmount): int
    {
        $max = (int) config('kartukita.payment.manual.unique_code_max');

        $taken = Payment::query()
            ->where('method', Payment::METHOD_MANUAL_QRIS)
            ->where('status', PaymentStatus::Menunggu)
            ->whereBetween('amount', [$baseAmount + 1, $baseAmount + $max])
            ->pluck('amount')
            ->map(fn (int $amount): int => $amount - $baseAmount)
            ->all();

        $available = array_values(array_diff(range(1, $max), $taken));

        // Semua kode terpakai (sangat jarang): tetap lanjut, admin mencocokkan lewat waktu & nama.
        return $available === [] ? random_int(1, $max) : $available[array_rand($available)];
    }
}
