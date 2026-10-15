import BottomSheet from '@/Components/BottomSheet';
import FlashToast from '@/Components/FlashToast';
import Icon from '@/Components/Icon';
import QrCode from '@/Components/QrCode';
import TemplateCover from '@/Components/TemplateCover';
import ActivityFeed from '@/Components/Dashboard/ActivityFeed';
import MomentReminders from '@/Components/Dashboard/MomentReminders';
import ReferralCard from '@/Components/Dashboard/ReferralCard';
import PublicLayout from '@/Layouts/PublicLayout';
import { dateTime, rupiah, time } from '@/lib/format';
import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

const FILTERS = [
    ['all', 'Semua'],
    ['terbit', 'Terbit'],
    ['bayar', 'Menunggu Bayar'],
    ['draf', 'Draf'],
];

const filterOf = (greeting) =>
    greeting.status === 'terbit' ? 'terbit' : ['menunggu_bayar', 'terkunci'].includes(greeting.status) ? 'bayar' : 'draf';

function GreetingCard({ greeting, onCopy, onShowQr }) {
    const [showReplies, setShowReplies] = useState(false);

    const deleteDraft = () => {
        if (confirm('Hapus draf ucapan ini?')) {
            router.delete(route('dashboard.greetings.destroy', greeting.id), { preserveScroll: true });
        }
    };

    return (
        <article className="flex flex-col gap-space-md rounded-3xl bg-surface-container-lowest p-space-md shadow-card transition-all hover:shadow-md">
            <div className="flex items-start gap-space-md">
                <div className="relative h-24 w-20 shrink-0 overflow-hidden rounded-2xl bg-surface-container-high shadow-inner">
                    <TemplateCover template={greeting.template} />
                </div>
                <div className="flex min-w-0 flex-1 flex-col justify-between">
                    <span className={`truncate text-headline-sm font-bold ${greeting.recipient_name ? 'text-on-surface' : 'italic text-on-surface-variant'}`}>
                        {greeting.recipient_name || 'Belum ada nama (Draf)'}
                    </span>
                    <span className="truncate text-body-sm text-on-surface-variant">
                        {greeting.occasion || '—'} • {greeting.template.name}
                    </span>
                    <div className="mt-2">
                        <span
                            className={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-label-sm font-semibold ${
                                greeting.status === 'terbit'
                                    ? 'bg-surface-container text-primary'
                                    : filterOf(greeting) === 'bayar'
                                      ? 'bg-error-container text-on-error-container'
                                      : 'bg-secondary-container text-on-secondary-container'
                            }`}
                        >
                            {greeting.status === 'terbit' && <span className="h-1.5 w-1.5 animate-pulse rounded-full bg-primary" />}
                            {greeting.status_label}
                        </span>
                    </div>
                </div>
            </div>

            {greeting.status === 'terbit' ? (
                <div className="flex items-center gap-space-xs rounded-2xl bg-surface-container-low px-space-sm py-2">
                    <Icon name={greeting.open_count ? 'visibility' : 'schedule'} filled={greeting.open_count > 0} size={18} className="text-primary" />
                    <span className="flex-1 truncate text-label-sm text-on-surface-variant">
                        {greeting.open_count
                            ? `Dibuka ${dateTime(greeting.last_opened_at)} (${greeting.open_count}x)`
                            : `Belum dibuka oleh ${greeting.recipient_name}`}
                    </span>
                    {greeting.replies.length > 0 && (
                        <button type="button" onClick={() => setShowReplies((open) => !open)} className="text-label-sm font-bold text-primary">
                            {greeting.replies.length} balasan
                        </button>
                    )}
                </div>
            ) : (
                <div className="flex items-center gap-space-xs rounded-2xl bg-surface-container-low px-space-sm py-2 text-on-surface-variant">
                    <Icon name="history" size={18} />
                    <span className="flex-1 truncate text-label-sm">Disimpan {dateTime(greeting.last_edited_at)}</span>
                </div>
            )}

            {greeting.opens_at && new Date(greeting.opens_at) > new Date() && (
                <div className="flex items-center gap-space-xs rounded-2xl bg-tertiary-fixed/50 px-space-sm py-2 text-on-tertiary-fixed">
                    <Icon name="schedule_send" size={18} />
                    <span className="text-label-sm">Terkunci sampai {dateTime(greeting.opens_at)}</span>
                </div>
            )}

            {greeting.videos.length > 0 && (
                <div className="flex flex-col gap-2">
                    {greeting.videos.map((video) => (
                        <div key={video.id} className="overflow-hidden rounded-2xl bg-inverse-surface">
                            <div className="flex items-center justify-between px-3 py-2 text-inverse-on-surface">
                                <span className="flex items-center gap-1 text-label-sm">
                                    <Icon name="videocam" size={16} className="text-tertiary-fixed-dim" />
                                    Video reaksi {greeting.recipient_name} • {dateTime(video.created_at)}
                                </span>
                                <button
                                    type="button"
                                    onClick={() => confirm('Hapus video reaksi ini?') && router.delete(route('reaction-videos.destroy', video.id), { preserveScroll: true })}
                                    aria-label="Hapus video"
                                    className="opacity-70 hover:opacity-100"
                                >
                                    <Icon name="delete_outline" size={18} />
                                </button>
                            </div>
                            <video src={video.url} controls playsInline preload="metadata" className="max-h-80 w-full bg-black" />
                        </div>
                    ))}
                </div>
            )}

            {(greeting.answers.length > 0 || greeting.emoji_reactions.length > 0) && (
                <div className="flex flex-wrap items-center gap-1.5">
                    {greeting.answers.map((answer, index) => (
                        <span key={index} className="inline-flex items-center gap-1 rounded-full bg-primary px-3 py-1 text-label-sm text-on-primary">
                            <Icon name="how_to_vote" size={14} />
                            Jawaban: {answer}
                        </span>
                    ))}
                    {greeting.emoji_reactions.map((reaction) => (
                        <span key={reaction.emoji} className="rounded-full bg-surface-container-low px-2.5 py-1 text-label-md">
                            {reaction.emoji} {reaction.count > 1 && reaction.count}
                        </span>
                    ))}
                </div>
            )}

            {showReplies && (
                <div className="flex flex-col gap-2">
                    {greeting.replies.map((reply) => (
                        <div key={reply.id} className="rounded-2xl bg-primary-fixed/40 p-3">
                            <p className="text-body-sm text-on-surface">{reply.message}</p>
                            <p className="mt-1 text-label-sm text-on-surface-variant">
                                — {reply.sender_name || 'Penerima'}, {dateTime(reply.created_at)}
                            </p>
                        </div>
                    ))}
                </div>
            )}

            <div className="flex items-center gap-space-xs pt-1">
                {greeting.status === 'terbit' && (
                    <>
                        <button
                            type="button"
                            onClick={() => onCopy(greeting.url)}
                            className="flex h-10 flex-1 items-center justify-center gap-1.5 rounded-full bg-surface-container text-label-md text-on-surface hover:bg-surface-container-high active:scale-95"
                        >
                            <Icon name="content_copy" size={18} className="text-primary" />
                            Salin Link
                        </button>
                        <button
                            type="button"
                            onClick={() => onShowQr(greeting)}
                            className="flex h-10 flex-1 items-center justify-center gap-1.5 rounded-full bg-surface-container text-label-md text-on-surface hover:bg-surface-container-high active:scale-95"
                        >
                            <Icon name="qr_code_2" size={18} className="text-primary" />
                            Lihat QR
                        </button>
                        <Link
                            href={route('result.show', greeting.id)}
                            aria-label="Detail"
                            className="flex h-10 w-10 items-center justify-center rounded-full bg-surface-container text-on-surface-variant hover:text-primary"
                        >
                            <Icon name="open_in_new" size={20} />
                        </Link>
                    </>
                )}
                {filterOf(greeting) === 'bayar' && greeting.order && (
                    <Link
                        href={route('orders.show', greeting.order.code)}
                        className="flex h-11 flex-1 items-center justify-center gap-2 rounded-full bg-primary text-label-lg text-on-primary shadow-md hover:bg-primary-container"
                    >
                        <Icon name="account_balance_wallet" size={20} />
                        {greeting.status === 'terkunci' ? 'Lihat Status' : `Bayar (${rupiah(greeting.order.amount)}) • s.d. ${time(greeting.order.expires_at)}`}
                    </Link>
                )}
                {['draf', 'pratinjau', 'menunggu_bayar'].includes(greeting.status) && (
                    <Link
                        href={route('studio.edit', greeting.id)}
                        className="flex h-10 flex-1 items-center justify-center gap-1.5 rounded-full bg-surface-container text-label-md text-on-surface hover:bg-surface-container-high"
                    >
                        <Icon name="edit" size={18} className="text-primary" />
                        {greeting.status === 'menunggu_bayar' ? 'Ubah Draf' : 'Lanjut Edit'}
                    </Link>
                )}
                <button
                    type="button"
                    aria-label="Duplikat untuk penerima lain"
                    title="Duplikat untuk penerima lain"
                    onClick={() => router.post(route('dashboard.greetings.duplicate', greeting.id))}
                    className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-surface-container text-on-surface-variant hover:text-primary"
                >
                    <Icon name="library_add" size={20} />
                </button>
                {['draf', 'pratinjau'].includes(greeting.status) && (
                    <button
                        type="button"
                        aria-label="Hapus draf"
                        onClick={deleteDraft}
                        className="flex h-10 w-10 items-center justify-center rounded-full bg-surface-container text-error hover:bg-error-container/40"
                    >
                        <Icon name="delete_outline" size={20} />
                    </button>
                )}
            </div>
        </article>
    );
}

/**
 * Dashboard akun: daftar ucapan, status, link/QR, balasan (desain dashboardakun).
 */
export default function Dashboard({ greetings, activities, reminders, referral }) {
    const { auth } = usePage().props;
    const [filter, setFilter] = useState('all');
    const [qrGreeting, setQrGreeting] = useState(null);
    const [toast, setToast] = useState(null);

    const counts = greetings.reduce((result, greeting) => ({ ...result, [filterOf(greeting)]: (result[filterOf(greeting)] ?? 0) + 1 }), { all: greetings.length });
    const visible = filter === 'all' ? greetings : greetings.filter((greeting) => filterOf(greeting) === filter);

    const copy = (url) => {
        navigator.clipboard?.writeText(url);
        setToast(`Tautan disalin! ${Date.now()}`);
    };

    return (
        <PublicLayout title="Kartu Saya">
            <FlashToast message={toast ? 'Tautan berhasil disalin!' : null} key={toast} />

            <section className="flex flex-col gap-space-md px-margin pb-space-lg pt-space-md">
                <div>
                    <div className="mb-1 inline-flex items-center gap-space-xs">
                        <Icon name="celebration" size={20} className="animate-bounce text-tertiary-container" />
                        <span className="text-label-md font-semibold uppercase tracking-wide text-tertiary">Dashboard Momen</span>
                    </div>
                    <h1 className="text-display-hero-mobile font-extrabold tracking-tight text-on-surface">Halo, {auth.user.name.split(' ')[0]}! ✨</h1>
                    <p className="mt-0.5 text-body-sm text-on-surface-variant">Kelola dan pantau momen spesial yang telah kamu buat.</p>
                </div>
                <div className="grid grid-cols-3 gap-space-xs">
                    {[
                        ['mail', 'bg-primary-fixed text-primary', greetings.length, 'Kartu Dibuat'],
                        ['verified', 'bg-surface-container-highest text-primary', counts.terbit ?? 0, 'Diterbitkan'],
                        ['favorite', 'bg-tertiary-fixed text-tertiary', greetings.filter((greeting) => greeting.open_count > 0).length, 'Dibuka Penerima'],
                    ].map(([icon, tone, value, label]) => (
                        <div key={label} className="flex flex-col items-center justify-center rounded-2xl bg-surface-container-low p-space-sm text-center shadow-sm">
                            <div className={`mb-1 flex h-7 w-7 items-center justify-center rounded-full ${tone}`}>
                                <Icon name={icon} size={16} />
                            </div>
                            <span className="text-headline-sm font-bold text-on-surface">{value}</span>
                            <span className="line-clamp-1 text-label-sm text-on-surface-variant">{label}</span>
                        </div>
                    ))}
                </div>
            </section>

            <ActivityFeed activities={activities} />

            <MomentReminders reminders={reminders} />

            <ReferralCard referral={referral} />

            <h2 className="px-margin pb-space-sm font-display text-headline-sm text-on-surface">Kartu kamu</h2>
            <section className="px-margin pb-space-md">
                <div className="no-scrollbar flex items-center gap-space-xs overflow-x-auto py-1">
                    {FILTERS.map(([key, label]) => (
                        <button
                            key={key}
                            type="button"
                            onClick={() => setFilter(key)}
                            className={`flex h-9 items-center gap-1.5 whitespace-nowrap rounded-full px-space-md text-label-md shadow-sm active:scale-95 ${
                                filter === key ? 'bg-on-surface text-surface-container-lowest' : 'bg-surface-container-low text-on-surface-variant'
                            }`}
                        >
                            {label}
                            <span className="rounded-full bg-surface-container-highest/40 px-1.5 py-0.5 text-[11px]">{counts[key] ?? 0}</span>
                        </button>
                    ))}
                </div>
            </section>

            <section className="flex flex-col gap-space-md px-margin pb-space-xl">
                {visible.length === 0 && (
                    <div className="flex flex-col items-center gap-2 py-space-xl text-center text-on-surface-variant">
                        <Icon name="drafts" size={40} />
                        <p className="text-body-sm">Belum ada ucapan di sini.</p>
                    </div>
                )}
                {visible.map((greeting) => (
                    <GreetingCard key={greeting.id} greeting={greeting} onCopy={copy} onShowQr={setQrGreeting} />
                ))}
            </section>

            <div className="fixed bottom-20 right-margin z-40">
                <Link
                    href={route('catalog.index')}
                    className="group flex h-13 items-center gap-space-sm rounded-full bg-gradient-to-r from-primary to-primary-container px-space-lg py-3 text-label-lg text-on-primary shadow-[0_8px_24px_rgba(168,50,35,0.38)] active:scale-95"
                >
                    <Icon name="auto_awesome" size={24} className="text-tertiary-fixed-dim transition-transform group-hover:rotate-12" />
                    <span className="font-bold tracking-tight">+ Buat Ucapan</span>
                </Link>
            </div>

            <BottomSheet open={qrGreeting !== null} onClose={() => setQrGreeting(null)} title="QR Kartu Ucapan" icon="qr_code_scanner">
                <p className="text-body-sm text-on-surface-variant">Untuk {qrGreeting?.recipient_name}</p>
                <div className="flex flex-col items-center rounded-2xl bg-surface-container-low p-space-md">
                    <div className="rounded-xl bg-surface-container-lowest p-3 shadow-sm">
                        <QrCode value={qrGreeting?.url} size={192} />
                    </div>
                    <span className="mt-2 text-center text-label-sm text-on-surface-variant">Arahkan kamera ponsel untuk membuka kartu</span>
                </div>
            </BottomSheet>
        </PublicLayout>
    );
}
