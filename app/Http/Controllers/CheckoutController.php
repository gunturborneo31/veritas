<?php

namespace App\Http\Controllers;

use App\Actions\Orders\PlaceOrder;
use App\Enums\GreetingStatus;
use App\Enums\MediaKind;
use App\Http\Requests\ApplyPromoRequest;
use App\Http\Requests\PlaceOrderRequest;
use App\Models\Greeting;
use App\Models\PromoCode;
use App\Support\Greetings\ContentSchema;
use App\Support\Greetings\GreetingOwnership;
use App\Support\Pricing\PriceCalculator;
use App\Support\Pricing\PromoCodes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function __construct(private GreetingOwnership $ownership) {}

    /**
     * Langkah 5: ringkasan, konfirmasi wajib, dan metode bayar. Wajib login.
     */
    public function show(Request $request, Greeting $greeting, ContentSchema $schema, PriceCalculator $prices, PromoCodes $promoCodes): Response|RedirectResponse
    {
        $this->ownership->authorize($request, $greeting);

        if (! $greeting->isEditable()) {
            return $greeting->status === GreetingStatus::Terbit
                ? to_route('result.show', $greeting)
                : to_route('dashboard');
        }

        $greeting->load('template.category', 'gate', 'media');

        return Inertia::render('Checkout/Show', [
            'greeting' => [
                'id' => $greeting->public_id,
                'recipient_name' => $greeting->recipient_name,
                'sender_name' => $greeting->sender_name,
                'occasion' => $greeting->occasion,
                'photo_count' => $greeting->media->where('kind', MediaKind::Image)->count(),
                'has_voice' => $greeting->media->contains('kind', MediaKind::Audio),
                'has_gate' => $greeting->gate !== null,
                'opens_at' => $greeting->opens_at?->toIso8601String(),
            ],
            'template' => $greeting->template->toCardArray(),
            'quote' => $prices->quote($greeting, $this->appliedPromo($request, $greeting, $promoCodes))->toArray(),
            'referralHint' => $request->session()->has('referral_greeting_id'),
            'missing' => $schema->missingFields($greeting->template->manifest(), $greeting),
            'paymentMethods' => collect(config('kartukita.payment.methods'))
                ->map(fn (array $method, string $key): array => ['key' => $key, 'label' => $method['label']])
                ->values(),
        ]);
    }

    public function store(PlaceOrderRequest $request, Greeting $greeting, PlaceOrder $placeOrder): RedirectResponse
    {
        $this->ownership->authorize($request, $greeting);

        $order = $placeOrder->handle($greeting, $request->user(), $request->session()->get($this->promoSessionKey($greeting)));
        $request->session()->forget($this->promoSessionKey($greeting));

        return to_route('orders.show', $order);
    }

    /**
     * Terapkan kode promo/referral/voucher. Kode divalidasi sekarang dan sekali lagi saat pesanan dibuat.
     */
    public function applyPromo(ApplyPromoRequest $request, Greeting $greeting, PromoCodes $promoCodes): RedirectResponse
    {
        $this->ownership->authorize($request, $greeting);

        $promo = $promoCodes->resolve($request->validated('promo_code'), $request->user());
        $request->session()->put($this->promoSessionKey($greeting), $promo->code);

        return back()->with('success', "Kode {$promo->code} dipakai — hemat {$promo->describe()} 🎉");
    }

    public function removePromo(Request $request, Greeting $greeting): RedirectResponse
    {
        $this->ownership->authorize($request, $greeting);
        $request->session()->forget($this->promoSessionKey($greeting));

        return back();
    }

    private function appliedPromo(Request $request, Greeting $greeting, PromoCodes $promoCodes): ?PromoCode
    {
        $code = $request->session()->get($this->promoSessionKey($greeting));

        if ($code === null) {
            return null;
        }

        try {
            return $promoCodes->resolve($code, $request->user());
        } catch (ValidationException) {
            $request->session()->forget($this->promoSessionKey($greeting));

            return null;
        }
    }

    private function promoSessionKey(Greeting $greeting): string
    {
        return "checkout.promo.{$greeting->id}";
    }
}
