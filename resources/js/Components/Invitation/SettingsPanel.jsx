import Icon from '@/Components/Icon';
import Field, { inputClass, SubmitButton } from '@/Components/Invitation/Field';
import Toggle from '@/Components/Toggle';
import { useForm } from '@inertiajs/react';
import { useRef } from 'react';

function ToggleRow({ icon, title, body, checked, onChange, disabled }) {
    return (
        <div className="flex items-center gap-3 py-3">
            <Icon name={icon} size={22} className="shrink-0 text-primary" />
            <div className="min-w-0 flex-1">
                <p className="text-label-md text-on-surface">{title}</p>
                <p className="text-body-sm text-on-surface-variant">{body}</p>
            </div>
            <Toggle checked={checked} onChange={disabled ? () => {} : onChange} label={title} />
        </div>
    );
}

/**
 * Fitur yang tampil di halaman tamu, batas RSVP, kuota bawaan, dan template pesan WhatsApp.
 */
export default function SettingsPanel({ invitation, readOnly }) {
    const messageRef = useRef(null);
    const { data, setData, put, processing, errors, isDirty } = useForm({
        rsvp_enabled: invitation.settings.rsvp_enabled,
        rsvp_deadline: invitation.settings.rsvp_deadline ?? '',
        wishes_enabled: invitation.settings.wishes_enabled,
        gifts_enabled: invitation.settings.gifts_enabled,
        default_guest_quota: invitation.settings.default_guest_quota,
        broadcast_message: invitation.settings.broadcast_message,
    });

    const sampleGuest = invitation.guests[0];
    const preview = data.broadcast_message
        .replaceAll('{nama}', sampleGuest?.name ?? 'Bapak Budi')
        .replaceAll('{link}', sampleGuest?.url ?? `${invitation.url ?? 'https://…'}?tamu=…`);

    const insertPlaceholder = (placeholder) => {
        const textarea = messageRef.current;
        const start = textarea?.selectionStart ?? data.broadcast_message.length;
        const end = textarea?.selectionEnd ?? start;
        setData('broadcast_message', data.broadcast_message.slice(0, start) + placeholder + data.broadcast_message.slice(end));
    };

    const submit = (event) => {
        event.preventDefault();
        put(route('invitations.settings.update', invitation.id), { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="flex flex-col gap-space-md">
            <fieldset disabled={readOnly} className="flex flex-col gap-space-md">
                <section className="rounded-3xl bg-surface-container-lowest px-space-md py-1 shadow-card">
                    <div className="divide-y divide-outline-variant/30">
                        <ToggleRow
                            icon="event_available"
                            title="Konfirmasi kehadiran (RSVP)"
                            body="Tamu memilih hadir/tidak dan jumlah orang."
                            checked={data.rsvp_enabled}
                            onChange={(value) => setData('rsvp_enabled', value)}
                            disabled={readOnly}
                        />
                        <ToggleRow
                            icon="forum"
                            title="Ucapan & doa"
                            body="Buku tamu digital yang bisa dibaca semua tamu."
                            checked={data.wishes_enabled}
                            onChange={(value) => setData('wishes_enabled', value)}
                            disabled={readOnly}
                        />
                        <ToggleRow
                            icon="redeem"
                            title="Amplop digital"
                            body="Tampilkan rekening & alamat kado."
                            checked={data.gifts_enabled}
                            onChange={(value) => setData('gifts_enabled', value)}
                            disabled={readOnly}
                        />
                    </div>
                </section>

                <div className="grid grid-cols-[1fr_auto] gap-space-sm">
                    <Field label="Batas RSVP (opsional)" error={errors.rsvp_deadline} hint="Kosongkan bila RSVP dibuka sampai acara.">
                        <input type="datetime-local" value={data.rsvp_deadline} onChange={(e) => setData('rsvp_deadline', e.target.value)} className={`${inputClass} px-3`} />
                    </Field>
                    <Field label="Kuota bawaan" error={errors.default_guest_quota}>
                        <input
                            type="number"
                            min={1}
                            max={invitation.max_guest_quota}
                            value={data.default_guest_quota}
                            onChange={(e) => setData('default_guest_quota', e.target.value)}
                            className={`${inputClass} w-24`}
                        />
                    </Field>
                </div>

                <Field label="Pesan WhatsApp untuk tamu" error={errors.broadcast_message} hint="{nama} dan {link} otomatis diganti untuk setiap tamu.">
                    <textarea
                        ref={messageRef}
                        rows={8}
                        value={data.broadcast_message}
                        onChange={(e) => setData('broadcast_message', e.target.value)}
                        maxLength={1500}
                        className={`${inputClass} resize-none text-body-sm`}
                    />
                    <div className="flex gap-1.5">
                        {['{nama}', '{link}'].map((placeholder) => (
                            <button
                                key={placeholder}
                                type="button"
                                onClick={() => insertPlaceholder(placeholder)}
                                className="rounded-full bg-surface-container px-3 py-1 font-mono text-label-sm text-on-surface-variant hover:bg-surface-container-high"
                            >
                                + {placeholder}
                            </button>
                        ))}
                    </div>
                </Field>

                <div className="rounded-3xl bg-[#e7ffdb] p-space-md">
                    <p className="mb-1 flex items-center gap-1 text-label-sm text-[#1b5e20]">
                        <Icon name="visibility" size={14} /> Pratinjau untuk {sampleGuest?.name ?? 'contoh tamu'}
                    </p>
                    <p className="whitespace-pre-line break-words text-body-sm text-on-surface">{preview}</p>
                </div>
            </fieldset>

            {!readOnly && (
                <SubmitButton processing={processing || !isDirty}>
                    <Icon name="save" size={20} />
                    Simpan Pengaturan
                </SubmitButton>
            )}
        </form>
    );
}
