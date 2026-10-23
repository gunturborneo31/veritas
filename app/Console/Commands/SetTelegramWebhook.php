<?php

namespace App\Console\Commands;

use App\Support\Telegram\TelegramBot;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('kartukita:telegram-webhook {--delete : Hapus webhook (kembali ke mode polling)}')]
#[Description('Daftarkan webhook Telegram agar tombol verifikasi pembayaran diproses seketika (butuh APP_URL https).')]
class SetTelegramWebhook extends Command
{
    public function handle(TelegramBot $bot): int
    {
        if ($this->option('delete')) {
            $response = $bot->call('deleteWebhook');
            $this->info(($response['ok'] ?? false) ? 'Webhook dihapus. Gunakan kartukita:telegram-poll.' : 'Gagal menghapus webhook.');

            return ($response['ok'] ?? false) ? self::SUCCESS : self::FAILURE;
        }

        $url = route('webhooks.telegram');

        if (! str_starts_with($url, 'https://') || blank(config('services.telegram.webhook_secret'))) {
            $this->error('Webhook butuh APP_URL https dan TELEGRAM_WEBHOOK_SECRET. Di lokal, pakai kartukita:telegram-poll.');

            return self::FAILURE;
        }

        $response = $bot->call('setWebhook', [
            'url' => $url,
            'secret_token' => config('services.telegram.webhook_secret'),
            'allowed_updates' => ['callback_query'],
        ]);

        if (($response['ok'] ?? false) !== true) {
            $this->error('Gagal mendaftarkan webhook: '.($response['description'] ?? 'tidak ada respons'));

            return self::FAILURE;
        }

        $this->info("Webhook aktif: {$url}");

        return self::SUCCESS;
    }
}
