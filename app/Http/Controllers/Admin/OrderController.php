<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Support\Greetings\GreetingPresenter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
        ]);
        $status = $validated['status'] ?? OrderStatus::MenungguKonfirmasi->value;

        $orders = Order::query()
            ->with('user', 'greeting.template')
            ->where('status', $status)
            ->latest()
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Order $order): array => [
                'code' => $order->code,
                'status' => $order->status->label(),
                'amount' => $order->amount,
                'customer' => $order->user->name,
                'recipient_name' => $order->greeting->recipient_name,
                'template_name' => $order->greeting->template->name,
                'created_at' => $order->created_at->toIso8601String(),
            ]);

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'status' => $status,
            'statuses' => collect(OrderStatus::cases())->map(fn (OrderStatus $case): array => ['value' => $case->value, 'label' => $case->label()]),
        ]);
    }

    public function show(Order $order, GreetingPresenter $presenter): Response
    {
        $order->load('user', 'payments.reviewer', 'greeting.template', 'greeting.gate');

        return Inertia::render('Admin/Orders/Show', [
            'order' => [
                'code' => $order->code,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'amount' => $order->amount,
                'price_breakdown' => $order->price_breakdown,
                'customer' => ['name' => $order->user->name, 'email' => $order->user->email, 'phone' => $order->user->phone],
                'created_at' => $order->created_at->toIso8601String(),
                'payments' => $order->payments->sortByDesc('id')->values()->map(fn (Payment $payment): array => [
                    'id' => $payment->id,
                    'method' => $payment->method,
                    'is_manual' => $payment->isManual(),
                    'unique_code' => $payment->isManual() ? $payment->amount - $order->amount : null,
                    'amount' => $payment->amount,
                    'status' => $payment->status->value,
                    'sender_account_name' => $payment->sender_account_name,
                    'proof_url' => $payment->proofUrl(),
                    'invoice_id' => $payment->provider_invoice_id,
                    'paid_at' => $payment->paid_at?->toIso8601String(),
                    'payer_name' => $payment->payer_name,
                    'payer_method' => $payment->payer_method,
                    'expires_at' => $payment->expires_at?->toIso8601String(),
                    'reviewer' => $payment->reviewer?->name,
                    'reviewed_at' => $payment->reviewed_at?->toIso8601String(),
                    'review_note' => $payment->review_note,
                    'created_at' => $payment->created_at->toIso8601String(),
                ]),
            ],
            'greeting' => $presenter->forDraft($order->greeting),
        ]);
    }
}
