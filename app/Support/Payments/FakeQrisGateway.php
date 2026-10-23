<?php

namespace App\Support\Payments;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Pengganti InterActive untuk pengembangan lokal (QRIS_DRIVER=fake). QR-nya bukan QRIS sungguhan;
 * status "dibayar" diatur lewat tombol simulasi di halaman pesanan.
 */
class FakeQrisGateway implements QrisGateway
{
    public function isConfigured(): bool
    {
        return true;
    }

    public function createInvoice(string $reference, int $amount): array
    {
        $invoiceId = 'FAKE'.Str::upper(Str::random(10));

        return [
            'invoice_id' => $invoiceId,
            'content' => "SIMULASI-QRIS|{$reference}|{$amount}|{$invoiceId}",
            'requested_at' => CarbonImmutable::now(),
        ];
    }

    public function checkStatus(string $invoiceId, int $amount, CarbonInterface $requestedAt): array
    {
        $isPaid = Cache::get(self::paidKey($invoiceId)) === $amount;

        return ['paid' => $isPaid, 'payer_name' => $isPaid ? 'PEMBELI SIMULASI' : null, 'payer_method' => $isPaid ? 'SIMULASI' : null];
    }

    public static function markPaid(string $invoiceId, int $amount): void
    {
        Cache::put(self::paidKey($invoiceId), $amount, now()->addDay());
    }

    private static function paidKey(string $invoiceId): string
    {
        return "fake-qris-paid:{$invoiceId}";
    }
}
