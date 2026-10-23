<?php

namespace App\Http\Controllers;

use App\Actions\Orders\PlaceOrder;
use App\Actions\Orders\StartManualPayment;
use App\Actions\Orders\StartQrisPayment;
use App\Enums\GreetingStatus;
use App\Enums\MediaKind;
use App\Http\Requests\ApplyPromoRequest;
use App\Http\Requests\PlaceOrderRequest;
use App\Models\Greeting;
use App\Models\PromoCode;
use App\Models\User;
use App\Support\Greetings\ContentSchema;
use App\Support\Greetings\CustomSlug;
use App\Support\Greetings\GreetingOwnership;
use App\Support\Payments\ManualQris;
use App\Support\Payments\QrisException;
use App\Support\Pricing\PriceCalculator;
use App\Support\Pricing\PromoCodes;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function __construct(private GreetingOwnership $ownership) {}

    /**
     * Langkah 5: ringkasan, konfirmasi wajib, dan metode bayar. Pembeli yang belum login tidak
     * dilempar ke halaman masuk: ia mengisi data akun di sini dan akunnya dibuat saat membayar.
     */
    public function show(Request $request, Greeting $greeting, ContentSchema $schema, PriceCalculator $prices, PromoCodes $promoCodes, ManualQris $manualQris): Response|RedirectResponse
    {
        $this->ownership->authorize($request, $greeting);

        if ($request->user() === null) {
            // Bila pembeli memilih "Masuk" karena sudah punya akun, ia kembali ke sini setelah login.
            $request->session()->put('url.intended', route('checkout.show', $greeting));
        }

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
            'needsAccount' => $request->user() === null,
            'link' => [
                'slug' => $greeting->slug,
                'prefix' => url('/u').'/',
                'suggestion' => CustomSlug::normalize($greeting->isInvitation() ? $greeting->sender_name : "untuk-{$greeting->recipient_name}"),
            ],
            'isManualPayment' => $manualQris->isEnabled(),
        ]);
    }

    /**
     * Buat pesanan lalu langsung siapkan QRIS-nya. Bila layanan QRIS sedang gangguan, pesanan tetap tersimpan
     * dan pembeli bisa menekan "Buat QR" lagi di halaman pesanan.
     */
    public function store(
        PlaceOrderRequest $request,
        Greeting $greeting,
        PlaceOrder $placeOrder,
        StartQrisPayment $startQris,
        ManualQris $manualQris,
        StartManualPayment $startManual,
        ContentSchema $schema,
    ): RedirectResponse {
        $this->ownership->authorize($request, $greeting);

        if ($request->user() === null) {
            $this->registerBuyer($request, $greeting, $schema);
            $greeting->refresh();
        }

        if ($request->filled('slug')) {
            // Dipesan sekarang, dipakai saat terbit (PublishGreeting tidak menimpa slug yang sudah ada).
            $greeting->forceFill(['slug' => $request->validated('slug')])->save();
        }

        $order = $placeOrder->handle($greeting, $request->user(), $request->session()->get($this->promoSessionKey($greeting)));
        $request->session()->forget($this->promoSessionKey($greeting));

        if ($manualQris->isEnabled()) {
            $startManual->handle($order);

            return to_route('orders.show', $order);
        }

        try {
            $startQris->handle($order);
        } catch (QrisException $exception) {
            return to_route('orders.show', $order)->with('error', $exception->getMessage());
        }

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

    /**
     * Buat akun pembeli dari data checkout lalu login otomatis. Draf anonim di perangkat ini ikut
     * dipindahkan ke akun lewat listener ClaimAnonymousDrafts (event Registered).
     * Kelengkapan kartu dicek dulu agar akun tidak dibuat untuk pesanan yang pasti gagal.
     */
    private function registerBuyer(PlaceOrderRequest $request, Greeting $greeting, ContentSchema $schema): void
    {
        $missing = $schema->missingFields($greeting->template->manifest(), $greeting);

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'greeting' => 'Lengkapi dulu: '.collect($missing)->pluck('label')->join(', ').'.',
            ]);
        }

        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'password' => Hash::make($request->validated('password')),
        ]);

        event(new Registered($user));
        Auth::login($user, remember: true);
        $request->session()->regenerate();
    }

    private function appliedPromo(Request $request, Greeting $greeting, PromoCodes $promoCodes): ?PromoCode
    {
        $code = $request->session()->get($this->promoSessionKey($greeting));

        if ($code === null || $request->user() === null) {
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
