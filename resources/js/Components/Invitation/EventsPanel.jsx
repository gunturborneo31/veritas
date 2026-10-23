import BottomSheet from '@/Components/BottomSheet';
import Icon from '@/Components/Icon';
import Field, { EmptyState, inputClass, Suggestions, SubmitButton } from '@/Components/Invitation/Field';
import { router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const MAX_EVENTS = 5;

const EVENT_NAMES = ['Akad Nikah', 'Pemberkatan', 'Resepsi', 'Ngunduh Mantu', 'Tasyakuran', 'Lamaran'];

const EMPTY_EVENT = { name: '', starts_at: '', ends_at: '', timezone: 'Asia/Jakarta', venue_name: '', address: '', maps_url: '', livestream_url: '' };

/**
 * Waktu disimpan sebagai jam dinding di lokasi acara ("2026-12-12T09:00"), jadi ditampilkan apa adanya.
 */
const formatWallClock = (value) =>
    new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(value));

const formatClock = (value) => value.slice(11, 16).replace(':', '.');

function EventFormSheet({ invitation, event, open, onClose }) {
    const { data, setData, post, put, processing, errors, clearErrors } = useForm(EMPTY_EVENT);

    useEffect(() => {
        if (open) {
            clearErrors();
            setData(event ? Object.fromEntries(Object.keys(EMPTY_EVENT).map((key) => [key, event[key] ?? ''])) : EMPTY_EVENT);
        }
    }, [open, event]);

    const submit = (formEvent) => {
        formEvent.preventDefault();
        const options = { preserveScroll: true, onSuccess: onClose };

        event ? put(route('invitations.events.update', [invitation.id, event.id]), options) : post(route('invitations.events.store', invitation.id), options);
    };

    return (
        <BottomSheet open={open} onClose={onClose} title={event ? 'Ubah Acara' : 'Tambah Acara'} icon="event">
            <form onSubmit={submit} className="-mx-1 flex max-h-[70vh] flex-col gap-space-md overflow-y-auto px-1 pb-1">
                <Field label="Nama acara" error={errors.name}>
                    <input value={data.name} onChange={(e) => setData('name', e.target.value)} maxLength={80} className={inputClass} placeholder="Akad Nikah" />
                    <Suggestions items={EVENT_NAMES} onPick={(name) => setData('name', name)} />
                </Field>
                <div className="grid grid-cols-2 gap-space-sm">
                    <Field label="Mulai" error={errors.starts_at}>
                        <input type="datetime-local" value={data.starts_at} onChange={(e) => setData('starts_at', e.target.value)} className={`${inputClass} px-3`} />
                    </Field>
                    <Field label="Selesai (opsional)" error={errors.ends_at}>
                        <input type="datetime-local" value={data.ends_at} onChange={(e) => setData('ends_at', e.target.value)} className={`${inputClass} px-3`} />
                    </Field>
                </div>
                <Field label="Zona waktu" error={errors.timezone} hint="Isi jam sesuai waktu setempat di lokasi acara.">
                    <div className="grid grid-cols-3 gap-space-xs">
                        {Object.entries(invitation.timezones).map(([timezone, label]) => (
                            <button
                                key={timezone}
                                type="button"
                                onClick={() => setData('timezone', timezone)}
                                className={`h-10 rounded-full text-label-md ${data.timezone === timezone ? 'bg-primary text-on-primary' : 'bg-surface-container text-on-surface-variant'}`}
                            >
                                {label}
                            </button>
                        ))}
                    </div>
                </Field>
                <Field label="Nama tempat" error={errors.venue_name}>
                    <input value={data.venue_name} onChange={(e) => setData('venue_name', e.target.value)} maxLength={120} className={inputClass} placeholder="Gedung Serbaguna Melati" />
                </Field>
                <Field label="Alamat lengkap (opsional)" error={errors.address}>
                    <textarea rows={2} value={data.address} onChange={(e) => setData('address', e.target.value)} maxLength={500} className={`${inputClass} resize-none`} />
                </Field>
                <Field label="Link Google Maps (opsional)" error={errors.maps_url} hint="Buka lokasi di Google Maps → Bagikan → Salin link.">
                    <input type="url" value={data.maps_url} onChange={(e) => setData('maps_url', e.target.value)} className={inputClass} placeholder="https://maps.app.goo.gl/…" />
                </Field>
                <Field label="Link live streaming (opsional)" error={errors.livestream_url}>
                    <input type="url" value={data.livestream_url} onChange={(e) => setData('livestream_url', e.target.value)} className={inputClass} placeholder="https://youtube.com/live/…" />
                </Field>
                <SubmitButton processing={processing}>
                    <Icon name="check" size={20} />
                    {event ? 'Simpan Acara' : 'Tambah Acara'}
                </SubmitButton>
            </form>
        </BottomSheet>
    );
}

/**
 * Rangkaian acara. Bisa diubah kapan saja; tamu langsung melihat jadwal terbaru.
 */
export default function EventsPanel({ invitation, readOnly }) {
    const { errors } = usePage().props;
    const [editing, setEditing] = useState(null);
    const [isFormOpen, setIsFormOpen] = useState(false);

    const openForm = (event = null) => {
        setEditing(event);
        setIsFormOpen(true);
    };

    const remove = (event) => {
        if (confirm(`Hapus acara ${event.name}?`)) {
            router.delete(route('invitations.events.destroy', [invitation.id, event.id]), { preserveScroll: true });
        }
    };

    return (
        <div className="flex flex-col gap-space-md">
            {errors.event && <p className="rounded-2xl bg-error-container px-space-sm py-2 text-label-md text-on-error-container">{errors.event}</p>}

            {invitation.events.length === 0 ? (
                <EmptyState icon="event" title="Belum ada acara" body="Tambahkan akad, resepsi, atau acara lain. Undangan baru bisa dibayar setelah minimal satu acara diisi." />
            ) : (
                <ul className="flex flex-col gap-space-sm">
                    {invitation.events.map((event) => (
                        <li key={event.id} className="flex flex-col gap-space-sm rounded-3xl bg-surface-container-lowest p-space-md shadow-card">
                            <div className="flex items-start justify-between gap-space-sm">
                                <div className="flex min-w-0 gap-3">
                                    <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-primary-fixed text-primary">
                                        <Icon name="celebration" size={22} />
                                    </div>
                                    <div className="min-w-0">
                                        <p className="text-label-lg text-on-surface">{event.name}</p>
                                        <p className="text-body-sm text-on-surface-variant">{formatWallClock(event.starts_at)}</p>
                                        <p className="text-body-sm text-on-surface-variant">
                                            {formatClock(event.starts_at)}
                                            {event.ends_at ? ` – ${formatClock(event.ends_at)}` : ' – selesai'} {invitation.timezones[event.timezone]}
                                        </p>
                                    </div>
                                </div>
                                {!readOnly && (
                                    <div className="flex shrink-0 gap-1">
                                        <button type="button" onClick={() => openForm(event)} aria-label={`Ubah ${event.name}`} className="flex h-8 w-8 items-center justify-center rounded-full text-on-surface-variant hover:bg-surface-container">
                                            <Icon name="edit" size={18} />
                                        </button>
                                        <button type="button" onClick={() => remove(event)} aria-label={`Hapus ${event.name}`} className="flex h-8 w-8 items-center justify-center rounded-full text-error hover:bg-error-container/40">
                                            <Icon name="delete_outline" size={18} />
                                        </button>
                                    </div>
                                )}
                            </div>
                            <div className="flex items-start gap-2 rounded-2xl bg-surface-container-low px-space-sm py-2">
                                <Icon name="location_on" size={18} className="mt-0.5 shrink-0 text-primary" />
                                <div className="min-w-0 text-body-sm">
                                    <p className="text-label-md text-on-surface">{event.venue_name}</p>
                                    {event.address && <p className="text-on-surface-variant">{event.address}</p>}
                                </div>
                            </div>
                            {(event.maps_url || event.livestream_url) && (
                                <div className="flex flex-wrap gap-1.5">
                                    {event.maps_url && (
                                        <a href={event.maps_url} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-1 rounded-full bg-surface-container px-3 py-1 text-label-sm text-on-surface-variant">
                                            <Icon name="map" size={14} /> Peta
                                        </a>
                                    )}
                                    {event.livestream_url && (
                                        <a href={event.livestream_url} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-1 rounded-full bg-surface-container px-3 py-1 text-label-sm text-on-surface-variant">
                                            <Icon name="live_tv" size={14} /> Live streaming
                                        </a>
                                    )}
                                </div>
                            )}
                        </li>
                    ))}
                </ul>
            )}

            {!readOnly && invitation.events.length < MAX_EVENTS && (
                <button
                    type="button"
                    onClick={() => openForm()}
                    className="flex h-11 items-center justify-center gap-1.5 rounded-full border-2 border-dashed border-outline-variant text-label-md text-on-surface-variant hover:border-primary hover:text-primary"
                >
                    <Icon name="add" size={18} /> Tambah Acara
                </button>
            )}

            <EventFormSheet invitation={invitation} event={editing} open={isFormOpen} onClose={() => setIsFormOpen(false)} />
        </div>
    );
}
