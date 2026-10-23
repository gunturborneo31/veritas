<?php

namespace App\Notifications\Channels;

use App\Support\Telegram\TelegramBot;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * Kirim notifikasi ke chat Telegram admin. Notifikasi menyediakan `toTelegram()` berisi teks,
 * URL tujuan, tombol (URL atau aksi), dan (opsional) foto dari disk penyimpanan — mis. bukti bayar.
 * Foto dikirim dengan sendPhoto (teks menjadi keterangannya); tanpa foto memakai sendMessage.
 */
class TelegramChannel
{
    public function __construct(private TelegramBot $bot) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $chatId = $notifiable->routeNotificationFor(self::class, $notification);

        if (! $this->bot->isConfigured() || blank($chatId) || ! method_exists($notification, 'toTelegram')) {
            return;
        }

        /** @var array{text: string, url: string, buttons?: list<list<array<string, string>>>, photo?: array{disk: string, path: string}|null} $message */
        $message = $notification->toTelegram($notifiable);
        $text = $message['text'];

        // Tombol URL hanya diterima bila https; di lokal tautannya ditulis di teks.
        if (! str_starts_with($message['url'], 'https://')) {
            $text .= "\n\n{$message['url']}";
        }

        $params = ['chat_id' => $chatId, 'reply_markup' => TelegramBot::keyboard($message['buttons'] ?? [])];
        $photo = $this->readPhoto($message['photo'] ?? null);

        if ($photo === null) {
            $this->bot->call('sendMessage', [...$params, 'text' => $text, 'disable_web_page_preview' => 'true']);

            return;
        }

        $this->bot->call('sendPhoto', [...$params, 'caption' => mb_substr($text, 0, TelegramBot::CAPTION_LIMIT)], $photo, timeout: 30);
    }

    /**
     * @param  array{disk: string, path: string}|null  $photo
     * @return array{contents: string, filename: string}|null
     */
    private function readPhoto(?array $photo): ?array
    {
        if ($photo === null || ! Storage::disk($photo['disk'])->exists($photo['path'])) {
            return null;
        }

        return ['contents' => Storage::disk($photo['disk'])->get($photo['path']), 'filename' => basename($photo['path'])];
    }
}
