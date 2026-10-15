<?php

namespace App\Actions\Orders;

use App\Enums\GreetingStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentProofSubmitted;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class SubmitPaymentProof
{
    /**
     * Simpan bukti transfer. Sejak titik ini isi ucapan dibekukan (status `terkunci`)
     * sambil menunggu verifikasi admin.
     */
    public function handle(Order $order, UploadedFile $proof, string $method, ?string $senderAccountName): Payment
    {
        if ($order->status !== OrderStatus::Menunggu) {
            throw ValidationException::withMessages(['proof' => 'Pesanan ini tidak sedang menunggu pembayaran.']);
        }

        $disk = config('kartukita.media.disk');
        $path = $proof->store("payments/{$order->code}", $disk);

        $payment = DB::transaction(function () use ($order, $method, $senderAccountName, $disk, $path): Payment {
            $payment = $order->payments()->create([
                'method' => $method,
                'amount' => $order->amount,
                'proof_disk' => $disk,
                'proof_path' => $path,
                'sender_account_name' => $senderAccountName,
                'status' => PaymentStatus::Menunggu,
            ]);

            $order->update(['status' => OrderStatus::MenungguKonfirmasi]);
            $order->greeting->forceFill(['status' => GreetingStatus::Terkunci])->save();

            return $payment;
        });

        Notification::send(User::query()->where('is_admin', true)->get(), new PaymentProofSubmitted($order));

        return $payment;
    }
}
