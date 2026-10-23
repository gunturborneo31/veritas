import { rupiah } from '@/lib/format';

/**
 * Harga template. Saat promo aktif, harga asli tampil dicoret di depan harga promo.
 */
export default function Price({ price, originalPrice = null, className = '', style = undefined }) {
    const isDiscounted = originalPrice !== null && originalPrice > price;

    return (
        <span className={`inline-flex flex-wrap items-baseline gap-x-1.5 ${className}`} style={style}>
            {isDiscounted && <s className="text-[0.7em] font-normal text-on-surface-variant opacity-70">{rupiah(originalPrice)}</s>}
            <span>{rupiah(price)}</span>
        </span>
    );
}
