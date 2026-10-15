<?php

namespace App\Support\Pricing;

/**
 * Rincian harga yang dihitung di server dan dikunci ke pesanan.
 */
final readonly class PriceQuote
{
    /**
     * @param  list<array{label: string, amount: int}>  $lines
     */
    public function __construct(
        public array $lines,
        public int $total,
        public int $discount = 0,
        public ?string $promoCode = null,
    ) {}

    /**
     * @return array{lines: list<array{label: string, amount: int}>, total: int, discount: int, promo_code: ?string}
     */
    public function toArray(): array
    {
        return ['lines' => $this->lines, 'total' => $this->total, 'discount' => $this->discount, 'promo_code' => $this->promoCode];
    }
}
