import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { dateTime, rupiah } from '@/lib/format';
import { Head, Link } from '@inertiajs/react';

const STATUS_CLASS = {
    lunas: 'bg-primary-fixed text-primary',
    menunggu_konfirmasi: 'bg-tertiary-fixed text-on-tertiary-fixed',
    menunggu: 'bg-surface-container text-on-surface-variant',
    batal: 'bg-surface-container text-on-surface-variant line-through',
};

/**
 * Pemakai satu kode promo: semua pesanan yang memakai kode ini beserta pembelinya.
 */
export default function Show({ promo, summary, orders }) {
    return (
        <AuthenticatedLayout header={<h2 className="text-headline-sm text-on-surface">Pemakai Kode {promo.code}</h2>}>
            <Head title={`Admin • Pemakai ${promo.code}`} />
            <div className="mx-auto max-w-5xl space-y-4 p-4 sm:p-6">
                <Link href={route('admin.promos.index')} className="text-label-md text-primary underline">
                    ← Kembali ke daftar promo
                </Link>

                <section className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    {[
                        ['Potongan', promo.description],
                        ['Pesanan memakai kode', summary.orders],
                        ['Lunas', `${summary.paid}${promo.max_uses ? ` / ${promo.max_uses}` : ''}`],
                        ['Total potongan (lunas)', rupiah(summary.total_discount)],
                    ].map(([label, value]) => (
                        <div key={label} className="rounded-2xl bg-white p-4 shadow-card">
                            <p className="text-label-sm text-on-surface-variant">{label}</p>
                            <p className="font-display text-headline-sm text-on-surface">{value}</p>
                        </div>
                    ))}
                </section>

                {orders.length === 0 && (
                    <p className="rounded-2xl bg-white px-4 py-8 text-center text-body-sm text-on-surface-variant shadow-card">Belum ada yang memakai kode ini.</p>
                )}

                <div className="space-y-2">
                    {orders.map((order) => (
                        <Link
                            key={order.code}
                            href={route('admin.orders.show', order.code)}
                            className="flex flex-col gap-1 rounded-2xl bg-white p-4 shadow-card hover:bg-surface-container-low sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div className="min-w-0">
                                <p className="truncate text-label-lg text-on-surface">
                                    {order.customer?.name ?? 'Tanpa akun'}
                                    <span className="font-normal text-on-surface-variant"> • {order.customer?.phone || order.customer?.email}</span>
                                </p>
                                <p className="truncate text-body-sm text-on-surface-variant">
                                    {order.template_name} → {order.recipient_name || order.occasion || '-'}
                                </p>
                                <p className="text-label-sm text-on-surface-variant">
                                    {order.code} • {dateTime(order.created_at)}
                                </p>
                            </div>
                            <div className="flex shrink-0 items-center gap-2 sm:flex-col sm:items-end">
                                <span className={`rounded-full px-2.5 py-0.5 text-label-sm ${STATUS_CLASS[order.status] ?? ''}`}>{order.status_label}</span>
                                <span className="text-label-md text-on-surface">
                                    {rupiah(order.amount)} <span className="text-label-sm text-primary">(-{rupiah(order.discount)})</span>
                                </span>
                            </div>
                        </Link>
                    ))}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
