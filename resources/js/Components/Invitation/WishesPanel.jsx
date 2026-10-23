import Icon from '@/Components/Icon';
import { EmptyState } from '@/Components/Invitation/Field';
import { dateTime } from '@/lib/format';
import { router } from '@inertiajs/react';

/**
 * Ucapan & doa dari tamu. Tampil untuk semua tamu, jadi bisa disembunyikan bila tidak pantas.
 */
export default function WishesPanel({ invitation, readOnly }) {
    const toggle = (wish) => router.patch(route('invitations.wishes.toggle', [invitation.id, wish.id]), {}, { preserveScroll: true });

    if (invitation.wishes.length === 0) {
        return <EmptyState icon="forum" title="Belum ada ucapan" body="Ucapan & doa dari tamu akan muncul di sini begitu undangan disebar." />;
    }

    return (
        <ul className="flex flex-col gap-space-sm">
            {invitation.wishes.map((wish) => (
                <li key={wish.id} className={`flex items-start gap-3 rounded-3xl p-space-md shadow-card ${wish.is_hidden ? 'bg-surface-container-low opacity-70' : 'bg-surface-container-lowest'}`}>
                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-fixed font-display text-label-lg text-primary">
                        {wish.name.charAt(0).toUpperCase()}
                    </div>
                    <div className="min-w-0 flex-1">
                        <p className="text-label-md text-on-surface">
                            {wish.name}
                            {wish.is_hidden && <span className="ml-2 rounded-full bg-surface-container-high px-2 py-0.5 text-label-sm text-on-surface-variant">Disembunyikan</span>}
                        </p>
                        <p className="mt-0.5 whitespace-pre-line break-words text-body-sm text-on-surface">{wish.message}</p>
                        <p className="mt-1 text-label-sm font-normal text-on-surface-variant">{dateTime(wish.created_at)}</p>
                    </div>
                    {!readOnly && (
                        <button
                            type="button"
                            onClick={() => toggle(wish)}
                            aria-label={wish.is_hidden ? 'Tampilkan ucapan' : 'Sembunyikan ucapan'}
                            title={wish.is_hidden ? 'Tampilkan' : 'Sembunyikan'}
                            className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-on-surface-variant hover:bg-surface-container"
                        >
                            <Icon name={wish.is_hidden ? 'visibility' : 'visibility_off'} size={18} />
                        </button>
                    )}
                </li>
            ))}
        </ul>
    );
}
