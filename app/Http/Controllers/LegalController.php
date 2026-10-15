<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * Kebijakan Privasi dan Syarat & Ketentuan. Isi teks ada di halaman React;
 * di sini hanya data yang bergantung pada konfigurasi.
 */
class LegalController extends Controller
{
    public function privacy(): Response
    {
        return Inertia::render('Legal/Privacy', $this->shared());
    }

    public function terms(): Response
    {
        return Inertia::render('Legal/Terms', $this->shared());
    }

    /**
     * @return array<string, mixed>
     */
    private function shared(): array
    {
        return [
            'supportEmail' => config('kartukita.support_email'),
            'supportWhatsapp' => config('kartukita.support_whatsapp'),
            'draftRetentionDays' => config('kartukita.draft_retention_days'),
            'orderExpiresHours' => config('kartukita.payment.order_expires_hours'),
            'updatedAt' => config('kartukita.legal_updated_at'),
        ];
    }
}
