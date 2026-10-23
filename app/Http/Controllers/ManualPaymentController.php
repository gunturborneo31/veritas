<?php

namespace App\Http\Controllers;

use App\Actions\Orders\ClaimManualPayment;
use App\Http\Requests\ClaimManualPaymentRequest;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;

class ManualPaymentController extends Controller
{
    /**
     * Tombol "Saya sudah bayar" pada QRIS statis: pembeli mengunggah bukti, admin mencocokkan
     * nominal unik dengan mutasi lalu menekan Konfirmasi.
     */
    public function claim(ClaimManualPaymentRequest $request, Order $order, ClaimManualPayment $claim): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        $claim->handle($order, $request->file('proof'), $request->validated('sender_account_name'));

        return to_route('orders.show', $order)->with('success', $request->hasFile('proof')
            ? 'Bukti terkirim! Pembayaranmu sedang kami cek.'
            : 'Kirim bukti pembayaranmu di WhatsApp, ya. Kami cek secepatnya.');
    }
}
