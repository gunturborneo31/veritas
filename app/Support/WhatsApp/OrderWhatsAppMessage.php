<?php

namespace App\Support\WhatsApp;

use App\Models\Order;

/**
 * Pesan WhatsApp untuk pembeli pembayaran manual (QRIS statis), dikirim admin dari WA-nya sendiri
 * lewat link wa.me (tanpa layanan pihak ketiga): satu ketuk membuka chat ke pembeli dengan pesan
 * yang sudah terisi. Link hanya ada bila pembeli mengisi nomor WA saat mengunggah bukti.
 */
class OrderWhatsAppMessage
{
    public function confirmedLink(Order $order): ?string
    {
        return $this->link($order, $this->confirmedText($order));
    }

    public function rejectedLink(Order $order, string $reason): ?string
    {
        return $this->link($order, $this->rejectedText($order, $reason));
    }

    public function confirmedText(Order $order): string
    {
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
            'Waktu konfirmasi: '.($order->paid_at ?? now())->locale('id')->translatedFormat('j F Y, H:i'),
            '',
            "{$product}mu sudah terbit 🎉",
        ];

        if (filled($greeting->slug)) {
            $lines[] = 'Link untuk dibagikan: '.route('recipient.show', $greeting->slug);
        }

        $lines[] = 'QR code & cara berbagi: '.route('result.show', $greeting);
        $lines[] = '';
        $lines[] = "Terima kasih sudah memakai {$brand} 🤍";

        return implode("\n", $lines);
    }

    public function rejectedText(Order $order, string $reason): string
    {
        $brand = config('kartukita.brand');

        return implode("\n", [
            "Halo! Bukti pembayaran pesanan *{$order->code}* di *{$brand}* belum bisa kami konfirmasi ⚠️",
            '',
            "Alasan: {$reason}",
            '',
            'Silakan cek lagi dan unggah ulang bukti pembayaranmu di sini:',
            route('orders.show', $order),
        ]);
    }

    private function link(Order $order, string $text): ?string
    {
        if (blank($order->notify_whatsapp)) {
            return null;
        }

        return "https://wa.me/{$order->notify_whatsapp}?text=".rawurlencode($text);
    }
}
