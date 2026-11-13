<?php

namespace App\Support\WhatsApp;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Kirim pesan WhatsApp lewat Fonnte (api.fonnte.com). Tidak pernah melempar error: kegagalan
 * kirim WA tidak boleh menggagalkan konfirmasi pembayaran, cukup dicatat di log.
 */
class FonnteGateway
{
    public function isConfigured(): bool
    {
        return filled(config('services.fonnte.token'));
    }

    public function send(string $phone, string $message): bool
    {
        if (! $this->isConfigured()) {
            Log::info('WhatsApp (Fonnte belum diatur, tidak dikirim)', ['to' => $phone, 'message' => $message]);

            return false;
        }

        try {
            $response = Http::asForm()
                ->withHeaders(['Authorization' => config('services.fonnte.token')])
                ->timeout(10)
                ->post(config('services.fonnte.url'), [
                    'target' => $phone,
                    'message' => $message,
                    'countryCode' => '62',
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('WhatsApp gagal terkirim: tidak bisa terhubung ke Fonnte', ['to' => $phone, 'error' => $exception->getMessage()]);

            return false;
        }

        if (! $response->successful() || $response->json('status') !== true) {
            Log::warning('WhatsApp gagal terkirim lewat Fonnte', ['to' => $phone, 'response' => $response->json() ?? $response->body()]);

            return false;
        }

        return true;
    }
}
