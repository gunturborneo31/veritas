<?php

namespace App\Http\Controllers;

use App\Support\Telegram\PaymentReviewActions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    /**
     * Webhook Telegram (produksi). Telegram mengirim rahasia di header X-Telegram-Bot-Api-Secret-Token
     * sesuai yang didaftarkan lewat `kartukita:telegram-webhook`; tanpa rahasia yang cocok, 404.
     */
    public function __invoke(Request $request, PaymentReviewActions $actions): JsonResponse
    {
        $secret = (string) config('services.telegram.webhook_secret');

        abort_if($secret === '' || ! hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token')), 404);

        $actions->handle($request->all());

        return response()->json(['ok' => true]);
    }
}
