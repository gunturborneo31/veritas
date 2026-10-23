import FlashToast from '@/Components/FlashToast';
import Icon from '@/Components/Icon';
import EventsPanel from '@/Components/Invitation/EventsPanel';
import GiftsPanel from '@/Components/Invitation/GiftsPanel';
import GuestsPanel from '@/Components/Invitation/GuestsPanel';
import SettingsPanel from '@/Components/Invitation/SettingsPanel';
import SummaryPanel from '@/Components/Invitation/SummaryPanel';
import WishesPanel from '@/Components/Invitation/WishesPanel';
import PublicLayout from '@/Layouts/PublicLayout';
import { dateTime } from '@/lib/format';
import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const TABS = [
    ['ringkasan', 'Ringkasan', 'insights'],
    ['tamu', 'Tamu', 'group'],
    ['acara', 'Acara', 'event'],
    ['amplop', 'Amplop', 'redeem'],
    ['ucapan', 'Ucapan & Doa', 'forum'],
    ['pengaturan', 'Pengaturan', 'tune'],
];

const initialTab = () => {
    const hash = typeof window === 'undefined' ? '' : window.location.hash.slice(1);

    return TABS.some(([key]) => key === hash) ? hash : null;
};

function StatusBanner({ invitation, onCopy }) {
    if (invitation.has_expired) {
        return (
            <div className="flex items-center gap-3 rounded-2xl bg-surface-container-high px-4 py-3 text-on-surface-variant">
                <Icon name="history" size={22} className="shrink-0" />
                <p className="text-body-sm">Masa aktif undangan sudah berakhir. Data tamu, RSVP, dan ucapan tetap bisa kamu lihat.</p>
            </div>
        );
    }

    if (invitation.is_draft) {
        return (
            <Link
                href={route('studio.edit', invitation.id)}
                className="flex items-center gap-3 rounded-2xl bg-tertiary-fixed px-4 py-3 text-on-tertiary-fixed shadow-sm active:scale-[0.99]"
            >
                <Icon name="edit_note" size={22} className="shrink-0" />
                <span className="min-w-0 flex-1">
                    <span className="block text-label-md">Undangan belum terbit</span>
                    <span className="block text-body-sm opacity-80">
                        Isi acara & daftar tamu sekarang. Link tamu aktif setelah pembayaran.
                        {invitation.events.length === 0 && ' Minimal satu acara wajib diisi.'}
                    </span>
                </span>
                <span className="shrink-0 text-label-md underline">Lanjut edit</span>
            </Link>
        );
    }

    if (!invitation.url) {
        return (
            <div className="flex items-center gap-3 rounded-2xl bg-surface-container-low px-4 py-3 text-on-surface-variant">
                <Icon name="hourglass_top" size={22} className="shrink-0" />
                <p className="text-body-sm">Pembayaran sedang diproses. Link undangan muncul di sini setelah terbit.</p>
            </div>
        );
    }

    return (
        <div className="flex flex-col gap-space-sm rounded-2xl bg-surface-container-low p-space-sm">
            <div className="flex items-center gap-space-xs">
                <Icon name="link" size={18} className="shrink-0 text-primary" />
                <span className="min-w-0 flex-1 truncate text-label-md text-on-surface">{invitation.url.replace(/^https?:\/\//, '')}</span>
                <button type="button" onClick={() => onCopy(invitation.url)} className="rounded-full bg-surface-container-lowest px-3 py-1.5 text-label-sm text-primary shadow-sm">
                    Salin
                </button>
                <a href={invitation.url} target="_blank" rel="noopener noreferrer" aria-label="Buka undangan" className="rounded-full bg-surface-container-lowest p-1.5 text-primary shadow-sm">
                    <Icon name="open_in_new" size={16} />
                </a>
            </div>
            <p className="px-1 text-label-sm font-normal text-on-surface-variant">Link umum tanpa nama tamu. Untuk undangan pribadi, kirim dari tab Tamu.</p>
            {invitation.is_revisable && (
                <Link
                    href={route('studio.edit', invitation.id)}
                    className="flex items-center gap-2 rounded-xl bg-surface-container-lowest px-3 py-2 text-on-surface shadow-sm active:scale-[0.99]"
                >
                    <Icon name="edit_note" size={18} className="shrink-0 text-primary" />
                    <span className="min-w-0 flex-1 text-label-md">
                        Revisi isi undangan
                        <span className="block text-label-sm font-normal text-on-surface-variant">Nama, foto & cerita — {invitation.revisable_until ? `sampai ${dateTime(invitation.revisable_until)}` : 'kapan saja'}</span>
                    </span>
                    <Icon name="arrow_forward" size={18} className="shrink-0 text-primary" />
                </Link>
            )}
        </div>
    );
}

/**
 * Kelola undangan: ringkasan RSVP, daftar tamu & kirim WhatsApp, acara, amplop digital,
 * ucapan & doa, dan pengaturan. Tab aktif disimpan di hash URL agar bisa ditautkan langsung.
 */
export default function Manage({ invitation }) {
    const [tab, setTab] = useState(() => initialTab() ?? (invitation.is_draft && invitation.events.length === 0 ? 'acara' : 'ringkasan'));
    const [copiedAt, setCopiedAt] = useState(null);
    const readOnly = invitation.has_expired;

    useEffect(() => {
        window.history.replaceState(window.history.state, '', `#${tab}`);
    }, [tab]);

    const copy = (url) => {
        navigator.clipboard?.writeText(url);
        setCopiedAt(Date.now());
    };

    const badgeFor = (key) =>
        ({
            tamu: invitation.summary.guests,
            acara: invitation.events.length,
            amplop: invitation.gifts.length,
            ucapan: invitation.wishes.length,
        })[key];

    return (
        <PublicLayout title="Kelola Undangan" back={{ href: route('dashboard'), label: 'Kartu Saya' }}>
            <FlashToast message={copiedAt ? 'Tautan berhasil disalin!' : null} key={copiedAt} />

            <section className="flex flex-col gap-space-md px-margin pb-space-md pt-space-md">
                <div>
                    <div className="mb-1 inline-flex items-center gap-space-xs">
                        <Icon name="local_florist" size={20} className="text-tertiary-container" />
                        <span className="text-label-md font-semibold uppercase tracking-wide text-tertiary">Undangan {invitation.occasion}</span>
                    </div>
                    <h1 className="text-headline-lg-mobile font-extrabold tracking-tight text-on-surface">{invitation.host_name || 'Undangan tanpa nama'}</h1>
                    <p className="mt-0.5 text-body-sm text-on-surface-variant">
                        {invitation.template_name} • {invitation.status_label}
                        {invitation.active_until && !invitation.has_expired && ` • aktif s.d. ${dateTime(invitation.active_until)}`}
                    </p>
                </div>
                <StatusBanner invitation={invitation} onCopy={copy} />
            </section>

            <nav aria-label="Bagian kelola undangan" className="sticky top-16 z-30 bg-surface/95 px-margin py-2 backdrop-blur-xl">
                <div className="no-scrollbar flex gap-space-xs overflow-x-auto">
                    {TABS.map(([key, label, icon]) => (
                        <button
                            key={key}
                            type="button"
                            onClick={() => setTab(key)}
                            aria-current={tab === key ? 'page' : undefined}
                            className={`flex h-9 shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full px-space-md text-label-md shadow-sm active:scale-95 ${
                                tab === key ? 'bg-on-surface text-surface-container-lowest' : 'bg-surface-container-low text-on-surface-variant'
                            }`}
                        >
                            <Icon name={icon} size={16} />
                            {label}
                            {badgeFor(key) > 0 && <span className="rounded-full bg-surface-container-highest/40 px-1.5 py-0.5 text-xs">{badgeFor(key)}</span>}
                        </button>
                    ))}
                </div>
            </nav>

            <section className="px-margin pb-24 pt-space-sm">
                {tab === 'ringkasan' && <SummaryPanel invitation={invitation} onNavigate={setTab} />}
                {tab === 'tamu' && <GuestsPanel invitation={invitation} readOnly={readOnly} onCopy={copy} />}
                {tab === 'acara' && <EventsPanel invitation={invitation} readOnly={readOnly} />}
                {tab === 'amplop' && <GiftsPanel invitation={invitation} readOnly={readOnly} />}
                {tab === 'ucapan' && <WishesPanel invitation={invitation} readOnly={readOnly} />}
                {tab === 'pengaturan' && <SettingsPanel invitation={invitation} readOnly={readOnly} />}
            </section>
        </PublicLayout>
    );
}
