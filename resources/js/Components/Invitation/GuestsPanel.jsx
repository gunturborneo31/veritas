import Icon from '@/Components/Icon';
import { EmptyState, inputClass } from '@/Components/Invitation/Field';
import { CheckInSheet, GuestFormSheet, ImportGuestsSheet } from '@/Components/Invitation/GuestSheets';
import { ATTENDANCE } from '@/Components/Invitation/SummaryPanel';
import { time } from '@/lib/format';
import { router } from '@inertiajs/react';
import { useMemo, useState } from 'react';

const STATUS_FILTERS = [
    ['all', 'Semua'],
    ['unsent', 'Belum dikirim'],
    ['awaiting', 'Belum RSVP'],
    ['hadir', 'Hadir'],
    ['tidak_hadir', 'Tidak hadir'],
];

const matchesStatus = (guest, status) =>
    ({
        all: true,
        unsent: guest.invited_at === null,
        awaiting: guest.rsvp === null,
        hadir: guest.rsvp?.attendance === 'hadir',
        tidak_hadir: guest.rsvp?.attendance === 'tidak_hadir',
    })[status];

function GuestRow({ invitation, guest, readOnly, onEdit, onCopy }) {
    const attendance = guest.rsvp ? ATTENDANCE[guest.rsvp.attendance] : null;

    const markInvited = () => router.post(route('invitations.guests.invited', [invitation.id, guest.id]), {}, { preserveScroll: true, preserveState: true });

    const remove = () => {
        if (confirm(`Hapus ${guest.name} dari daftar tamu? Link pribadinya tidak akan berlaku lagi.`)) {
            router.delete(route('invitations.guests.destroy', [invitation.id, guest.id]), { preserveScroll: true });
        }
    };

    return (
        <li className="flex flex-col gap-space-sm rounded-3xl bg-surface-container-lowest p-space-md shadow-card">
            <div className="flex items-start justify-between gap-space-sm">
                <div className="min-w-0">
                    <p className="truncate text-label-lg text-on-surface">{guest.name}</p>
                    <p className="truncate text-label-sm font-normal text-on-surface-variant">
                        {[guest.group, `${guest.quota} orang`, guest.phone].filter(Boolean).join(' • ')}
                    </p>
                </div>
                {!readOnly && (
                    <div className="flex shrink-0 gap-1">
                        <button type="button" onClick={() => onEdit(guest)} aria-label={`Ubah ${guest.name}`} className="flex h-8 w-8 items-center justify-center rounded-full text-on-surface-variant hover:bg-surface-container">
                            <Icon name="edit" size={18} />
                        </button>
                        <button type="button" onClick={remove} aria-label={`Hapus ${guest.name}`} className="flex h-8 w-8 items-center justify-center rounded-full text-error hover:bg-error-container/40">
                            <Icon name="delete_outline" size={18} />
                        </button>
                    </div>
                )}
            </div>

            <div className="flex flex-wrap gap-1.5">
                <span className={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-label-sm ${attendance?.tone ?? 'bg-surface-container text-on-surface-variant'}`}>
                    <Icon name={attendance?.icon ?? 'hourglass_empty'} size={14} />
                    {attendance ? `${attendance.label}${guest.rsvp.attendance === 'hadir' ? ` • ${guest.rsvp.party_size} org` : ''}` : 'Belum RSVP'}
                </span>
                {guest.invited_at && (
                    <span className="inline-flex items-center gap-1 rounded-full bg-surface-container px-2.5 py-0.5 text-label-sm text-on-surface-variant">
                        <Icon name="send" size={14} /> Terkirim
                    </span>
                )}
                {guest.opened_at && (
                    <span className="inline-flex items-center gap-1 rounded-full bg-tertiary-fixed px-2.5 py-0.5 text-label-sm text-on-tertiary-fixed">
                        <Icon name="visibility" size={14} /> Dibuka
                    </span>
                )}
                {guest.checked_in_at && (
                    <span className="inline-flex items-center gap-1 rounded-full bg-primary-fixed px-2.5 py-0.5 text-label-sm text-on-primary-fixed">
                        <Icon name="how_to_reg" size={14} /> Check-in {time(guest.checked_in_at)}
                    </span>
                )}
            </div>

            {guest.url ? (
                <div className="flex items-center gap-space-xs">
                    <a
                        href={guest.whatsapp_url}
                        target="_blank"
                        rel="noopener noreferrer"
                        onClick={markInvited}
                        className="flex h-10 flex-1 items-center justify-center gap-1.5 rounded-full bg-whatsapp text-label-md text-white shadow-sm active:scale-95"
                    >
                        <Icon name="chat" size={18} />
                        {guest.invited_at ? 'Kirim Ulang' : 'Kirim via WhatsApp'}
                    </a>
                    <button
                        type="button"
                        onClick={() => onCopy(guest.url)}
                        aria-label="Salin link tamu"
                        className="flex h-10 w-10 items-center justify-center rounded-full bg-surface-container text-on-surface-variant hover:text-primary"
                    >
                        <Icon name="content_copy" size={18} />
                    </button>
                    <a
                        href={guest.qr_download_url}
                        download
                        aria-label="Unduh QR check-in"
                        className="flex h-10 w-10 items-center justify-center rounded-full bg-surface-container text-on-surface-variant hover:text-primary"
                    >
                        <Icon name="qr_code_2" size={18} />
                    </a>
                </div>
            ) : (
                <p className="rounded-2xl bg-surface-container-low px-space-sm py-2 text-label-sm font-normal text-on-surface-variant">
                    Link pribadi aktif setelah undangan terbit.
                </p>
            )}
        </li>
    );
}

/**
 * Daftar tamu: cari, saring status, tambah/impor, kirim WhatsApp satu per satu, dan check-in.
 */
export default function GuestsPanel({ invitation, readOnly, onCopy }) {
    const [query, setQuery] = useState('');
    const [status, setStatus] = useState('all');
    const [group, setGroup] = useState(null);
    const [editing, setEditing] = useState(null);
    const [sheet, setSheet] = useState(null);

    const visible = useMemo(() => {
        const needle = query.trim().toLowerCase();

        return invitation.guests.filter(
            (guest) => matchesStatus(guest, status) && (group === null || guest.group === group) && (needle === '' || guest.name.toLowerCase().includes(needle)),
        );
    }, [invitation.guests, query, status, group]);

    const openForm = (guest = null) => {
        setEditing(guest);
        setSheet('form');
    };

    return (
        <div className="flex flex-col gap-space-md">
            {!readOnly && (
                <div className="grid grid-cols-2 gap-space-xs">
                    <button
                        type="button"
                        onClick={() => openForm()}
                        className="flex h-11 items-center justify-center gap-1.5 rounded-full bg-primary text-label-md text-on-primary shadow-md active:scale-95"
                    >
                        <Icon name="person_add" size={18} /> Tambah Tamu
                    </button>
                    <button
                        type="button"
                        onClick={() => setSheet('import')}
                        className="flex h-11 items-center justify-center gap-1.5 rounded-full bg-surface-container text-label-md text-on-surface active:scale-95"
                    >
                        <Icon name="upload_file" size={18} className="text-primary" /> Impor dari Excel
                    </button>
                    {invitation.url && (
                        <button
                            type="button"
                            onClick={() => setSheet('check-in')}
                            className="col-span-2 flex h-11 items-center justify-center gap-1.5 rounded-full bg-inverse-surface text-label-md text-inverse-on-surface active:scale-95"
                        >
                            <Icon name="qr_code_scanner" size={18} /> Mode Check-in Tamu
                        </button>
                    )}
                </div>
            )}

            {invitation.guests.length === 0 ? (
                <EmptyState icon="group_add" title="Belum ada tamu" body="Tambahkan satu per satu atau tempel daftar dari Excel. Setiap tamu mendapat link dengan namanya sendiri." />
            ) : (
                <>
                    <div className="relative">
                        <Icon name="search" size={20} className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant" />
                        <input
                            type="search"
                            value={query}
                            onChange={(event) => setQuery(event.target.value)}
                            placeholder={`Cari di ${invitation.guests.length} tamu…`}
                            className={`${inputClass} pl-11`}
                        />
                    </div>

                    <div className="no-scrollbar flex gap-space-xs overflow-x-auto py-1">
                        {STATUS_FILTERS.map(([key, label]) => (
                            <button
                                key={key}
                                type="button"
                                onClick={() => setStatus(key)}
                                className={`h-9 whitespace-nowrap rounded-full px-space-md text-label-md shadow-sm ${
                                    status === key ? 'bg-on-surface text-surface-container-lowest' : 'bg-surface-container-low text-on-surface-variant'
                                }`}
                            >
                                {label} <span className="opacity-70">{invitation.guests.filter((guest) => matchesStatus(guest, key)).length}</span>
                            </button>
                        ))}
                    </div>

                    {invitation.groups.length > 0 && (
                        <div className="no-scrollbar -mt-space-sm flex gap-1.5 overflow-x-auto">
                            {[null, ...invitation.groups].map((item) => (
                                <button
                                    key={item ?? 'semua-grup'}
                                    type="button"
                                    onClick={() => setGroup(item)}
                                    className={`whitespace-nowrap rounded-full px-3 py-1 text-label-sm ${
                                        group === item ? 'bg-primary-fixed text-on-primary-fixed' : 'bg-surface-container text-on-surface-variant'
                                    }`}
                                >
                                    {item ?? 'Semua grup'}
                                </button>
                            ))}
                        </div>
                    )}

                    <ul className="flex flex-col gap-space-sm">
                        {visible.map((guest) => (
                            <GuestRow key={guest.id} invitation={invitation} guest={guest} readOnly={readOnly} onEdit={openForm} onCopy={onCopy} />
                        ))}
                        {visible.length === 0 && <p className="py-space-lg text-center text-body-sm text-on-surface-variant">Tidak ada tamu yang cocok.</p>}
                    </ul>
                </>
            )}

            <GuestFormSheet invitation={invitation} guest={editing} open={sheet === 'form'} onClose={() => setSheet(null)} />
            <ImportGuestsSheet invitation={invitation} open={sheet === 'import'} onClose={() => setSheet(null)} />
            <CheckInSheet invitation={invitation} open={sheet === 'check-in'} onClose={() => setSheet(null)} />
        </div>
    );
}
