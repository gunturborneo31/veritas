<?php

namespace App\Http\Controllers;

use App\Actions\Orders\StartManualPayment;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Support\Greetings\QrCodeGenerator;
use App\Support\Payments\FakeQrisGateway;
use App\Support\Payments\ManualQris;
use App\Support\Payments\QrisGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    /**
     * Halaman bayar. QRIS dinamis: QR bernominal tetap dengan hitung mundur 30 menit. QRIS statis (manual):
     * gambar QR merchant + nominal unik + tombol "Saya sudah bayar". Di kedua mode status diperbarui
     * otomatis (halaman memanggil ulang prop `order` secara berkala) dan berpindah ke hasil setelah lunas.
     */
    public function show(Request $request, Order $order, QrCodeGenerator $qr, QrisGateway $gateway, ManualQris $manualQris, StartManualPayment $startManual): Response|RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        $order->load('greeting.template', 'latestPayment');

        if ($order->status === OrderStatus::Lunas) {
            return to_route('result.show', $order->greeting)->with('success', 'Pembayaran diterima. Kartumu sudah terbit! 🎉');
        }

        $isManual = $manualQris->isEnabled();
        $payment = $isManual && $manualQris->isConfigured() ? ($startManual->handle($order) ?? $order->latestPayment) : $order->latestPayment;
        $hasQris = ! $isManual && $payment?->isQris() === true;
        $rejected = $order->status === OrderStatus::Menunggu
            ? $order->payments()->where('status', PaymentStatus::Ditolak)->latest('id')->first()
            : null;

        return Inertia::render('Orders/Show', [
            'order' => [
                'code' => $order->code,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'amount' => $order->amount,
                'price_breakdown' => $order->price_breakdown,
                'created_at' => $order->created_at->toIso8601String(),
                'qris' => ! $hasQris ? null : [
                    'is_scannable' => $payment->isScannable(),
                    'status' => $payment->status->value,
                    'expires_at' => $payment->expires_at?->toIso8601String(),
                    'svg' => $payment->isScannable() ? $qr->paymentSvg($payment->qris_content) : null,
                ],
                'manual' => $payment?->isManual() !== true ? null : [
                    'amount' => $payment->amount,
                    'unique_code' => $payment->uniqueCode(),
                    'image_url' => $manualQris->imageUrl(),
                    'merchant_name' => $manualQris->merchantName(),
                    'proof_channel' => $manualQris->proofChannel(),
                    'whatsapp_url' => $manualQris->whatsappUrl($payment),
                ],
                // Setelah klaim via WhatsApp, pembeli bisa membuka lagi chat untuk mengirim bukti.
                'review_whatsapp_url' => $order->status === OrderStatus::MenungguKonfirmasi && $payment?->isManual() && $payment->proof_path === null
                    ? $manualQris->whatsappUrl($payment)
                    : null,
                'is_awaiting_review' => $order->status === OrderStatus::MenungguKonfirmasi,
                'rejection_note' => $rejected?->review_note,
            ],
            'greeting' => [
                'id' => $order->greeting->public_id,
                'recipient_name' => $order->greeting->recipient_name,
                'template_name' => $order->greeting->template->name,
            ],
            'isQrisReady' => $isManual ? $manualQris->isConfigured() : $gateway->isConfigured(),
            'canSimulate' => ! $isManual && $gateway instanceof FakeQrisGateway,
        ]);
    }
}
