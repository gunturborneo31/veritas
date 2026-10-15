<?php

namespace App\Http\Controllers;

use App\Enums\GreetingStatus;
use App\Models\Greeting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResultController extends Controller
{
    /**
     * Hasil setelah lunas: link unik, QR Code, dan tombol bagikan WhatsApp.
     */
    public function show(Request $request, Greeting $greeting): Response|RedirectResponse
    {
        abort_unless($greeting->user_id === $request->user()->id, 404);

        if ($greeting->status !== GreetingStatus::Terbit) {
            return to_route('dashboard');
        }

        $greeting->load('template', 'latestOrder');
        $url = route('recipient.show', $greeting->slug);

        return Inertia::render('Result/Show', [
            'greeting' => [
                'id' => $greeting->public_id,
                'recipient_name' => $greeting->recipient_name,
                'occasion' => $greeting->occasion,
                'published_at' => $greeting->published_at->toIso8601String(),
                'opens_at' => $greeting->opens_at?->toIso8601String(),
                'url' => $url,
                'whatsapp_text' => "Hai {$greeting->recipient_name}! Ada ucapan spesial buat kamu, buka di sini ya: {$url}",
            ],
            'template' => $greeting->template->toCardArray(),
            'orderCode' => $greeting->latestOrder?->code,
        ]);
    }
}
