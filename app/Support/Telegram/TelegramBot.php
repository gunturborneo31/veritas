<?php

namespace App\Support\Telegram;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Klien tipis Telegram Bot API untuk notifikasi admin dan tombol aksinya.
 *
 * @see https://core.telegram.org/bots/api
 */
class TelegramBot
{
    /**
     * Batas panjang keterangan foto di Telegram.
     */
    public const CAPTION_LIMIT = 1024;

    public function isConfigured(): bool
    {
        return filled(config('services.telegram.bot_token'));
    }

    /**
     * Panggil satu metode Bot API. Kegagalan dilaporkan, bukan dilempar, agar notifikasi tidak
     * pernah menggagalkan alur pembayaran.
     *
     * @param  array<string, mixed>  $params
     * @param  array{contents: string, filename: string}|null  $photo
     * @return array<string, mixed>|null
     */
    public function call(string $method, array $params = [], ?array $photo = null, int $timeout = 15): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $url = 'https://api.telegram.org/bot'.config('services.telegram.bot_token')."/{$method}";
        $params = array_map(fn (mixed $value): string => is_array($value) ? json_encode($value) : (string) $value, array_filter($params, fn (mixed $value): bool => $value !== null));

        try {
            $response = $photo === null
                ? Http::timeout($timeout)->asForm()->post($url, $params)
                : Http::timeout($timeout)->attach('photo', $photo['contents'], $photo['filename'])->post($url, $params);
        } catch (ConnectionException $exception) {
            report($exception);

            return null;
        }

        if ($response->failed() || $response->json('ok') !== true) {
            report(new RuntimeException("Telegram {$method} gagal (HTTP {$response->status()}): ".$response->body()));

            return $response->json() ?: null;
        }

        return $response->json();
    }

    /**
     * Susun inline keyboard. Tombol URL hanya diterima Telegram bila https, jadi tombol URL lain dibuang.
     *
     * @param  list<list<array{text: string, url?: string, callback_data?: string}>>  $rows
     * @return array{inline_keyboard: list<list<array<string, string>>>}
     */
    public static function keyboard(array $rows): array
    {
        $rows = array_map(
            fn (array $row): array => array_values(array_filter($row, fn (array $button): bool => ! isset($button['url']) || str_starts_with($button['url'], 'https://'))),
            $rows,
        );

        return ['inline_keyboard' => array_values(array_filter($rows))];
    }

    public function answerCallback(string $callbackId, string $text, bool $asAlert = false): void
    {
        $this->call('answerCallbackQuery', ['callback_query_id' => $callbackId, 'text' => $text, 'show_alert' => $asAlert ? 'true' : 'false']);
    }

    /**
     * Ganti teks/keterangan pesan (foto memakai caption) sekaligus tombolnya.
     *
     * @param  array<string, mixed>  $message
     * @param  list<list<array<string, string>>>  $rows
     */
    public function editMessage(array $message, string $text, array $rows = []): void
    {
        $isPhoto = array_key_exists('caption', $message) || array_key_exists('photo', $message);

        $this->call($isPhoto ? 'editMessageCaption' : 'editMessageText', [
            'chat_id' => data_get($message, 'chat.id'),
            'message_id' => $message['message_id'] ?? null,
            $isPhoto ? 'caption' : 'text' => mb_substr($text, 0, $isPhoto ? self::CAPTION_LIMIT : 4096),
            'reply_markup' => self::keyboard($rows),
        ]);
    }

    /**
     * @param  array<string, mixed>  $message
     * @param  list<list<array<string, string>>>  $rows
     */
    public function editKeyboard(array $message, array $rows): void
    {
        $this->call('editMessageReplyMarkup', [
            'chat_id' => data_get($message, 'chat.id'),
            'message_id' => $message['message_id'] ?? null,
            'reply_markup' => self::keyboard($rows),
        ]);
    }
}
