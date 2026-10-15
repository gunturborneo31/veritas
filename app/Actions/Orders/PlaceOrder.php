<?php

namespace App\Actions\Orders;

use App\Enums\GreetingStatus;
use App\Enums\OrderStatus;
use App\Exceptions\GreetingLockedException;
use App\Models\Greeting;
use App\Models\Order;
use App\Models\User;
use App\Support\Greetings\ContentSchema;
use App\Support\Pricing\PriceCalculator;
use App\Support\Pricing\PromoCodes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PlaceOrder
{
    public function __construct(
        private PriceCalculator $prices,
        private ContentSchema $schema,
        private PromoCodes $promoCodes,
    ) {}

    /**
     * Buat pesanan dari draf. Harga dihitung ulang di server dan dikunci ke pesanan.
     * Satu ucapan hanya punya satu pesanan aktif.
     */
    public function handle(Greeting $greeting, User $user, ?string $promoCode = null): Order
    {
        if (! $greeting->isEditable()) {
            throw GreetingLockedException::forGreeting($greeting);
        }

        $greeting->loadMissing('template', 'gate');
        $missing = $this->schema->missingFields($greeting->template->manifest(), $greeting);

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'greeting' => 'Lengkapi dulu: '.collect($missing)->pluck('label')->join(', ').'.',
            ]);
        }

        $promo = blank($promoCode) ? null : $this->promoCodes->resolve($promoCode, $user);
        $quote = $this->prices->quote($greeting, $promo);

        return DB::transaction(function () use ($greeting, $user, $quote, $promo): Order {
            $greeting->orders()
                ->where('status', OrderStatus::Menunggu)
                ->update(['status' => OrderStatus::Batal, 'cancelled_at' => now()]);

            $greeting->forceFill([
                'user_id' => $user->id,
                'draft_token_hash' => null,
                'status' => GreetingStatus::MenungguBayar,
            ])->save();

            return $greeting->orders()->create([
                'code' => $this->generateCode(),
                'user_id' => $user->id,
                'promo_code_id' => $promo?->id,
                'discount' => $quote->discount,
                'amount' => $quote->total,
                'price_breakdown' => $quote->lines,
                'status' => OrderStatus::Menunggu,
                'data_confirmed_at' => now(),
            ]);
        });
    }

    private function generateCode(): string
    {
        do {
            $code = 'KK'.now()->format('ymd').Str::upper(Str::random(5));
        } while (Order::where('code', $code)->exists());

        return $code;
    }
}
