import Icon from '@/Components/Icon';
import { dateTime } from '@/lib/format';
import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { isInteractive, ManagedHint } from './invitationShared';
import { SceneCard, SceneLabel } from './shared';

const OPTIONS = [
    ['hadir', 'Hadir', 'celebration'],
    ['tidak_hadir', 'Tidak Bisa', 'sentiment_dissatisfied'],
    ['ragu', 'Masih Ragu', 'help'],
];

const LABELS = { hadir: 'akan hadir', tidak_hadir: 'tidak bisa hadir', ragu: 'masih ragu' };

/**
 * Konfirmasi kehadiran. Tamu dengan link pribadi tidak perlu menulis nama; jumlah orang dibatasi kuotanya.
 * Mengirim ulang memperbarui jawaban sebelumnya.
 */
export default function Rsvp({ slot, values, mode, slug, invitation }) {
    const { errors } = usePage().props;
    const rsvp = invitation?.rsvp;
    const guest = invitation?.guest;
    const [isEditing, setIsEditing] = useState(false);
    const [form, setForm] = useState({ name: '', attendance: rsvp?.response?.attendance ?? 'hadir', party_size: rsvp?.response?.party_size || 1 });
    const [processing, setProcessing] = useState(false);
    const canSubmit = isInteractive(mode, slug);

    if (!rsvp) {
        return null;
    }

    const submit = (event) => {
        event.preventDefault();

        if (!canSubmit) {
            return;
        }

        router.post(route('recipient.rsvp', slug), form, {
            preserveScroll: true,
            preserveState: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
            onSuccess: () => setIsEditing(false),
        });
    };

    const showSummary = rsvp.response && !isEditing;

    return (
        <SceneCard className="text-left">
            <SceneLabel icon="event_available">{values.title || slot?.title || 'Konfirmasi Kehadiran'}</SceneLabel>
            <ManagedHint mode={mode}>Batas waktu & kuota orang diatur di Kelola Undangan → Pengaturan.</ManagedHint>

            {guest && (
                <p className="text-body-sm text-on-surface">
                    Halo <strong>{guest.name}</strong>, undangan ini berlaku untuk {guest.quota} orang.
                </p>
            )}
            {values.note && <p className="text-body-sm text-on-surface-variant">{values.note}</p>}
            {rsvp.deadline && rsvp.is_open && <p className="text-label-sm text-on-surface-variant">Konfirmasi paling lambat {dateTime(rsvp.deadline)}.</p>}

            {!rsvp.is_open ? (
                <p className="rounded-xl bg-surface-container px-3 py-2 text-body-sm text-on-surface-variant">Konfirmasi kehadiran sudah ditutup. Terima kasih 🙏</p>
            ) : showSummary ? (
                <div className="flex flex-col items-center gap-2 rounded-2xl bg-primary-fixed/50 px-4 py-4 text-center">
                    <Icon name={rsvp.response.attendance === 'hadir' ? 'celebration' : 'favorite'} size={32} className="text-primary" />
                    <p className="text-label-lg text-on-surface">
                        Kamu {LABELS[rsvp.response.attendance]}
                        {rsvp.response.attendance === 'hadir' && ` (${rsvp.response.party_size} orang)`}
                    </p>
                    <p className="text-label-sm text-on-surface-variant">Terima kasih sudah mengonfirmasi.</p>
                    <button type="button" onClick={() => setIsEditing(true)} className="text-label-md text-primary underline underline-offset-2">
                        Ubah jawaban
                    </button>
                </div>
            ) : (
                <form onSubmit={submit} className="flex flex-col gap-3">
                    {!guest && (
                        <div>
                            <input
                                value={form.name}
                                onChange={(event) => setForm({ ...form, name: event.target.value })}
                                maxLength={80}
                                placeholder="Nama kamu"
                                className="w-full rounded-xl border-0 bg-surface-container-low px-3 py-2.5 text-body-md focus:ring-2 focus:ring-primary/40"
                            />
                            {errors.name && <p className="mt-1 text-label-sm text-error">{errors.name}</p>}
                        </div>
                    )}
                    <div className="grid grid-cols-3 gap-2">
                        {OPTIONS.map(([value, label, icon]) => (
                            <button
                                key={value}
                                type="button"
                                onClick={() => setForm({ ...form, attendance: value })}
                                aria-pressed={form.attendance === value}
                                className={`flex flex-col items-center gap-1 rounded-2xl px-1 py-3 text-label-sm transition-colors ${
                                    form.attendance === value ? 'bg-primary text-on-primary shadow-md' : 'bg-surface-container text-on-surface-variant'
                                }`}
                            >
                                <Icon name={icon} size={22} />
                                {label}
                            </button>
                        ))}
                    </div>
                    {errors.attendance && <p className="text-label-sm text-error">{errors.attendance}</p>}

                    {form.attendance === 'hadir' && (
                        <div className="flex items-center justify-between rounded-2xl bg-surface-container-low px-3 py-2">
                            <span className="text-label-md text-on-surface">Jumlah orang</span>
                            <div className="flex items-center gap-3">
                                <button
                                    type="button"
                                    aria-label="Kurangi"
                                    disabled={form.party_size <= 1}
                                    onClick={() => setForm({ ...form, party_size: form.party_size - 1 })}
                                    className="flex h-8 w-8 items-center justify-center rounded-full bg-surface-container-lowest shadow-sm disabled:opacity-40"
                                >
                                    <Icon name="remove" size={18} />
                                </button>
                                <span className="w-6 text-center text-headline-sm text-on-surface">{form.party_size}</span>
                                <button
                                    type="button"
                                    aria-label="Tambah"
                                    disabled={form.party_size >= rsvp.max_party_size}
                                    onClick={() => setForm({ ...form, party_size: form.party_size + 1 })}
                                    className="flex h-8 w-8 items-center justify-center rounded-full bg-surface-container-lowest shadow-sm disabled:opacity-40"
                                >
                                    <Icon name="add" size={18} />
                                </button>
                            </div>
                        </div>
                    )}
                    {errors.party_size && <p className="text-label-sm text-error">{errors.party_size}</p>}

                    <button
                        type="submit"
                        disabled={processing || !canSubmit}
                        className="flex items-center justify-center gap-2 rounded-full bg-primary py-3 text-label-lg text-on-primary shadow-md active:scale-[0.98] disabled:opacity-60"
                    >
                        <Icon name="send" size={18} />
                        {canSubmit ? 'Kirim Konfirmasi' : 'Contoh — tamu mengisi di sini'}
                    </button>
                </form>
            )}
        </SceneCard>
    );
}
