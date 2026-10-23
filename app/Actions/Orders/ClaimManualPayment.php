<?php

namespace App\Actions\Orders;

use App\Enums\GreetingStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\ManualPaymentClaimed;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClaimManualPayment
{
    /**
     * Pembeli menyatakan sudah bayar: bukti (bila diunggah) disimpan privat, pesanan menunggu
     * konfirmasi admin, isi kartu dikunci selama pemeriksaan, dan admin diberi tahu (dashboard,
     * email, dan Telegram beserta foto bukti). Tanpa unggahan berarti bukti dikirim lewat WhatsApp.
     */
    public function handle(Order $order, ?UploadedFile $proof = null, ?string $senderAccountName = null): Order
    {
        $disk = config('kartukita.media.disk');
        $path = $proof?->storeAs("payments/{$order->code}", Str::ulid()->toBase32().'.'.$proof->extension(), $disk);

        try {
            $order = DB::transaction(function () use ($order, $disk, $path, $senderAccountName): Order {
                $order = Order::query()->lockForUpdate()->findOrFail($order->id);
                $payment = $order->latestPayment;

                if ($order->status !== OrderStatus::Menunggu || $payment?->isManual() !== true || $payment->status !== PaymentStatus::Menunggu) {
                    throw ValidationException::withMessages(['order' => 'Pesanan ini tidak sedang menunggu pembayaran.']);
                }

                $payment->update([
                    'proof_disk' => $path === null ? null : $disk,
                    'proof_path' => $path,
                    'sender_account_name' => $senderAccountName,
                ]);

                $order->update(['status' => OrderStatus::MenungguKonfirmasi]);
                $order->greeting->forceFill(['status' => GreetingStatus::Terkunci])->save();

                return $order;
            });
        } catch (ValidationException $exception) {
            if ($path !== null) {
                Storage::disk($disk)->delete($path);
            }

            throw $exception;
        }

        $this->notifyAdmins($order->latestPayment);

        return $order;
    }

    private function notifyAdmins(Payment $payment): void
    {
        $notification = new ManualPaymentClaimed($payment);

        Notification::send(User::query()->where('is_admin', true)->get(), $notification);

        if (filled(config('services.telegram.bot_token')) && filled(config('services.telegram.chat_id'))) {
            Notification::route(TelegramChannel::class, config('services.telegram.chat_id'))->notify($notification);
        }
    }
}
