import Icon from '@/Components/Icon';
import PublicLayout from '@/Layouts/PublicLayout';
import { rupiah } from '@/lib/format';
import { Link, router, useForm, usePoll } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const APPS = ['GoPay', 'OVO', 'DANA', 'ShopeePay', 'LinkAja', 'm-banking'];

function useCountdown(target) {
    const [remaining, setRemaining] = useState(() => (target ? Math.max(0, new Date(target) - Date.now()) : 0));

    useEffect(() => {
        if (!target) {
            return undefined;
        }

        const tick = () => setRemaining(Math.max(0, new Date(target) - Date.now()));
        tick();
        const timer = setInterval(tick, 1000);

        return () => clearInterval(timer);
    }, [target]);

    return remaining;
}

function formatRemaining(milliseconds) {
    const totalSeconds = Math.ceil(milliseconds / 1000);

    return `${String(Math.floor(totalSeconds / 60)).padStart(2, '0')}:${String(totalSeconds % 60).padStart(2, '0')}`;
}

function NoticeCard({ icon, iconClass = 'text-primary', title, children }) {
    return (
        <section className="flex flex-col items-center gap-2 rounded-3xl bg-surface-container-lowest p-space-lg text-center shadow-card">
            <Icon name={icon} size={32} className={iconClass} />
            <p className="font-display text-headline-sm text-on-surface">{title}</p>
            {children}
        </section>
    );
}

/**
 * Satu langkah bernomor pada alur bayar QRIS statis.
 */
function Step({ number, title, children }) {
    return (
        <section className="flex flex-col gap-3 rounded-3xl bg-surface-container-lowest p-space-md shadow-card">
            <div className="flex items-center gap-2">
                <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary text-label-md text-on-primary">{number}</span>
                <h2 className="font-display text-headline-sm text-on-surface">{title}</h2>
            </div>
            {children}
        </section>
    );
}

/**
 * Langkah 3 & 4: unggah screenshot/foto bukti bayar (wajib, dengan pratinjau) lalu kirim.
 * Bukti diteruskan ke admin lewat Telegram agar konfirmasi cepat.
 */
function ProofForm({ orderCode, amount }) {
    const { data, setData, post, processing, errors, progress } = useForm({ proof: null, sender_account_name: '' });
    const [preview, setPreview] = useState(null);

    useEffect(() => () => preview && URL.revokeObjectURL(preview), [preview]);

    const pick = (file) => {
        setData('proof', file ?? null);
        setPreview(file ? URL.createObjectURL(file) : null);
    };

    const submit = (event) => {
        event.preventDefault();
        post(route('orders.manual.claim', orderCode), { forceFormData: true, preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="flex flex-col gap-space-md">
            <Step number={3} title="Upload bukti pembayaran">
                <p className="text-body-sm text-on-surface-variant">Ambil screenshot layar “Pembayaran berhasil” dari aplikasimu, lalu pilih di sini.</p>

                <label className="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-outline-variant bg-surface-container-low p-3 text-center hover:border-primary">
                    {preview ? (
                        <img src={preview} alt="Pratinjau bukti pembayaran" className="max-h-64 rounded-xl object-contain" />
                    ) : (
                        <>
                            <Icon name="add_photo_alternate" size={32} className="text-primary" />
                            <span className="text-label-md text-on-surface">Pilih screenshot / foto bukti bayar</span>
                            <span className="text-label-sm font-normal text-on-surface-variant">JPG, PNG, atau WEBP • maks. 5 MB</span>
                        </>
                    )}
                    <input type="file" accept="image/jpeg,image/png,image/webp" className="sr-only" onChange={(event) => pick(event.target.files?.[0])} />
                </label>
                {preview && (
                    <button type="button" onClick={() => pick(null)} className="-mt-1 self-center text-label-sm text-primary underline underline-offset-2">
                        Ganti gambar
                    </button>
                )}
                {errors.proof && <p className="text-label-sm text-error">{errors.proof}</p>}

                <input
                    value={data.sender_account_name}
                    onChange={(event) => setData('sender_account_name', event.target.value)}
                    maxLength={80}
                    placeholder="Nama akun pengirim (opsional), mis. Budi S."
                    className="w-full rounded-xl border-0 bg-surface-container-low px-3 py-2.5 text-body-md focus:ring-2 focus:ring-primary/40"
                />
            </Step>

            <Step number={4} title="Kirim bukti">
                <p className="text-body-sm text-on-surface-variant">
                    Pastikan kamu sudah membayar <strong className="text-on-surface">{rupiah(amount)}</strong>. Setelah dikirim, admin mencocokkan pembayaranmu dan kartu
                    langsung terbit begitu dikonfirmasi.
                </p>
                {errors.order && <p className="text-label-sm text-error">{errors.order}</p>}

                <button
                    type="submit"
                    disabled={processing || !data.proof}
                    className="flex h-12 w-full items-center justify-center gap-2 rounded-full bg-primary text-label-lg text-on-primary shadow-md active:scale-95 disabled:opacity-50"
                >
                    <Icon name={processing ? 'progress_activity' : 'send'} size={20} className={processing ? 'animate-spin' : ''} />
                    {processing && progress ? `Mengunggah ${progress.percentage}%` : 'Kirim Bukti Pembayaran'}
                </button>
                {!data.proof && <p className="text-center text-label-sm font-normal text-on-surface-variant">Pilih gambar bukti di langkah 3 dulu.</p>}
            </Step>
        </form>
    );
}

/**
 * Mode alternatif: bukti dikirim lewat WhatsApp admin. Tombol membuka chat dengan pesan yang sudah
 * berisi kode pesanan & nominal, sekaligus menandai pesanan "sedang dicek" agar admin mendapat notifikasi.
 */
function WhatsAppProof({ orderCode, whatsappUrl }) {
    const claim = () => router.post(route('orders.manual.claim', orderCode), {}, { preserveScroll: true });

    return (
        <Step number={3} title="Kirim bukti pembayaran">
            <p className="text-body-sm text-on-surface-variant">Kirim screenshot bukti pembayaranmu ke admin lewat WhatsApp. Pesan berisi kode pesanan sudah disiapkan.</p>
            <a
                href={whatsappUrl}
                target="_blank"
                rel="noopener noreferrer"
                onClick={claim}
                className="flex h-12 w-full items-center justify-center gap-2 rounded-full bg-whatsapp text-label-lg text-white shadow-md active:scale-95"
            >
                <Icon name="chat" size={20} />
                Kirim Bukti via WhatsApp
            </a>
        </Step>
    );
}

/**
 * QRIS statis milik merchant, dipandu langkah demi langkah: scan → bayar nominal tepat → upload bukti → kirim.
 * Nominal unik ditonjolkan dan bisa disalin karena harus diketik pembeli. Admin lalu mencocokkan mutasi dan menekan Konfirmasi.
 */
function ManualPayment({ manual, orderCode }) {
    const [copied, setCopied] = useState(false);

    const copyAmount = () => {
        navigator.clipboard?.writeText(String(manual.amount));
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <>
            <Step number={1} title="Scan QRIS">
                <p className="text-body-sm text-on-surface-variant">
                    Buka aplikasi e-wallet / m-banking ({APPS.join(', ')}), pilih <strong className="text-on-surface">Scan / Bayar</strong>, lalu arahkan ke QR ini. Kalau
                    membuka dari HP yang sama, simpan gambarnya lalu pilih dari galeri.
                </p>
                <img src={manual.image_url} alt={`QRIS ${manual.merchant_name}`} className="w-full max-w-[280px] self-center rounded-2xl bg-white p-2 shadow-inner" />
                <p className="text-center text-label-sm font-normal text-on-surface-variant">
                    Pastikan nama merchant: <strong className="text-on-surface">{manual.merchant_name}</strong>
                </p>
                <a
                    href={manual.image_url}
                    download
                    className="flex h-10 items-center gap-1.5 self-center rounded-full bg-primary-fixed px-4 text-label-md text-on-primary-fixed active:scale-95"
                >
                    <Icon name="download" size={18} />
                    Simpan gambar QR
                </a>
            </Step>

            <Step number={2} title="Bayar dengan nominal tepat">
                <div className="flex w-full flex-col items-center gap-1 rounded-2xl bg-tertiary-fixed/60 p-space-sm text-center">
                    <span className="text-label-sm text-on-tertiary-fixed">Ketik nominal tepat sebesar</span>
                    <button type="button" onClick={copyAmount} className="flex items-center gap-2 font-display text-headline-lg-mobile text-on-surface active:scale-95">
                        {rupiah(manual.amount)}
                        <Icon name={copied ? 'check' : 'content_copy'} size={20} className="text-primary" />
                    </button>
                    <span className="max-w-xs text-label-sm font-normal text-on-tertiary-fixed">
                        {copied ? 'Nominal disalin!' : `Termasuk kode unik Rp${manual.unique_code} agar pembayaranmu langsung kami kenali. Jangan dibulatkan, ya.`}
                    </span>
                </div>
                <p className="text-body-sm text-on-surface-variant">QRIS ini tidak mengisi nominal otomatis. Selesaikan pembayaran di aplikasimu sampai muncul tanda berhasil.</p>
            </Step>

            {manual.proof_channel === 'whatsapp' ? <WhatsAppProof orderCode={orderCode} whatsappUrl={manual.whatsapp_url} /> : <ProofForm orderCode={orderCode} amount={manual.amount} />}
        </>
    );
}


/**
 * Bayar dengan QRIS dinamis. Pembayaran terdeteksi otomatis (webhook + cek berkala di server);
 * halaman ini hanya memuat ulang status dari server kita, tidak pernah langsung ke penyedia QRIS.
 */
export default function Show({ order, greeting, isQrisReady, canSimulate }) {
    const qris = order.qris;
    const isWaiting = order.status === 'menunggu';
    const manual = order.manual;
    const isScannable = isWaiting && qris?.is_scannable;
    const remaining = useCountdown(isScannable ? qris.expires_at : null);
    const [isBusy, setIsBusy] = useState(false);

    // QRIS dinamis: menunggu lunas otomatis. QRIS statis: menunggu konfirmasi admin setelah "Saya sudah bayar".
    usePoll(5000, { only: ['order'] }, { autoStart: (isWaiting && Boolean(qris)) || order.is_awaiting_review });

    useEffect(() => {
        if (isScannable && remaining === 0) {
            router.reload({ only: ['order'] });
        }
    }, [isScannable, remaining]);

    // Tetap di posisi gulir bila masih di halaman ini; ke atas bila berpindah ke halaman kartu yang terbit.
    const post = (name) =>
        router.post(route(name, order.code), {}, { preserveScroll: (page) => page.component === 'Orders/Show', onStart: () => setIsBusy(true), onFinish: () => setIsBusy(false) });

    return (
        <PublicLayout title={`Bayar ${order.code}`} back={{ href: route('dashboard'), label: 'Kartu Saya' }}>
            <div className="flex flex-col gap-space-md px-margin py-space-md">
                <section className="flex flex-col items-center gap-1 text-center">
                    <span className="rounded-full bg-surface-container px-3 py-1 text-label-sm text-on-surface-variant">
                        {manual && isWaiting ? 'Selesaikan pembayaran' : order.status_label} • {order.code}
                    </span>
                    <h1 className="mt-1 font-display text-display-hero-mobile text-on-surface">{rupiah(manual ? manual.amount : order.amount)}</h1>
                    <p className="max-w-sm text-body-sm text-on-surface-variant">
                        Kartu <strong className="text-on-surface">{greeting.template_name}</strong> untuk <strong className="text-on-surface">{greeting.recipient_name}</strong>
                    </p>
                </section>

                {order.rejection_note && isWaiting && (
                    <div role="alert" className="flex items-start gap-2 rounded-2xl bg-error-container px-4 py-3 text-on-error-container">
                        <Icon name="error" size={20} className="mt-0.5 shrink-0" />
                        <div className="text-body-sm">
                            <p className="text-label-md">Pembayaran sebelumnya belum kami temukan</p>
                            <p>{order.rejection_note}</p>
                        </div>
                    </div>
                )}

                {order.status === 'batal' ? (
                    <NoticeCard icon="timer_off" iconClass="text-error" title="Pesanan ini sudah kedaluwarsa">
                        <p className="text-body-sm text-on-surface-variant">Ucapanmu aman dan kembali menjadi draf. Kamu bisa membuat pesanan baru kapan saja.</p>
                        <Link href={route('checkout.show', greeting.id)} className="mt-1 rounded-full bg-primary px-5 py-2.5 text-label-md text-on-primary">
                            Buat pesanan baru
                        </Link>
                    </NoticeCard>
                ) : order.is_awaiting_review ? (
                    <NoticeCard icon="hourglass_top" iconClass="text-tertiary" title="Menunggu konfirmasi pembayaran admin">
                        <p className="text-body-sm text-on-surface-variant">
                            Bukti pembayaranmu sudah kami terima dan sedang dicocokkan admin. Biarkan halaman ini terbuka — begitu dikonfirmasi, kamu langsung diarahkan ke link &
                            QR kartumu.
                        </p>
                        <p className="flex items-center gap-1.5 text-label-sm font-normal text-on-surface-variant">
                            <span className="h-2 w-2 animate-pulse rounded-full bg-primary" />
                            Menunggu konfirmasi admin
                        </p>
                        {order.review_whatsapp_url && (
                            <a
                                href={order.review_whatsapp_url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="mt-1 flex h-11 items-center gap-1.5 rounded-full bg-whatsapp px-5 text-label-md text-white shadow-sm active:scale-95"
                            >
                                <Icon name="chat" size={18} />
                                Belum kirim bukti? Buka WhatsApp lagi
                            </a>
                        )}
                    </NoticeCard>
                ) : !isQrisReady ? (
                    <NoticeCard icon="construction" iconClass="text-tertiary" title="Pembayaran belum aktif">
                        <p className="text-body-sm text-on-surface-variant">Pembayaran QRIS sedang disiapkan. Pesananmu tersimpan — coba lagi nanti atau hubungi admin.</p>
                    </NoticeCard>
                ) : manual && isWaiting ? (
                    <ManualPayment manual={manual} orderCode={order.code} />
                ) : isScannable ? (
                    <>
                        <section className="flex flex-col items-center gap-3 rounded-3xl bg-surface-container-lowest p-space-md shadow-card">
                            <div className="flex w-full items-center justify-between">
                                <span className="flex items-center gap-1.5 font-display text-headline-sm text-on-surface">
                                    <Icon name="qr_code_2" size={22} className="text-primary" />
                                    QRIS
                                </span>
                                <span
                                    className={`flex items-center gap-1 rounded-full px-3 py-1 text-label-md ${remaining < 5 * 60 * 1000 ? 'bg-error-container text-on-error-container' : 'bg-surface-container text-on-surface'}`}
                                >
                                    <Icon name="timer" size={16} />
                                    {formatRemaining(remaining)}
                                </span>
                            </div>

                            <div
                                className="w-full max-w-[280px] rounded-2xl bg-white p-2 shadow-inner [&_svg]:h-auto [&_svg]:w-full"
                                role="img"
                                aria-label={`QRIS untuk membayar ${rupiah(order.amount)}`}
                                dangerouslySetInnerHTML={{ __html: qris.svg }}
                            />

                            <p className="text-center text-body-sm text-on-surface-variant">
                                Nominal <strong className="text-on-surface">{rupiah(order.amount)}</strong> sudah terisi otomatis. Bisa dibayar lewat {APPS.join(', ')}.
                            </p>

                            <div className="grid w-full grid-cols-1 gap-2 sm:grid-cols-2">
                                <a
                                    href={route('orders.qris.image', order.code)}
                                    className="flex h-12 items-center justify-center gap-2 rounded-full bg-primary-fixed text-label-lg text-on-primary-fixed active:scale-95"
                                >
                                    <Icon name="download" size={20} />
                                    Simpan gambar QR
                                </a>
                                <button
                                    type="button"
                                    disabled={isBusy}
                                    onClick={() => post('orders.qris.check')}
                                    className="flex h-12 items-center justify-center gap-2 rounded-full bg-primary text-label-lg text-on-primary shadow-md active:scale-95 disabled:opacity-60"
                                >
                                    <Icon name={isBusy ? 'progress_activity' : 'task_alt'} size={20} className={isBusy ? 'animate-spin' : ''} />
                                    Saya sudah bayar
                                </button>
                            </div>

                            <p className="flex items-center gap-1.5 text-label-sm font-normal text-on-surface-variant">
                                <span className="h-2 w-2 animate-pulse rounded-full bg-primary" />
                                Menunggu pembayaran — halaman ini berpindah otomatis setelah lunas.
                            </p>
                        </section>

                        <section className="flex flex-col gap-2 rounded-3xl bg-surface-container-low p-space-md">
                            <p className="text-label-lg text-on-surface">Cara bayar</p>
                            <ol className="flex flex-col gap-2 text-body-sm text-on-surface-variant">
                                <li className="flex gap-2">
                                    <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-label-sm text-on-primary">1</span>
                                    <span>
                                        <strong className="text-on-surface">Dari HP lain:</strong> buka aplikasi e-wallet / m-banking → Scan / Bayar → arahkan ke QR di atas.
                                    </span>
                                </li>
                                <li className="flex gap-2">
                                    <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-label-sm text-on-primary">2</span>
                                    <span>
                                        <strong className="text-on-surface">Di HP ini:</strong> ketuk “Simpan gambar QR”, buka aplikasi e-wallet / m-banking → Scan → pilih gambar dari galeri.
                                    </span>
                                </li>
                                <li className="flex gap-2">
                                    <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-label-sm text-on-primary">3</span>
                                    <span>Pastikan nama merchant dan nominal sesuai, lalu bayar. Kartu langsung terbit tanpa perlu kirim bukti.</span>
                                </li>
                            </ol>
                        </section>
                    </>
                ) : (
                    <NoticeCard icon="qr_code_scanner" title={qris ? 'QR sudah kedaluwarsa' : 'Siapkan QR pembayaran'}>
                        <p className="text-body-sm text-on-surface-variant">
                            {qris
                                ? 'QRIS hanya berlaku 30 menit. Jika kamu sudah membayar, tunggu sebentar — status akan diperbarui otomatis. Jika belum, buat QR baru.'
                                : 'Buat QR untuk membayar pesanan ini.'}
                        </p>
                        <div className="mt-1 flex flex-wrap justify-center gap-2">
                            <button
                                type="button"
                                disabled={isBusy}
                                onClick={() => post('orders.qris.refresh')}
                                className="flex h-11 items-center gap-1.5 rounded-full bg-primary px-5 text-label-md text-on-primary disabled:opacity-60"
                            >
                                <Icon name="refresh" size={18} />
                                Buat QR baru
                            </button>
                            {qris && (
                                <button
                                    type="button"
                                    disabled={isBusy}
                                    onClick={() => post('orders.qris.check')}
                                    className="flex h-11 items-center gap-1.5 rounded-full bg-surface-container px-5 text-label-md text-on-surface disabled:opacity-60"
                                >
                                    <Icon name="task_alt" size={18} />
                                    Saya sudah bayar
                                </button>
                            )}
                        </div>
                    </NoticeCard>
                )}

                {canSimulate && isScannable && (
                    <button
                        type="button"
                        onClick={() => post('orders.qris.simulate')}
                        className="rounded-2xl border-2 border-dashed border-tertiary px-4 py-3 text-label-md text-tertiary"
                    >
                        🧪 Simulasikan pembayaran berhasil (mode pengembangan)
                    </button>
                )}

                <p className="flex items-center justify-center gap-1.5 text-center text-label-sm font-normal text-on-surface-variant">
                    <Icon name="lock" size={14} />
                    {manual
                        ? `Dibayar langsung ke merchant ${manual.merchant_name}. Bukti bayar hanya dipakai untuk mencocokkan pembayaranmu.`
                        : 'Dibayar langsung ke merchant QRIS resmi. Kami tidak pernah meminta kirim bukti transfer.'}
                </p>
            </div>
        </PublicLayout>
    );
}
