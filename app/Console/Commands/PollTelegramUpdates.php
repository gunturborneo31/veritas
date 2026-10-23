<?php

namespace App\Console\Commands;

use App\Support\Telegram\PaymentReviewActions;
use App\Support\Telegram\TelegramBot;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Signature('kartukita:telegram-poll {--once : Ambil update sekali lalu berhenti}')]
#[Description('Terima ketukan tombol verifikasi pembayaran dari Telegram tanpa webhook (untuk lokal / server tanpa HTTPS).')]
class PollTelegramUpdates extends Command
{
    private const OFFSET_KEY = 'telegram:update_offset';

    /**
     * Long polling getUpdates. Tidak bisa berjalan bersamaan dengan webhook: hapus webhook dulu
     * (`kartukita:telegram-webhook --delete`) bila pernah didaftarkan.
     */
    public function handle(TelegramBot $bot, PaymentReviewActions $actions): int
    {
        if (! $bot->isConfigured()) {
            $this->error('TELEGRAM_BOT_TOKEN belum diisi.');

            return self::FAILURE;
        }

        $this->info('Menunggu ketukan tombol dari Telegram… (Ctrl+C untuk berhenti)');

        do {
            $response = $bot->call('getUpdates', [
                'offset' => Cache::get(self::OFFSET_KEY),
                'timeout' => $this->option('once') ? 0 : 25,
                'allowed_updates' => ['callback_query'],
            ], timeout: 35);

            if (($response['ok'] ?? false) !== true) {
                $this->error('Gagal mengambil update: '.($response['description'] ?? 'tidak ada respons').'. Jika webhook aktif, jalankan kartukita:telegram-webhook --delete.');

                return self::FAILURE;
            }

            foreach ($response['result'] as $update) {
                $actions->handle($update);
                Cache::forever(self::OFFSET_KEY, $update['update_id'] + 1);
                $this->line('Diproses: '.($update['callback_query']['data'] ?? 'update '.$update['update_id']));
            }
        } while (! $this->option('once'));

        return self::SUCCESS;
    }
}
