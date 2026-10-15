<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    /**
     * Instruksi pembayaran manual + unggah bukti transfer + status verifikasi.
     */
    public function show(Request $request, Order $order): Response|RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        $order->load('greeting.template', 'latestPayment');

        if ($order->status === OrderStatus::Lunas) {
            return to_route('result.show', $order->greeting);
        }

        $methods = config('kartukita.payment.methods');
        $methods['qris']['image_url'] = Storage::disk('public')->url($methods['qris']['image_path']);

        return Inertia::render('Orders/Show', [
            'order' => [
                'code' => $order->code,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'amount' => $order->amount,
                'price_breakdown' => $order->price_breakdown,
                'created_at' => $order->created_at->toIso8601String(),
                'expires_at' => $order->created_at->addHours(config('kartukita.payment.order_expires_hours'))->toIso8601String(),
                'latest_payment' => $order->latestPayment === null ? null : [
                    'status' => $order->latestPayment->status->value,
                    'review_note' => $order->latestPayment->review_note,
                    'submitted_at' => $order->latestPayment->created_at->toIso8601String(),
                ],
            ],
            'greeting' => [
                'id' => $order->greeting->public_id,
                'recipient_name' => $order->greeting->recipient_name,
                'template_name' => $order->greeting->template->name,
            ],
            'paymentMethods' => $methods,
        ]);
    }
}
