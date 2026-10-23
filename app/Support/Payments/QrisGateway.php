<?php

namespace App\Support\Payments;

use Carbon\CarbonInterface;

/**
 * Penyedia QRIS dinamis: buat invoice QR per pesanan dan periksa apakah sudah dibayar.
 */
interface QrisGateway
{
    public function isConfigured(): bool;

    /**
     * @return array{invoice_id: string, content: string, requested_at: CarbonInterface}
     *
     * @throws QrisException
     */
    public function createInvoice(string $reference, int $amount): array;

    /**
     * @return array{paid: bool, payer_name: ?string, payer_method: ?string}
     *
     * @throws QrisException
     */
    public function checkStatus(string $invoiceId, int $amount, CarbonInterface $requestedAt): array;
}
