<?php

namespace App\Http\Controllers;

use App\Actions\Orders\SettleQrisPayment;
use App\Actions\Orders\StartQrisPayment;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Support\Greetings\QrCodeGenerator;
use App\Support\Payments\FakeQrisGateway;
use App\Support\Payments\QrisException;
use App\Support\Payments\QrisGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Aksi pembeli di halaman pesanan: buat QR baru, cek status sekarang, unduh gambar QR.
 */
class QrisPaymentController extends Controller
{
    public function refresh(Request $request, Order $order, StartQrisPayment $start): RedirectResponse
    {
        $this->authorizeOwner($request, $order);

        try {
            $start->handle($order);
        } catch (QrisException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('orders.show', $order);
    }

    /**
     * Tombol "Saya sudah bayar". Dibatasi sekali per 15 detik per pesanan sesuai panduan InterActive.
     */
    public function check(Request $request, Order $order, SettleQrisPayment $settle): RedirectResponse
    {
        $this->authorizeOwner($request, $order);

        $payment = $order->latestPayment;
        $key = "qris-check:{$order->id}";

        if ($payment === null || ! $payment->isQris()) {
            return to_route('orders.show', $order);
        }

        if (RateLimiter::tooManyAttempts($key, 1)) {
            return back()->with('error', 'Sedang kami cek. Tunggu '.RateLimiter::availableIn($key).' detik lalu coba lagi.');
        }

        RateLimiter::hit($key, 15);

        if ($settle->handle($payment)) {
            return to_route('result.show', $order->greeting)->with('success', 'Pembayaran diterima. Kartumu sudah terbit! 🎉');
        }

        return back()->with('error', 'Pembayaran belum masuk. Jika sudah membayar, tunggu sebentar — halaman ini akan berpindah otomatis.');
    }

    /**
     * Gambar QR untuk disimpan lalu dipindai dari galeri (saat membayar di HP yang sama).
     */
    public function image(Request $request, Order $order, QrCodeGenerator $qr): Response
    {
        $this->authorizeOwner($request, $order);

        $payment = $order->latestPayment;
        abort_unless($payment?->isQris() && $payment->isScannable(), 404);

        return response($qr->paymentPng($payment->qris_content), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => "attachment; filename=\"QRIS-{$order->code}.png\"",
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * Hanya untuk QRIS_DRIVER=fake di luar produksi: anggap QR sudah dibayar.
     */
    public function simulate(Request $request, Order $order, QrisGateway $gateway, SettleQrisPayment $settle): RedirectResponse
    {
        $this->authorizeOwner($request, $order);
        abort_unless($gateway instanceof FakeQrisGateway, 404);

        $payment = $order->latestPayment;
        abort_unless($payment?->isQris() && $order->status === OrderStatus::Menunggu, 404);

        FakeQrisGateway::markPaid($payment->provider_invoice_id, $payment->amount);
        $settle->handle($payment);

        return to_route('result.show', $order->greeting)->with('success', 'Pembayaran simulasi diterima. 🎉');
    }

    private function authorizeOwner(Request $request, Order $order): void
    {
        abort_unless($order->user_id === $request->user()->id, 404);
    }
}
