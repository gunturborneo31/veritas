<?php

namespace App\Http\Controllers;

use App\Actions\Orders\SubmitPaymentProof;
use App\Http\Requests\SubmitPaymentProofRequest;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;

class PaymentProofController extends Controller
{
    public function store(SubmitPaymentProofRequest $request, Order $order, SubmitPaymentProof $submitProof): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        $submitProof->handle(
            $order,
            $request->file('proof'),
            $request->validated('method'),
            $request->validated('sender_account_name'),
        );

        return to_route('orders.show', $order)
            ->with('success', 'Bukti pembayaran terkirim. Kami akan memverifikasinya secepatnya.');
    }
}
