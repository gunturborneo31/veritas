<?php

namespace App\Http\Controllers;

use App\Enums\GreetingStatus;
use App\Models\Greeting;
use App\Support\Greetings\QrCodeGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResultController extends Controller
{
    /**
     * Hasil setelah lunas: link unik, QR Code, dan tombol bagikan WhatsApp.
     */
    public function show(Request $request, Greeting $greeting, QrCodeGenerator $qr): Response|RedirectResponse
    {
        abort_unless($greeting->user_id === $request->user()->id, 404);

        if ($greeting->status !== GreetingStatus::Terbit) {
            return to_route('dashboard');
        }

        $greeting->load('template', 'latestOrder');
        $url = route('recipient.show', $greeting->slug);

        // Undangan disebar ke banyak orang: teks umumnya untuk grup, sedangkan link pribadi dikirim dari Kelola Undangan.
        $whatsappText = $greeting->isInvitation()
            ? "Tanpa mengurangi rasa hormat, kami mengundang Bapak/Ibu/Saudara/i untuk hadir di acara {$greeting->occasion} kami, {$greeting->sender_name}. Detail acara dan konfirmasi kehadiran: {$url}"
            : "Hai {$greeting->recipient_name}! Ada ucapan spesial buat kamu, buka di sini ya: {$url}";

        return Inertia::render('Result/Show', [
            'greeting' => [
                'id' => $greeting->public_id,
                'type' => $greeting->type->value,
                'sender_name' => $greeting->sender_name,
                'manage_url' => $greeting->isInvitation() ? route('invitations.show', $greeting) : null,
                'recipient_name' => $greeting->recipient_name,
                'occasion' => $greeting->occasion,
                'published_at' => $greeting->published_at->toIso8601String(),
                'opens_at' => $greeting->opens_at?->toIso8601String(),
                'url' => $url,
                'qr_svg' => $qr->svg($url, $greeting->template->resolvedTheme()['accent']),
                'qr_download_url' => route('result.qr', $greeting),
                'whatsapp_text' => $whatsappText,
            ],
            'template' => $greeting->template->toCardArray(),
            'orderCode' => $greeting->latestOrder?->code,
        ]);
    }
}
