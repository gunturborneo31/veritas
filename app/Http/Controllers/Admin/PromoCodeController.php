<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PromoKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PromoCodeRequest;
use App\Models\Order;
use App\Models\PromoCode;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Kode promo umum (kampanye). Kode referral & voucher hadiah dibuat otomatis oleh sistem.
 */
class PromoCodeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Promos/Index', [
            'promos' => PromoCode::query()->where('kind', PromoKind::Promo)->latest()->get(),
            'referralStats' => [
                'codes' => PromoCode::query()->where('kind', PromoKind::Referral)->count(),
                'uses' => (int) PromoCode::query()->where('kind', PromoKind::Referral)->sum('uses_count'),
                'rewards' => PromoCode::query()->where('kind', PromoKind::Reward)->count(),
            ],
        ]);
    }

    /**
     * Daftar pesanan yang memakai kode ini (semua status), terbaru di atas.
     */
    public function show(PromoCode $promoCode): Response
    {
        abort_unless($promoCode->kind === PromoKind::Promo, 404);

        $orders = $promoCode->orders()
            ->with('user:id,name,email,phone', 'greeting:id,recipient_name,occasion,type,template_id', 'greeting.template:id,name')
            ->latest()
            ->get();

        return Inertia::render('Admin/Promos/Show', [
            'promo' => [
                ...$promoCode->only(['id', 'code', 'label', 'discount_type', 'discount_value', 'max_uses', 'uses_count', 'is_active', 'starts_at', 'ends_at']),
                'description' => $promoCode->describe(),
            ],
            'summary' => [
                'orders' => $orders->count(),
                'paid' => $orders->where('status', OrderStatus::Lunas)->count(),
                'total_discount' => (int) $orders->where('status', OrderStatus::Lunas)->sum('discount'),
                'total_revenue' => (int) $orders->where('status', OrderStatus::Lunas)->sum('amount'),
            ],
            'orders' => $orders->map(fn (Order $order): array => [
                'code' => $order->code,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'amount' => $order->amount,
                'discount' => $order->discount,
                'customer' => $order->user?->only(['name', 'email', 'phone']),
                'recipient_name' => $order->greeting?->recipient_name,
                'occasion' => $order->greeting?->occasion,
                'template_name' => $order->greeting?->template?->name,
                'created_at' => $order->created_at->toIso8601String(),
                'paid_at' => $order->paid_at?->toIso8601String(),
            ]),
        ]);
    }

    public function store(PromoCodeRequest $request): RedirectResponse
    {
        PromoCode::create([...$request->validated(), 'kind' => PromoKind::Promo]);

        return back()->with('success', 'Kode promo dibuat.');
    }

    public function update(PromoCodeRequest $request, PromoCode $promoCode): RedirectResponse
    {
        abort_unless($promoCode->kind === PromoKind::Promo, 404);

        $promoCode->update($request->validated());

        return back()->with('success', 'Kode promo diperbarui.');
    }

    /**
     * Pesanan yang pernah memakai kode ini tetap menyimpan nilai potongannya (promo_code_id menjadi null).
     */
    public function destroy(PromoCode $promoCode): RedirectResponse
    {
        abort_unless($promoCode->kind === PromoKind::Promo, 404);

        $promoCode->delete();

        return back()->with('success', "Kode {$promoCode->code} dihapus.");
    }
}
