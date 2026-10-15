import Icon from '@/Components/Icon';
import PublicLayout from '@/Layouts/PublicLayout';
import { dateTime, rupiah } from '@/lib/format';
import { Link, useForm, usePoll } from '@inertiajs/react';
import { useState } from 'react';

function CopyRow({ label, value }) {
    const [copied, setCopied] = useState(false);

    return (
        <div className="flex items-center justify-between rounded-2xl bg-surface-container-low p-3">
            <div>
                <p className="text-label-sm text-on-surface-variant">{label}</p>
                <p className="text-label-lg text-on-surface">{value}</p>
            </div>
            <button
                type="button"
                onClick={() => {
                    navigator.clipboard?.writeText(String(value));
                    setCopied(true);
                    setTimeout(() => setCopied(false), 2000);
                }}
                className="flex items-center gap-1 rounded-full bg-primary px-3 py-1.5 text-label-sm text-on-primary"
            >
                <Icon name={copied ? 'done' : 'content_copy'} size={14} />
                {copied ? 'Tersalin' : 'Salin'}
            </button>
        </div>
    );
}

/**
 * Instruksi pembayaran manual (transfer / QRIS statis) + unggah bukti + status verifikasi admin.
 */
export default function Show({ order, greeting, paymentMethods }) {
    const form = useForm({ method: 'transfer', sender_account_name: '', proof: null });
    const isWaitingReview = order.status === 'menunggu_konfirmasi';
    const wasRejected = order.latest_payment?.status === 'ditolak' && order.status === 'menunggu';

    usePoll(15000, { only: ['order'] }, { autoStart: isWaitingReview });

    const submit = (event) => {
        event.preventDefault();
        form.post(route('orders.proof.store', order.code), { forceFormData: true });
    };

    return (
        <PublicLayout title={`Pesanan ${order.code}`}>
            <div className="flex flex-col gap-space-lg px-margin py-space-md">
                <section className="flex flex-col items-center gap-2 text-center">
                    <div className="flex h-20 w-20 items-center justify-center rounded-full bg-surface-container-high shadow-lg">
                        <Icon name={isWaitingReview ? 'hourglass_top' : 'account_balance_wallet'} size={40} className="text-primary" filled />
                    </div>
                    <span className="rounded-full bg-surface-container px-3 py-1 text-label-sm text-on-surface-variant">{order.status_label}</span>
                    <h1 className="text-headline-lg-mobile text-on-surface">
                        {isWaitingReview ? 'Bukti bayar sedang diperiksa' : `Bayar ${rupiah(order.amount)}`}
                    </h1>
                    <p className="max-w-sm text-body-sm text-on-surface-variant">
                        Ucapan <strong className="text-on-surface">{greeting.template_name}</strong> untuk <strong className="text-on-surface">{greeting.recipient_name}</strong>.
                        No. pesanan <span className="font-semibold text-on-surface">{order.code}</span>.
                    </p>
                    {order.status === 'menunggu' && (
                        <p className="inline-flex items-center gap-1 rounded-full bg-error-container px-3 py-1 text-label-sm text-on-error-container">
                            <Icon name="alarm" size={14} />
                            Bayar sebelum {dateTime(order.expires_at)}
                        </p>
                    )}
                </section>

                {order.status === 'batal' ? (
                    <section className="flex flex-col items-center gap-2 rounded-3xl bg-surface-container-lowest p-space-md text-center shadow-card">
                        <Icon name="timer_off" size={28} className="text-error" />
                        <p className="font-display text-headline-sm text-on-surface">Pesanan ini sudah kedaluwarsa</p>
                        <p className="text-body-sm text-on-surface-variant">Ucapanmu aman dan kembali menjadi draf. Kamu bisa membuat pesanan baru kapan saja.</p>
                        <Link href={route('checkout.show', greeting.id)} className="mt-1 rounded-full bg-primary px-5 py-2.5 text-label-md text-on-primary">
                            Buat pesanan baru
                        </Link>
                    </section>
                ) : isWaitingReview ? (
                    <section className="flex flex-col gap-2 rounded-3xl bg-surface-container-lowest p-space-md text-center shadow-card">
                        <Icon name="schedule" size={28} className="mx-auto text-tertiary" />
                        <p className="text-body-sm text-on-surface-variant">
                            Dikirim {dateTime(order.latest_payment?.submitted_at)}. Halaman ini akan otomatis berpindah ke link & QR setelah admin mengonfirmasi.
                        </p>
                        <p className="text-label-sm text-on-surface-variant">Isi ucapan sudah terkunci dan tidak bisa diubah.</p>
                    </section>
                ) : (
                    <>
                        {wasRejected && (
                            <div className="rounded-2xl bg-error-container p-space-md text-on-error-container">
                                <p className="text-label-md font-bold">Bukti sebelumnya ditolak</p>
                                <p className="text-body-sm">{order.latest_payment.review_note}</p>
                            </div>
                        )}

                        <section className="flex flex-col gap-space-sm rounded-3xl bg-surface-container-lowest p-space-md shadow-card">
                            <h2 className="text-headline-sm text-on-surface">1. Transfer sesuai nominal</h2>
                            <div className="flex gap-2">
                                {Object.entries(paymentMethods).map(([key, method]) => (
                                    <button
                                        key={key}
                                        type="button"
                                        onClick={() => form.setData('method', key)}
                                        className={`flex-1 rounded-full px-4 py-2 text-label-md ${form.data.method === key ? 'bg-primary text-on-primary' : 'bg-surface-container text-on-surface'}`}
                                    >
                                        {method.label}
                                    </button>
                                ))}
                            </div>
                            {form.data.method === 'transfer' ? (
                                <div className="flex flex-col gap-2">
                                    <CopyRow label={`Bank ${paymentMethods.transfer.bank}`} value={paymentMethods.transfer.account_number} />
                                    <p className="px-1 text-label-sm text-on-surface-variant">a.n. {paymentMethods.transfer.account_holder}</p>
                                </div>
                            ) : (
                                <div className="flex flex-col items-center gap-2 rounded-2xl bg-surface-container-low p-4">
                                    <img src={paymentMethods.qris.image_url} alt="QRIS" className="h-56 w-56 rounded-xl bg-white object-contain p-2" />
                                    <p className="text-label-sm text-on-surface-variant">Scan dengan aplikasi bank / e-wallet apa pun.</p>
                                </div>
                            )}
                            <CopyRow label="Nominal (tepat)" value={order.amount} />
                        </section>

                        <form onSubmit={submit} className="flex flex-col gap-space-sm rounded-3xl bg-surface-container-lowest p-space-md shadow-card">
                            <h2 className="text-headline-sm text-on-surface">2. Unggah bukti transfer</h2>
                            <input
                                type="text"
                                placeholder="Nama pemilik rekening pengirim (opsional)"
                                value={form.data.sender_account_name}
                                onChange={(event) => form.setData('sender_account_name', event.target.value)}
                                className="w-full rounded-2xl border-0 bg-surface-container-low px-4 py-3 text-body-md focus:ring-2 focus:ring-primary/40"
                            />
                            <label className="flex cursor-pointer flex-col items-center justify-center gap-1 rounded-2xl bg-surface-container-low py-5 text-center">
                                <Icon name="upload_file" size={28} className="text-primary" />
                                <span className="text-label-md text-primary">{form.data.proof ? form.data.proof.name : 'Pilih foto bukti transfer'}</span>
                                <span className="text-label-sm text-on-surface-variant">JPG / PNG hingga 4MB</span>
                                <input type="file" accept="image/*" className="hidden" onChange={(event) => form.setData('proof', event.target.files[0] ?? null)} />
                            </label>
                            {Object.values(form.errors).map((error) => (
                                <p key={error} className="text-label-sm text-error">
                                    {error}
                                </p>
                            ))}
                            <button
                                type="submit"
                                disabled={!form.data.proof || form.processing}
                                className="flex h-12 items-center justify-center gap-2 rounded-full bg-primary text-label-lg text-on-primary shadow-md disabled:opacity-50"
                            >
                                <Icon name="send" size={18} />
                                Kirim Bukti Pembayaran
                            </button>
                            <p className="text-center text-label-sm text-on-surface-variant">Setelah dikirim, isi ucapan terkunci permanen.</p>
                        </form>
                    </>
                )}

                <Link href={route('dashboard')} className="text-center text-label-md text-primary hover:underline">
                    Kembali ke Kartu Saya
                </Link>
            </div>
        </PublicLayout>
    );
}
