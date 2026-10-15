<?php

namespace App\Actions\Orders;

use App\Actions\Greetings\PublishGreeting;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PromoKind;
use App\Models\Order;
use App\Models\PromoCode;
use App\Models\User;
use App\Notifications\ReferralRewarded;
use App\Support\Pricing\PromoCodes;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmPayment
{
    public function __construct(
        private PublishGreeting $publishGreeting,
        private PromoCodes $promoCodes,
    ) {}

    /**
     * Admin menyatakan pembayaran sah: pesanan lunas, ucapan dibekukan ke snapshot dan diterbitkan.
     * Pemakaian kode promo baru dihitung di sini; pemilik kode referral mendapat voucher hadiah.
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
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            $order->update(['status' => OrderStatus::Lunas, 'paid_at' => now()]);

            $this->publishGreeting->handle($order->greeting);

            if ($order->promo_code_id !== null) {
                $this->recordPromoUse($order);
            }

            return $order;
        });
    }

    private function recordPromoUse(Order $order): void
    {
        $promo = PromoCode::query()->lockForUpdate()->findOrFail($order->promo_code_id);
        $promo->increment('uses_count');

        if ($promo->kind === PromoKind::Referral && $promo->owner !== null) {
            $reward = $this->promoCodes->issueReward($promo->owner);
            $promo->owner->notify(new ReferralRewarded($reward));
        }
    }
}
