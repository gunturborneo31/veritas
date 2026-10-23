<?php

namespace App\Actions\Orders;

use App\Actions\Greetings\PublishGreeting;
use App\Enums\GreetingStatus;
use App\Enums\OrderStatus;
use App\Enums\PromoKind;
use App\Models\Order;
use App\Models\PromoCode;
use App\Notifications\ReferralRewarded;
use App\Support\Pricing\PromoCodes;

/**
 * Pesanan dinyatakan lunas: ucapan dibekukan ke snapshot dan diterbitkan, pemakaian promo dihitung,
 * dan pemilik kode referral mendapat voucher. Dipanggil di dalam transaksi oleh pemanggilnya
 * (pembayaran QRIS otomatis atau konfirmasi admin untuk pesanan transfer lama).
 */
class FulfillOrder
{
    public function __construct(
        private PublishGreeting $publishGreeting,
        private PromoCodes $promoCodes,
    ) {}

    public function handle(Order $order): Order
    {
        $order->update(['status' => OrderStatus::Lunas, 'paid_at' => now(), 'cancelled_at' => null]);

        // Pesanan lain untuk ucapan yang sama tidak relevan lagi.
        $order->greeting->orders()
            ->whereKeyNot($order->id)
            ->where('status', OrderStatus::Menunggu)
            ->update(['status' => OrderStatus::Batal, 'cancelled_at' => now()]);

        $greeting = $order->greeting;

        if ($greeting->status !== GreetingStatus::Terkunci) {
            $greeting->forceFill(['status' => GreetingStatus::Terkunci])->save();
        }

        $this->publishGreeting->handle($greeting);

        if ($order->promo_code_id !== null) {
            $this->recordPromoUse($order);
        }

        return $order;
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
