<?php

namespace App\Support\WhatsApp;

use App\Models\Order;

/**
 * Kabar WhatsApp ke pembeli untuk pembayaran manual (QRIS statis): dikonfirmasi atau ditolak admin.
 * Hanya dikirim bila pembeli mengisi nomor WA saat mengunggah bukti.
 */
class OrderWhatsAppNotifier
{
    public function __construct(private FonnteGateway $gateway) {}

    public function paymentConfirmed(Order $order): bool
    {
        if (blank($order->notify_whatsapp)) {
            return false;
        }

        $order->loadMissing('greeting.template');
        $greeting = $order->greeting;
        $brand = config('kartukita.brand');
        $product = $greeting->isInvitation() ? 'Undangan' : 'Kartu ucapan';
        $forLine = $greeting->isInvitation()
            ? "Acara: {$greeting->occasion}"
            : "Untuk: {$greeting->recipient_name}".($greeting->occasion ? " ({$greeting->occasion})" : '');

        $lines = [
            "Halo! Pembayaranmu di *{$brand}* sudah *DIKONFIRMASI* ✅",
            '',
            '*Detail pesanan*',
            "Kode: {$order->code}",
            "Layanan: {$product} • {$greeting->template->name}",
            $forLine,
            'Total dibayar: Rp'.number_format($order->amount, 0, ',', '.'),
            'Waktu konfirmasi: '.now()->locale('id')->translatedFormat('j F Y, H:i'),
            '',
            "{$product}mu sudah terbit 🎉",
        ];

        if (filled($greeting->slug)) {
            $lines[] = 'Link untuk dibagikan: '.route('recipient.show', $greeting->slug);
        }

        $lines[] = 'QR code & cara berbagi: '.route('result.show', $greeting);
        $lines[] = '';
        $lines[] = "Terima kasih sudah memakai {$brand} 🤍";

        return $this->gateway->send($order->notify_whatsapp, implode("\n", $lines));
    }

    public function paymentRejected(Order $order, string $reason): bool
    {
        if (blank($order->notify_whatsapp)) {
            return false;
        }

        $brand = config('kartukita.brand');

        $message = implode("\n", [
            "Halo! Bukti pembayaran pesanan *{$order->code}* di *{$brand}* belum bisa kami konfirmasi ⚠️",
            '',
            "Alasan: {$reason}",
            '',
            'Silakan cek lagi dan unggah ulang bukti pembayaranmu di sini:',
            route('orders.show', $order),
        ]);

        return $this->gateway->send($order->notify_whatsapp, $message);
    }
}
