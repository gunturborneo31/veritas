import Icon from '@/Components/Icon';
import { router, useForm } from '@inertiajs/react';

/**
 * Input kode promo / referral teman / voucher hadiah. Validasi & potongan dihitung di server.
 */
export default function PromoCodeField({ greetingId, quote, referralHint }) {
    const form = useForm({ promo_code: '' });

    if (quote.promo_code) {
        return (
            <div className="flex items-center justify-between gap-3 rounded-2xl border border-dashed border-primary/40 bg-primary-fixed/40 p-3">
                <div className="flex items-center gap-2">
                    <Icon name="sell" size={20} className="text-primary" />
                    <div>
                        <p className="text-label-md text-on-surface">Kode {quote.promo_code} dipakai</p>
                        <p className="text-label-sm text-primary">Hemat Rp{quote.discount.toLocaleString('id-ID')}</p>
                    </div>
                </div>
                <button
                    type="button"
                    onClick={() => router.delete(route('checkout.promo.remove', greetingId), { preserveScroll: true })}
                    className="text-label-sm text-on-surface-variant underline"
                >
                    Hapus
                </button>
            </div>
        );
    }

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                form.post(route('checkout.promo.apply', greetingId), { preserveScroll: true });
            }}
            className="flex flex-col gap-1.5"
        >
            <div className="flex gap-2">
                <div className="relative flex-1">
                    <Icon name="confirmation_number" size={18} className="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant" />
                    <input
                        type="text"
                        value={form.data.promo_code}
                        onChange={(event) => form.setData('promo_code', event.target.value.toUpperCase())}
                        placeholder="Kode promo / referral teman"
                        className="h-11 w-full rounded-full border-0 bg-surface-container-low pl-10 pr-4 text-label-md uppercase tracking-wider text-on-surface focus:ring-2 focus:ring-primary/40"
                    />
                </div>
                <button type="submit" disabled={!form.data.promo_code.trim() || form.processing} className="h-11 rounded-full bg-on-surface px-4 text-label-md text-surface disabled:opacity-40">
                    Pakai
                </button>
            </div>
            {form.errors.promo_code && <p className="px-2 text-label-sm text-error">{form.errors.promo_code}</p>}
            {referralHint && !form.errors.promo_code && (
                <p className="px-2 text-label-sm text-on-surface-variant">Diajak teman? Masukkan kode referral-nya untuk potongan di pesanan pertamamu.</p>
            )}
        </form>
    );
}
