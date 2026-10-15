import FlashToast from '@/Components/FlashToast';
import Icon from '@/Components/Icon';
import { resolveTheme, themeVars } from '@/lib/theme';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

const inputClass = 'w-full rounded-2xl border-0 bg-white/80 px-4 py-3 text-body-md text-on-surface shadow-sm focus:ring-2 focus:ring-primary/40';

/**
 * Halaman patungan: teman pengirim menitip pesan (dan foto) tanpa membuat akun.
 */
export default function Show({ token, isOpen, greeting, template }) {
    const theme = resolveTheme(template.theme);
    const [sent, setSent] = useState(false);
    const form = useForm({ contributor_name: '', message: '', photo: null });

    const submit = (event) => {
        event.preventDefault();
        form.post(route('contributions.store', token), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setSent(true);
            },
        });
    };

    return (
        <div
            className="flex min-h-screen w-full flex-col items-center px-margin py-space-xl"
            style={{ ...themeVars(theme), background: `linear-gradient(180deg, ${theme.gradient.join(', ')})` }}
        >
            <Head title={`Titip pesan untuk ${greeting.recipient_name || 'kejutan'}`}>
                <meta name="robots" content="noindex" />
            </Head>
            <FlashToast />

            <div className="flex w-full max-w-md flex-col gap-space-md">
                <div className="flex flex-col items-center gap-2 text-center">
                    <span className="text-5xl">{theme.emoji}</span>
                    <span className="rounded-full bg-white/70 px-3 py-1 text-label-sm uppercase tracking-[0.2em] text-primary shadow-sm">Kartu Patungan</span>
                    <h1 className="font-display text-headline-lg-mobile leading-tight text-on-surface">
                        {greeting.sender_name || 'Temanmu'} sedang menyiapkan kejutan {greeting.occasion?.toLowerCase()} untuk {greeting.recipient_name || 'seseorang'}
                    </h1>
                    <p className="text-body-sm text-on-surface-variant">
                        Titip satu pesan (dan foto kalau mau). Semua pesan akan muncul di dinding ucapan saat {greeting.recipient_name || 'dia'} membuka kartunya. 🤫 Jangan bocorin ya!
                    </p>
                    {greeting.contribution_count > 0 && (
                        <p className="flex items-center gap-1 text-label-md text-primary">
                            <Icon name="groups" size={18} />
                            {greeting.contribution_count} orang sudah ikut menulis
                        </p>
                    )}
                </div>

                {!isOpen ? (
                    <div className="rounded-3xl bg-white/80 p-6 text-center shadow-card">
                        <Icon name="lock_clock" size={32} className="text-primary" />
                        <p className="mt-2 font-display text-headline-sm text-on-surface">Pengumpulan pesan sudah ditutup</p>
                        <p className="text-body-sm text-on-surface-variant">Kartunya sudah dikunci dan siap dikirim. Terima kasih sudah mau ikut!</p>
                    </div>
                ) : sent ? (
                    <div className="flex flex-col items-center gap-3 rounded-3xl bg-white/85 p-6 text-center shadow-card">
                        <span className="text-5xl">💌</span>
                        <p className="font-display text-headline-sm text-on-surface">Pesanmu sudah tertitip!</p>
                        <p className="text-body-sm text-on-surface-variant">Kamu bisa menutup halaman ini. Kejutannya akan sampai bersama ucapan dari {greeting.sender_name || 'temanmu'}.</p>
                        <button type="button" onClick={() => setSent(false)} className="text-label-md text-primary">
                            Tulis pesan lain
                        </button>
                    </div>
                ) : (
                    <form onSubmit={submit} className="flex flex-col gap-3 rounded-3xl bg-white/60 p-5 shadow-card backdrop-blur">
                        <input
                            type="text"
                            maxLength={60}
                            placeholder="Namamu"
                            value={form.data.contributor_name}
                            onChange={(event) => form.setData('contributor_name', event.target.value)}
                            className={inputClass}
                        />
                        {form.errors.contributor_name && <p className="text-label-sm text-error">{form.errors.contributor_name}</p>}
                        <textarea
                            rows={5}
                            maxLength={500}
                            placeholder={`Tulis pesan untuk ${greeting.recipient_name || 'dia'}…`}
                            value={form.data.message}
                            onChange={(event) => form.setData('message', event.target.value)}
                            className={`${inputClass} resize-none`}
                        />
                        <div className="flex justify-between text-label-sm text-on-surface-variant">
                            {form.errors.message ? <span className="text-error">{form.errors.message}</span> : <span />}
                            <span>{form.data.message.length}/500</span>
                        </div>
                        <label className="flex cursor-pointer items-center gap-3 rounded-2xl border border-dashed border-primary/40 bg-white/60 p-3">
                            <Icon name="add_photo_alternate" size={22} className="text-primary" />
                            <span className="flex-1 truncate text-body-sm text-on-surface-variant">{form.data.photo ? form.data.photo.name : 'Tambah foto bareng (opsional)'}</span>
                            <input type="file" accept="image/*" className="hidden" onChange={(event) => form.setData('photo', event.target.files[0] ?? null)} />
                        </label>
                        {form.errors.photo && <p className="text-label-sm text-error">{form.errors.photo}</p>}
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="flex h-12 items-center justify-center gap-2 rounded-full bg-primary text-label-lg text-on-primary shadow-lg shadow-primary/30 disabled:opacity-60"
                        >
                            <Icon name={form.processing ? 'progress_activity' : 'send'} size={18} className={form.processing ? 'animate-spin' : ''} />
                            Titipkan pesan
                        </button>
                    </form>
                )}

                <p className="text-center text-label-sm text-on-surface-variant">Dibuat dengan KartuKita • Ucapan digital interaktif</p>
            </div>
        </div>
    );
}
