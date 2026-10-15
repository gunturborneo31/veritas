import PhoneFrame from '@/Components/Greeting/PhoneFrame';
import Player from '@/Components/Greeting/Player';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { dateTime, rupiah } from '@/lib/format';
import { Head, useForm } from '@inertiajs/react';

/**
 * Verifikasi pembayaran manual. Konfirmasi = snapshot dibekukan & ucapan terbit.
 */
export default function Show({ order, greeting }) {
    const confirmForm = useForm({});
    const rejectForm = useForm({ note: '' });
    const isReviewable = order.status === 'menunggu_konfirmasi';

    return (
        <AuthenticatedLayout header={<h2 className="text-headline-sm text-on-surface">Pesanan {order.code}</h2>}>
            <Head title={`Admin • ${order.code}`} />
            <div className="mx-auto grid max-w-6xl gap-6 p-4 sm:p-6 lg:grid-cols-[1fr_360px]">
                <div className="space-y-4">
                    <section className="space-y-2 rounded-2xl bg-white p-5 shadow-card">
                        <p className="text-label-md text-on-surface-variant">Status</p>
                        <p className="text-headline-sm text-on-surface">{order.status_label}</p>
                        <p className="text-body-sm">
                            {order.customer.name} • {order.customer.email} {order.customer.phone && `• ${order.customer.phone}`}
                        </p>
                        <p className="text-body-sm text-on-surface-variant">Dibuat {dateTime(order.created_at)}</p>
                        <div className="border-t border-surface-container pt-2">
                            {order.price_breakdown.map((line) => (
                                <div key={line.label} className="flex justify-between text-body-sm">
                                    <span>{line.label}</span>
                                    <span>{rupiah(line.amount)}</span>
                                </div>
                            ))}
                            <div className="flex justify-between pt-1 text-label-lg font-bold">
                                <span>Total</span>
                                <span>{rupiah(order.amount)}</span>
                            </div>
                        </div>
                    </section>

                    {order.payments.map((payment) => (
                        <section key={payment.id} className="space-y-3 rounded-2xl bg-white p-5 shadow-card">
                            <div className="flex items-center justify-between">
                                <p className="text-label-lg">
                                    Bukti #{payment.id} • {payment.method} • {rupiah(payment.amount)}
                                </p>
                                <span className="rounded-full bg-surface-container px-3 py-1 text-label-sm">{payment.status}</span>
                            </div>
                            <p className="text-body-sm text-on-surface-variant">
                                Dikirim {dateTime(payment.created_at)} {payment.sender_account_name && `• a.n. ${payment.sender_account_name}`}
                            </p>
                            <a href={payment.proof_url} target="_blank" rel="noreferrer">
                                <img src={payment.proof_url} alt="Bukti transfer" className="max-h-96 rounded-xl border border-surface-container object-contain" />
                            </a>
                            {payment.reviewer && (
                                <p className="text-label-sm text-on-surface-variant">
                                    Diperiksa {payment.reviewer} • {dateTime(payment.reviewed_at)} {payment.review_note && `• ${payment.review_note}`}
                                </p>
                            )}
                        </section>
                    ))}

                    {isReviewable && (
                        <section className="space-y-3 rounded-2xl bg-white p-5 shadow-card">
                            <h3 className="text-headline-sm">Verifikasi</h3>
                            <button
                                type="button"
                                disabled={confirmForm.processing}
                                onClick={() => confirm('Konfirmasi pembayaran dan terbitkan ucapan? Isi ucapan akan dibekukan permanen.') && confirmForm.post(route('admin.orders.confirm', order.code))}
                                className="w-full rounded-full bg-success py-3 text-label-lg text-white"
                            >
                                Pembayaran Sah — Terbitkan Ucapan
                            </button>
                            <form
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    rejectForm.post(route('admin.orders.reject', order.code));
                                }}
                                className="space-y-2"
                            >
                                <input
                                    type="text"
                                    value={rejectForm.data.note}
                                    onChange={(event) => rejectForm.setData('note', event.target.value)}
                                    placeholder="Alasan penolakan (dikirim ke pembeli)"
                                    className="w-full rounded-xl border-surface-container"
                                />
                                {rejectForm.errors.note && <p className="text-label-sm text-error">{rejectForm.errors.note}</p>}
                                <button type="submit" disabled={rejectForm.processing} className="w-full rounded-full bg-error-container py-3 text-label-lg text-on-error-container">
                                    Tolak Bukti
                                </button>
                            </form>
                            {Object.values(confirmForm.errors).map((error) => (
                                <p key={error} className="text-label-sm text-error">
                                    {error}
                                </p>
                            ))}
                        </section>
                    )}
                </div>

                <aside className="flex justify-center">
                    <PhoneFrame>
                        <Player greeting={greeting} mode="preview" />
                    </PhoneFrame>
                </aside>
            </div>
        </AuthenticatedLayout>
    );
}
