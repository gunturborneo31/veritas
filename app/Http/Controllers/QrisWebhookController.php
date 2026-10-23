<?php

namespace App\Http\Controllers;

use App\Actions\Orders\SettleQrisPayment;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QrisWebhookController extends Controller
{
    /**
     * Webhook InterActive QRIS. Webhook mereka tidak bertanda tangan, jadi:
     * 1) URL memuat token rahasia (QRIS_INTERACTIVE_WEBHOOK_TOKEN) agar tidak mudah ditebak, dan
     * 2) isi webhook tidak pernah dipercaya — status selalu dikonfirmasi ulang ke API cek status.
     */
    public function __invoke(Request $request, string $token, SettleQrisPayment $settle): JsonResponse
    {
        $expected = (string) config('kartukita.payment.qris.webhook_token');

        abort_if($expected === '' || ! hash_equals($expected, $token), 404);

        $invoiceId = (string) $request->input('invoice', '');
        $payment = $invoiceId === '' ? null : Payment::query()->where('provider_invoice_id', $invoiceId)->first();

        if ($payment !== null) {
            $settle->handle($payment);
        }

        // Selalu 200 agar InterActive tidak mengulang kiriman untuk invoice yang bukan milik kita.
        return response()->json(['received' => true]);
    }
}
