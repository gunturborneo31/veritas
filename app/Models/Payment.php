<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Catatan pembayaran. Pembayaran baru memakai QRIS dinamis yang lunas otomatis; data lama bisa
 * berupa transfer manual dengan bukti yang diverifikasi admin.
 */
#[Fillable([
    'order_id', 'method', 'amount', 'proof_disk', 'proof_path', 'sender_account_name', 'status', 'reviewed_by', 'reviewed_at', 'review_note',
    'provider_invoice_id', 'qris_content', 'requested_at', 'expires_at', 'paid_at', 'payer_name', 'payer_method', 'status_checks', 'last_checked_at',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    public const METHOD_QRIS = 'qris';

    /**
     * QRIS statis milik merchant; dibayar dengan nominal unik lalu dikonfirmasi admin.
     */
    public const METHOD_MANUAL_QRIS = 'qris_manual';

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'reviewed_at' => 'datetime',
            'requested_at' => 'datetime',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'last_checked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isManual(): bool
    {
        return $this->method === self::METHOD_MANUAL_QRIS;
    }

    /**
     * Kode unik yang ditambahkan ke harga pesanan (3 digit terakhir nominal).
     */
    public function uniqueCode(): int
    {
        return $this->amount - $this->order->amount;
    }

    public function isQris(): bool
    {
        return $this->method === self::METHOD_QRIS && $this->provider_invoice_id !== null;
    }

    /**
     * QR masih bisa dipindai (QRIS InterActive berlaku 30 menit sejak dibuat).
     */
    public function isScannable(): bool
    {
        return $this->status === PaymentStatus::Menunggu && $this->expires_at?->isFuture() === true;
    }

    public function proofUrl(): ?string
    {
        return $this->proof_path === null ? null : Storage::disk($this->proof_disk)->temporaryUrl($this->proof_path, now()->addMinutes(30));
    }
}
