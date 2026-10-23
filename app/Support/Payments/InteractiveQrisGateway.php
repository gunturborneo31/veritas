<?php

namespace App\Support\Payments;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Klien Open API InterActive QRIS (qris.interactive.co.id). Waktu dari API memakai WIB.
 *
 * @see https://qris.interactive.co.id/api-doc/
 */
class InteractiveQrisGateway implements QrisGateway
{
    private const TIMEZONE = 'Asia/Jakarta';

    public function isConfigured(): bool
    {
        return filled($this->config('api_key')) && filled($this->config('merchant_id'));
    }

    public function createInvoice(string $reference, int $amount): array
    {
        $data = $this->request('show_qris.php', [
            'do' => 'create-invoice',
            'cliTrxNumber' => $reference,
            'cliTrxAmount' => $amount,
            'useTip' => 'no',
        ]);

        if (($data['status'] ?? null) !== 'success' || blank($data['data']['qris_content'] ?? null) || blank($data['data']['qris_invoiceid'] ?? null)) {
            report(new QrisException('InterActive create-invoice gagal: '.Str::limit(json_encode($data), 300)));

            throw QrisException::unavailable();
        }

        return [
            'invoice_id' => (string) $data['data']['qris_invoiceid'],
            'content' => $data['data']['qris_content'],
            'requested_at' => isset($data['data']['qris_request_date'])
                ? CarbonImmutable::createFromFormat('Y-m-d H:i:s', $data['data']['qris_request_date'], self::TIMEZONE)->setTimezone(config('app.timezone'))
                : CarbonImmutable::now(),
        ];
    }

    public function checkStatus(string $invoiceId, int $amount, CarbonInterface $requestedAt): array
    {
        $data = $this->request('checkpaid_qris.php', [
            'do' => 'checkStatus',
            'invid' => $invoiceId,
            'trxvalue' => $amount,
            'trxdate' => $requestedAt->copy()->setTimezone(self::TIMEZONE)->format('Y-m-d'),
        ]);

        $isPaid = ($data['status'] ?? null) === 'success' && ($data['data']['qris_status'] ?? null) === 'paid';

        return [
            'paid' => $isPaid,
            'payer_name' => $isPaid ? ($data['data']['qris_payment_customername'] ?? null) : null,
            'payer_method' => $isPaid ? ($data['data']['qris_payment_methodby'] ?? null) : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function request(string $endpoint, array $query): array
    {
        if (! $this->isConfigured()) {
            throw QrisException::notConfigured();
        }

        try {
            /** @var Response $response */
            $response = Http::timeout(15)
                ->acceptJson()
                ->get(rtrim($this->config('base_url'), '/')."/{$endpoint}", [
                    ...$query,
                    'apikey' => $this->config('api_key'),
                    'mID' => $this->config('merchant_id'),
                ]);
        } catch (ConnectionException $exception) {
            report($exception);

            throw QrisException::unavailable();
        }

        if ($response->serverError() || ! is_array($response->json())) {
            report(new QrisException("InterActive {$endpoint} HTTP {$response->status()}: ".Str::limit($response->body(), 200)));

            throw QrisException::unavailable();
        }

        return $response->json();
    }

    private function config(string $key): mixed
    {
        return config("kartukita.payment.qris.{$key}");
    }
}
