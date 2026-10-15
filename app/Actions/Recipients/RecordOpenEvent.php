<?php

namespace App\Actions\Recipients;

use App\Models\Greeting;
use App\Notifications\GreetingOpened;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RecordOpenEvent
{
    /**
     * Pola user agent bot pratinjau tautan (WhatsApp, Telegram, dsb.).
     */
    private const BOT_PATTERN = '/bot|crawl|spider|preview|facebookexternalhit|whatsapp|telegram|slack|discord|embedly|skype/i';

    /**
     * Dipanggil hanya dari aksi eksplisit "Buka". Sesi pemilik dan bot diabaikan.
     */
    public function handle(Greeting $greeting, Request $request): void
    {
        if ($request->user()?->id === $greeting->user_id) {
            return;
        }

        $userAgent = (string) $request->userAgent();

        if ($userAgent === '' || preg_match(self::BOT_PATTERN, $userAgent) === 1) {
            return;
        }

        $isFirstOpen = ! $greeting->openEvents()->exists();

        $greeting->openEvents()->create([
            'visitor_hash' => hash('sha256', $request->ip().'|'.$userAgent),
            'user_agent' => Str::limit($userAgent, 250, ''),
            'opened_at' => now(),
        ]);

        if ($isFirstOpen) {
            $greeting->user?->notify(new GreetingOpened($greeting));
        }
    }
}
