<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PromoKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PromoCodeRequest;
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
}
