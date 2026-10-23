import LinkPicker from '@/Components/Checkout/LinkPicker';
import PromoCodeField from '@/Components/Checkout/PromoCodeField';
import Icon from '@/Components/Icon';
import TemplateCover from '@/Components/TemplateCover';
import StudioLayout from '@/Layouts/StudioLayout';
import { dateTime, rupiah } from '@/lib/format';
import { Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

const accountInputClass =
    'w-full rounded-2xl border-0 bg-surface-container-low px-4 py-3 text-body-md text-on-surface placeholder:text-on-surface-variant/60 focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary/40';

/**
 * Data akun untuk pembeli yang belum login. Akun dibuat & login otomatis saat menekan Bayar,
 * sehingga pembeli tidak perlu pindah ke halaman daftar.
 */
function AccountFields({ form }) {
    const [showPassword, setShowPassword] = useState(false);

    const field = (name, label, props) => (
        <label className="flex flex-col gap-1">
            <span className="text-label-md text-on-surface">{label}</span>
            <input {...props} value={form.data[name]} onChange={(event) => form.setData(name, event.target.value)} className={accountInputClass} />
            {form.errors[name] && <span className="text-label-sm text-error">{form.errors[name]}</span>}
        </label>
    );

    return (
        <section className="flex flex-col gap-space-sm rounded-3xl bg-surface-container-lowest p-space-md shadow-card">
            <div className="flex items-center justify-between gap-2">
                <div className="flex items-center gap-2">
                    <Icon name="person" size={20} className="text-primary" />
                    <h2 className="text-headline-sm text-on-surface">Data Pemesan</h2>
                </div>
                <Link href={route('login')} className="text-label-md text-primary underline-offset-2 hover:underline">
                    Sudah punya akun? Masuk
                </Link>
            </div>
            <p className="text-body-sm text-on-surface-variant">Akunmu dibuat otomatis saat membayar, supaya kartu bisa kamu kelola dan buka lagi kapan saja.</p>
            {field('name', 'Nama', { autoComplete: 'name', maxLength: 80, placeholder: 'Nama kamu' })}
            {field('email', 'Email', { type: 'email', autoComplete: 'email', placeholder: 'nama@email.com' })}
            {field('phone', 'No. WhatsApp', { type: 'tel', inputMode: 'tel', autoComplete: 'tel', maxLength: 20, placeholder: '08xxxxxxxxxx' })}
            <label className="flex flex-col gap-1">
                <span className="text-label-md text-on-surface">Buat Password</span>
                <div className="relative">
                    <input
                        type={showPassword ? 'text' : 'password'}
                        autoComplete="new-password"
                        value={form.data.password}
                        onChange={(event) => form.setData('password', event.target.value)}
                        placeholder="Minimal 8 karakter"
                        className={`${accountInputClass} pr-12`}
                    />
                    <button
                        type="button"
                        onClick={() => setShowPassword((value) => !value)}
                        aria-label={showPassword ? 'Sembunyikan password' : 'Tampilkan password'}
                        className="absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant"
                    >
                        <Icon name={showPassword ? 'visibility_off' : 'visibility'} size={20} />
                    </button>
                </div>
                {form.errors.password && <span className="text-label-sm text-error">{form.errors.password}</span>}
            </label>
        </section>
    );
}

/**
 * Langkah 5 — Ringkasan & Bayar. Harga ditampilkan dari server; browser tidak mengirim harga.
 */
export default function Show({ greeting, template, quote, missing, referralHint, link, needsAccount = false, isManualPayment = false }) {
    const form = useForm({ confirm_data: false, slug: link?.slug ?? '', name: '', email: '', phone: '', password: '' });
    const isComplete = missing.length === 0;
    const hasAccountData = !needsAccount || (form.data.name && form.data.email && form.data.phone && form.data.password);

    const submit = () => form.post(route('checkout.store', greeting.id), { preserveScroll: true });

    return (
        <StudioLayout
            title="Ringkasan & Bayar"
            greetingId={greeting.id}
            step={4}
            onBack={() => router.visit(route('preview.show', greeting.id))}
            actions={
                <div className="flex w-full items-center justify-between gap-space-md">
                    <div className="flex flex-col">
                        <span className="text-label-sm text-on-surface-variant">Total Pembayaran</span>
                        <span className="text-headline-lg-mobile font-bold text-primary">{rupiah(quote.total)}</span>
                    </div>
                    <button
                        type="button"
                        onClick={submit}
                        disabled={!form.data.confirm_data || !isComplete || !hasAccountData || form.processing}
                        className="flex h-12 max-w-[220px] flex-1 items-center justify-center gap-2 rounded-full bg-primary text-label-lg font-bold text-on-primary shadow-md shadow-primary/25 transition-all hover:bg-primary-container active:scale-95 disabled:opacity-50"
                    >
                        <Icon name={form.processing ? 'progress_activity' : 'lock'} size={18} className={form.processing ? 'animate-spin' : ''} />
                        <span>Bayar dengan QRIS</span>
                    </button>
                </div>
            }
        >
            <div className="flex flex-col gap-space-lg px-margin">
                <div className="flex items-center justify-center gap-1.5 text-center">
                    <Icon name="lock" size={15} className="text-tertiary" />
                    <span className="text-label-sm font-medium text-on-surface-variant">Harga dihitung & dikunci di server</span>
                </div>

                <section className="flex flex-col gap-space-md rounded-3xl bg-surface-container-lowest p-space-md shadow-card">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <Icon name="celebration" size={20} className="text-primary" />
                            <h2 className="text-headline-sm text-on-surface">Ringkasan Kartu Ucapan</h2>
                        </div>
                        <Link href={route('studio.edit', greeting.id)} className="flex items-center gap-1 rounded-full px-2 py-1 text-primary hover:bg-surface-container-low">
                            <Icon name="edit" size={16} />
                            <span className="text-label-sm font-semibold">Ubah data</span>
                        </Link>
                    </div>
                    <div className="flex items-center gap-space-md rounded-2xl bg-surface-container-low/60 p-space-sm">
                        <div className="relative h-24 w-20 shrink-0 overflow-hidden rounded-xl shadow-sm">
                            <TemplateCover template={template} />
                        </div>
                        <div className="flex min-w-0 flex-1 flex-col gap-1">
                            <div className="flex items-center gap-1.5">
                                <span className="rounded-full bg-primary/10 px-2 py-0.5 text-label-sm font-semibold text-primary">Penerima</span>
                                <p className="truncate text-headline-sm text-on-surface">{greeting.recipient_name}</p>
                            </div>
                            <p className="flex items-center gap-1 text-body-sm text-on-surface-variant">
                                <Icon name="cake" size={16} className="text-tertiary" /> {greeting.occasion}
                            </p>
                            <p className="flex items-center gap-1 text-label-sm text-on-surface-variant/80">
                                <Icon name="palette" size={15} className="text-primary" /> {template.name} • {greeting.photo_count} foto
                                {greeting.has_voice && ' • pesan suara'}
                                {greeting.has_gate && ' • gembok'}
                            </p>
                            {greeting.opens_at && (
                                <p className="flex items-center gap-1 text-label-sm font-semibold text-primary">
                                    <Icon name="schedule_send" size={15} /> Terbuka mulai {dateTime(greeting.opens_at)}
                                </p>
                            )}
                        </div>
                    </div>

                    <label className="flex cursor-pointer select-none items-start gap-3 rounded-xl bg-surface p-space-sm">
                        <input
                            type="checkbox"
                            checked={form.data.confirm_data}
                            onChange={(event) => form.setData('confirm_data', event.target.checked)}
                            className="mt-0.5 h-5 w-5 rounded-md text-primary focus:ring-primary"
                        />
                        <div className="min-w-0 flex-1">
                            <div className="mb-0.5 flex items-center gap-2">
                                <span className="text-label-md font-semibold text-on-surface">Konfirmasi Data Kartu</span>
                                <span className="rounded-full bg-primary px-1.5 text-[11px] uppercase tracking-wider text-on-primary">Wajib</span>
                            </div>
                            <p className="text-body-sm text-on-surface-variant">Saya sudah memeriksa ejaan nama, tanggal, dan pesan di pratinjau.</p>
                        </div>
                    </label>
                    {form.errors.confirm_data && <p className="text-label-sm text-error">{form.errors.confirm_data}</p>}
                    {form.errors.greeting && <p className="text-label-sm text-error">{form.errors.greeting}</p>}
                    {!isComplete && <p className="text-label-sm text-error">Belum lengkap: {missing.map((item) => item.label).join(', ')}</p>}
                </section>

                {link && <LinkPicker greetingId={greeting.id} link={link} value={form.data.slug} onChange={(slug) => form.setData('slug', slug)} error={form.errors.slug} />}

                {needsAccount && <AccountFields form={form} />}

                <section className="flex gap-3 rounded-2xl bg-primary-fixed/40 p-space-md">
                    <Icon name="info" size={22} className="mt-0.5 shrink-0 text-primary" />
                    <div>
                        <h4 className="mb-1 text-label-lg font-bold text-on-primary-fixed">Catatan Penting Sebelum Bayar</h4>
                        <p className="text-body-sm leading-relaxed text-on-surface-variant">
                            Begitu pembayaran QRIS berhasil, kartu langsung terbit dan nama penerima, foto, pesan, musik, serta gembok di dalamnya terkunci permanen. Satu
                            pembayaran berlaku untuk satu penerima.
                        </p>
                    </div>
                </section>

                <section className="flex flex-col gap-space-sm">
                    <div className="flex items-center justify-between">
                        <h2 className="text-headline-sm text-on-surface">Metode Pembayaran</h2>
                        <span className="rounded-full bg-primary-fixed px-2 py-0.5 text-label-sm font-bold text-on-primary-fixed">{isManualPayment ? 'QRIS' : 'Otomatis'}</span>
                    </div>
                    <div className="flex flex-col gap-1 rounded-3xl bg-surface-container-lowest p-space-md shadow-sm">
                        <div className="flex items-center gap-3">
                            <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-primary-fixed text-primary">
                                <Icon name="qr_code_2" size={24} />
                            </span>
                            <span>
                                <span className="block text-label-lg text-on-surface">QRIS</span>
                                <span className="block text-body-sm text-on-surface-variant">GoPay, OVO, DANA, ShopeePay, LinkAja, dan semua m-banking</span>
                            </span>
                        </div>
                        <p className="pt-2 text-label-sm font-normal text-on-surface-variant">
                            {isManualPayment
                                ? 'QRIS dan nominal yang harus dibayar muncul setelah pesanan dibuat. Setelah bayar, kirim bukti pembayaran — kartu terbit begitu admin mengonfirmasi.'
                                : 'QR dengan nominal pas muncul setelah pesanan dibuat. Tidak perlu kirim bukti — kartu terbit otomatis begitu pembayaran masuk.'}
                        </p>
                    </div>
                </section>

                <section className="flex flex-col gap-space-sm rounded-3xl bg-surface-container-lowest p-space-md shadow-sm">
                    <h2 className="text-headline-sm text-on-surface">Rincian Pembayaran</h2>
                    {needsAccount ? (
                        <p className="text-label-sm font-normal text-on-surface-variant">Punya kode promo? Masuk ke akunmu dulu untuk memakainya.</p>
                    ) : (
                        <PromoCodeField greetingId={greeting.id} quote={quote} referralHint={referralHint} />
                    )}
                    <div className="flex flex-col gap-2 text-body-sm text-on-surface-variant">
                        {quote.lines.map((line) => (
                            <div key={line.label} className="flex items-center justify-between">
                                <span>{line.label}</span>
                                <span className={line.amount < 0 ? 'font-semibold text-primary' : 'font-semibold text-on-surface'}>{line.amount < 0 ? `-${rupiah(-line.amount)}` : rupiah(line.amount)}</span>
                            </div>
                        ))}
                    </div>
                    <div className="my-1 h-px bg-surface-container-highest" />
                    <div className="flex items-center justify-between text-label-lg font-bold text-on-surface">
                        <span>Total Akhir</span>
                        <span className="text-headline-sm text-primary">{rupiah(quote.total)}</span>
                    </div>
                </section>
            </div>
        </StudioLayout>
    );
}
