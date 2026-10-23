import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { dateTime } from '@/lib/format';
import { Head, router, useForm } from '@inertiajs/react';

const inputClass = 'rounded-xl border-surface-container';

/**
 * Kode promo kampanye. Kode referral & voucher hadiah dibuat otomatis oleh sistem (lihat statistik).
 */
export default function Index({ promos, referralStats }) {
    const form = useForm({
        code: '',
        label: '',
        discount_type: 'fixed',
        discount_value: 5000,
        max_uses: '',
        first_order_only: false,
        starts_at: '',
        ends_at: '',
        is_active: true,
    });

    const toggleActive = (promo) =>
        router.put(
            route('admin.promos.update', promo.id),
            { ...promo, is_active: !promo.is_active, starts_at: promo.starts_at ?? '', ends_at: promo.ends_at ?? '' },
            { preserveScroll: true },
        );

    return (
        <AuthenticatedLayout header={<h2 className="text-headline-sm text-on-surface">Kode Promo & Referral</h2>}>
            <Head title="Admin • Promo" />
            <div className="mx-auto max-w-5xl space-y-6 p-4 sm:p-6">
                <section className="grid grid-cols-3 gap-3">
                    {[
                        ['Kode referral pengguna', referralStats.codes],
                        ['Pesanan lewat referral', referralStats.uses],
                        ['Voucher hadiah terbit', referralStats.rewards],
                    ].map(([label, value]) => (
                        <div key={label} className="rounded-2xl bg-white p-4 shadow-card">
                            <p className="text-label-sm text-on-surface-variant">{label}</p>
                            <p className="font-display text-headline-md text-on-surface">{value}</p>
                        </div>
                    ))}
                </section>

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post(route('admin.promos.store'), { preserveScroll: true, onSuccess: () => form.reset() });
                    }}
                    className="grid gap-3 rounded-2xl bg-white p-5 shadow-card sm:grid-cols-4"
                >
                    <input placeholder="KODE" value={form.data.code} onChange={(event) => form.setData('code', event.target.value.toUpperCase())} className={inputClass} />
                    <input placeholder="Label (mis. Promo Lebaran)" value={form.data.label} onChange={(event) => form.setData('label', event.target.value)} className={`${inputClass} sm:col-span-2`} />
                    <div className="flex gap-2">
                        <select value={form.data.discount_type} onChange={(event) => form.setData('discount_type', event.target.value)} className={inputClass}>
                            <option value="fixed">Rp</option>
                            <option value="percent">%</option>
                        </select>
                        <input type="number" min={1} value={form.data.discount_value} onChange={(event) => form.setData('discount_value', event.target.value)} className={`${inputClass} w-full`} />
                    </div>
                    <input type="number" min={1} placeholder="Kuota (kosong = tanpa batas)" value={form.data.max_uses} onChange={(event) => form.setData('max_uses', event.target.value)} className={inputClass} />
                    <input type="datetime-local" value={form.data.starts_at} onChange={(event) => form.setData('starts_at', event.target.value)} className={inputClass} title="Mulai" />
                    <input type="datetime-local" value={form.data.ends_at} onChange={(event) => form.setData('ends_at', event.target.value)} className={inputClass} title="Berakhir" />
                    <label className="flex items-center gap-2 text-body-sm">
                        <input type="checkbox" checked={form.data.first_order_only} onChange={(event) => form.setData('first_order_only', event.target.checked)} className="rounded text-primary" />
                        Hanya pesanan pertama
                    </label>
                    {Object.values(form.errors).map((error) => (
                        <p key={error} className="text-label-sm text-error sm:col-span-4">
                            {error}
                        </p>
                    ))}
                    <button type="submit" disabled={form.processing} className="rounded-full bg-primary px-5 py-2.5 text-label-md text-on-primary sm:col-span-4 sm:justify-self-start">
                        Buat Kode Promo
                    </button>
                </form>

                <div className="space-y-2 sm:hidden">
                    {promos.length === 0 && <p className="rounded-2xl bg-white px-4 py-8 text-center text-body-sm text-on-surface-variant shadow-card">Belum ada kode promo.</p>}
                    {promos.map((promo) => (
                        <div key={promo.id} className="space-y-1 rounded-2xl bg-white p-4 shadow-card">
                            <div className="flex items-center justify-between gap-2">
                                <p className="truncate text-label-lg tracking-wider text-on-surface">{promo.code}</p>
                                <span className={`shrink-0 rounded-full px-2.5 py-0.5 text-label-sm ${promo.is_active ? 'bg-primary-fixed text-primary' : 'bg-surface-container text-on-surface-variant'}`}>
                                    {promo.is_active ? 'Aktif' : 'Nonaktif'}
                                </span>
                            </div>
                            {promo.label && <p className="text-label-sm text-on-surface-variant">{promo.label}</p>}
                            <p className="text-body-sm text-on-surface">
                                Potongan {promo.discount_type === 'percent' ? `${promo.discount_value}%` : `Rp${Number(promo.discount_value).toLocaleString('id-ID')}`}
                                {promo.first_order_only && ' (pesanan pertama)'} • dipakai {promo.uses_count}
                                {promo.max_uses ? ` / ${promo.max_uses}` : ''}
                            </p>
                            <p className="text-label-sm text-on-surface-variant">
                                {promo.starts_at ? dateTime(promo.starts_at) : 'Sekarang'} – {promo.ends_at ? dateTime(promo.ends_at) : 'Tanpa batas'}
                            </p>
                            <button type="button" onClick={() => toggleActive(promo)} className="pt-1 text-label-md text-primary underline">
                                {promo.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                            </button>
                        </div>
                    ))}
                </div>

                <div className="hidden overflow-x-auto rounded-2xl bg-white shadow-card sm:block">
                    <table className="min-w-full text-left text-body-sm">
                        <thead className="bg-surface-container-low text-label-md text-on-surface-variant">
                            <tr>
                                {['Kode', 'Potongan', 'Pemakaian', 'Periode', 'Status', ''].map((heading) => (
                                    <th key={heading} className="px-4 py-3">
                                        {heading}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {promos.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="px-4 py-8 text-center text-on-surface-variant">
                                        Belum ada kode promo.
                                    </td>
                                </tr>
                            )}
                            {promos.map((promo) => (
                                <tr key={promo.id} className="border-t border-surface-container">
                                    <td className="px-4 py-3">
                                        <p className="font-semibold tracking-wider">{promo.code}</p>
                                        <p className="text-label-sm text-on-surface-variant">{promo.label}</p>
                                    </td>
                                    <td className="px-4 py-3">
                                        {promo.discount_type === 'percent' ? `${promo.discount_value}%` : `Rp${Number(promo.discount_value).toLocaleString('id-ID')}`}
                                        {promo.first_order_only && <span className="ml-1 text-label-sm text-on-surface-variant">(pesanan pertama)</span>}
                                    </td>
                                    <td className="px-4 py-3">
                                        {promo.uses_count}
                                        {promo.max_uses ? ` / ${promo.max_uses}` : ''}
                                    </td>
                                    <td className="px-4 py-3 text-label-sm">
                                        {promo.starts_at ? dateTime(promo.starts_at) : 'Sekarang'} – {promo.ends_at ? dateTime(promo.ends_at) : 'Tanpa batas'}
                                    </td>
                                    <td className="px-4 py-3">{promo.is_active ? 'Aktif' : 'Nonaktif'}</td>
                                    <td className="px-4 py-3">
                                        <button type="button" onClick={() => toggleActive(promo)} className="text-primary underline">
                                            {promo.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
