<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Orders\ConfirmPayment;
use App\Actions\Orders\RejectPayment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectPaymentRequest;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentReviewController extends Controller
{
    public function confirm(Request $request, Order $order, ConfirmPayment $confirmPayment): RedirectResponse
    {
        $confirmPayment->handle($order, $request->user());

        return to_route('admin.orders.show', $order)->with('success', 'Pembayaran dikonfirmasi. Ucapan sudah terbit.');
    }

    public function reject(RejectPaymentRequest $request, Order $order, RejectPayment $rejectPayment): RedirectResponse
    {
        $rejectPayment->handle($order, $request->user(), $request->validated('note'));

        return to_route('admin.orders.show', $order)->with('success', 'Bukti pembayaran ditolak.');
    }
}
