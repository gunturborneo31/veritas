import Icon from '@/Components/Icon';
import { dateTime } from '@/lib/format';
import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { isInteractive, ManagedHint } from './invitationShared';
import { SceneCard, SceneLabel } from './shared';

/**
 * Ucapan & doa: formulir di atas, ucapan terbaru dari tamu lain di bawahnya.
 */
export default function Wishes({ slot, values, mode, slug, invitation }) {
    const { errors } = usePage().props;
    const wishes = invitation?.wishes;
    const guest = invitation?.guest;
    const [form, setForm] = useState({ name: '', message: '' });
    const [processing, setProcessing] = useState(false);
    const canSubmit = isInteractive(mode, slug);

    if (!wishes) {
        return null;
    }

    const submit = (event) => {
        event.preventDefault();

        if (!canSubmit) {
            return;
        }

        router.post(route('recipient.wish', slug), form, {
            preserveScroll: true,
            preserveState: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
            onSuccess: () => setForm({ name: '', message: '' }),
        });
    };

    return (
        <SceneCard className="text-left">
            <SceneLabel icon="forum">{values.title || slot?.title || 'Ucapan & Doa'}</SceneLabel>
            <ManagedHint mode={mode}>Ucapan yang tidak pantas bisa disembunyikan dari Kelola Undangan.</ManagedHint>

            <form onSubmit={submit} className="flex flex-col gap-2">
                {!guest && (
                    <input
                        value={form.name}
                        onChange={(event) => setForm({ ...form, name: event.target.value })}
                        maxLength={80}
                        placeholder="Nama kamu"
                        className="w-full rounded-xl border-0 bg-surface-container-low px-3 py-2.5 text-body-md focus:ring-2 focus:ring-primary/40"
                    />
                )}
                {errors.name && <p className="text-label-sm text-error">{errors.name}</p>}
                <textarea
                    rows={3}
                    value={form.message}
                    onChange={(event) => setForm({ ...form, message: event.target.value })}
                    maxLength={500}
                    placeholder={guest ? `Tulis ucapan & doa, ${guest.name}…` : 'Tulis ucapan & doa untuk kedua mempelai…'}
                    className="w-full resize-none rounded-xl border-0 bg-surface-container-low px-3 py-2.5 text-body-md focus:ring-2 focus:ring-primary/40"
                />
                {errors.message && <p className="text-label-sm text-error">{errors.message}</p>}
                <button
                    type="submit"
                    disabled={processing || !canSubmit || form.message.trim() === ''}
                    className="flex items-center justify-center gap-2 rounded-full bg-primary py-2.5 text-label-lg text-on-primary shadow-md active:scale-[0.98] disabled:opacity-60"
                >
                    <Icon name="send" size={18} />
                    {canSubmit ? 'Kirim Ucapan' : 'Contoh — tamu mengisi di sini'}
                </button>
            </form>

            {wishes.items.length > 0 && (
                <ul className="no-scrollbar -mx-1 flex max-h-72 flex-col gap-2 overflow-y-auto px-1 pt-1">
                    {wishes.items.map((wish, index) => (
                        <li key={index} className="rounded-2xl bg-surface-container-low px-3 py-2.5">
                            <p className="text-label-md text-on-surface">{wish.name}</p>
                            <p className="whitespace-pre-line break-words text-body-sm text-on-surface">{wish.message}</p>
                            <p className="mt-0.5 text-[11px] text-on-surface-variant">{dateTime(wish.created_at)}</p>
                        </li>
                    ))}
                </ul>
            )}
        </SceneCard>
    );
}
