import Icon from '@/Components/Icon';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { dateTime, rupiah } from '@/lib/format';
import { Head, Link } from '@inertiajs/react';

export default function Index({ orders, status, statuses }) {
    return (
        <AuthenticatedLayout header={<h2 className="text-headline-sm text-on-surface">Pesanan</h2>}>
            <Head title="Admin • Pesanan" />
            <div className="mx-auto max-w-6xl space-y-4 p-4 sm:p-6">
                <div className="flex flex-wrap gap-2">
                    {statuses.map((item) => (
                        <Link
                            key={item.value}
                            href={route('admin.orders.index', { status: item.value })}
                            className={`rounded-full px-4 py-2 text-label-md ${item.value === status ? 'bg-primary text-on-primary' : 'bg-surface-container text-on-surface'}`}
                        >
                            {item.label}
                        </Link>
                    ))}
                </div>

                <div className="space-y-2 sm:hidden">
                    {orders.data.length === 0 && <p className="rounded-2xl bg-white px-4 py-8 text-center text-body-sm text-on-surface-variant shadow-card">Tidak ada pesanan dengan status ini.</p>}
                    {orders.data.map((order) => (
                        <Link
                            key={order.code}
                            href={route('admin.orders.show', order.code)}
                            className="flex items-center gap-3 rounded-2xl bg-white p-4 shadow-card active:scale-[0.99]"
                        >
                            <div className="min-w-0 flex-1">
                                <p className="flex items-center justify-between gap-2">
                                    <span className="text-label-lg text-on-surface">{order.code}</span>
                                    <span className="text-label-lg text-primary">{rupiah(order.amount)}</span>
                                </p>
                                <p className="truncate text-body-sm text-on-surface">
                                    {order.customer} → {order.recipient_name}
                                </p>
                                <p className="truncate text-label-sm text-on-surface-variant">
                                    {order.template_name} • {dateTime(order.created_at)}
                                </p>
                            </div>
                            <Icon name="chevron_right" size={22} className="text-on-surface-variant" />
                        </Link>
                    ))}
                </div>

                <div className="hidden overflow-x-auto rounded-2xl bg-white shadow-card sm:block">
                    <table className="min-w-full text-left text-body-sm">
                        <thead className="bg-surface-container-low text-label-md text-on-surface-variant">
                            <tr>
                                {['Kode', 'Pembeli', 'Penerima', 'Template', 'Total', 'Dibuat', ''].map((heading) => (
                                    <th key={heading} className="px-4 py-3">
                                        {heading}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {orders.data.length === 0 && (
                                <tr>
                                    <td colSpan={7} className="px-4 py-8 text-center text-on-surface-variant">
                                        Tidak ada pesanan dengan status ini.
                                    </td>
                                </tr>
                            )}
                            {orders.data.map((order) => (
                                <tr key={order.code} className="border-t border-surface-container">
                                    <td className="px-4 py-3 font-semibold">{order.code}</td>
                                    <td className="px-4 py-3">{order.customer}</td>
                                    <td className="px-4 py-3">{order.recipient_name}</td>
                                    <td className="px-4 py-3">{order.template_name}</td>
                                    <td className="px-4 py-3">{rupiah(order.amount)}</td>
                                    <td className="px-4 py-3">{dateTime(order.created_at)}</td>
                                    <td className="px-4 py-3">
                                        <Link href={route('admin.orders.show', order.code)} className="text-primary underline">
                                            Periksa
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="flex flex-wrap gap-1">
                    {orders.links.map((link, index) =>
                        link.url ? (
                            <Link
                                key={index}
                                href={link.url}
                                className={`rounded-lg px-3 py-1 text-label-md ${link.active ? 'bg-primary text-on-primary' : 'bg-white'}`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ) : (
                            <span key={index} className="px-3 py-1 text-label-md text-on-surface-variant" dangerouslySetInnerHTML={{ __html: link.label }} />
                        ),
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
