import Icon from '@/Components/Icon';
import { Link } from '@inertiajs/react';

/**
 * Tautan ke isian yang kurang di Studio. Detail acara undangan diisi di halaman Kelola Undangan.
 */
export function missingHref(greetingId, item) {
    return item.slot === 'acara' ? `${route('invitations.show', greetingId)}#acara` : `${route('studio.edit', greetingId)}#isian-${item.slot}`;
}

/**
 * Daftar periksa isian wajib yang belum diisi. Di Studio setiap baris memanggil `onJump`
 * (gulir ke kolomnya); di pratinjau & checkout setiap baris menjadi tautan kembali ke Studio.
 */
export default function MissingChecklist({ missing, greetingId, onJump = null, compact = false }) {
    if (missing.length === 0) {
        return compact ? null : (
            <div className="flex items-center gap-2 rounded-2xl bg-success/10 px-4 py-3 text-success">
                <Icon name="task_alt" size={20} />
                <span className="text-label-md">Semua isian wajib sudah lengkap</span>
            </div>
        );
    }

    const rowClass = 'flex w-full items-center gap-3 rounded-2xl bg-surface-container-lowest px-3 py-2.5 text-left shadow-sm transition-colors hover:bg-error-container/30 active:scale-[0.99]';

    const row = (item) => (
        <>
            <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 border-error/60 text-error">
                <Icon name="priority_high" size={14} />
            </span>
            <span className="min-w-0 flex-1">
                <span className="block truncate text-label-sm font-normal text-on-surface-variant">{item.section}</span>
                <span className="block text-label-md text-on-surface">
                    {item.label}
                    {item.hint && <span className="font-normal text-on-surface-variant"> — {item.hint}</span>}
                </span>
            </span>
            <span className="shrink-0 text-label-sm text-primary">Isi</span>
            <Icon name="chevron_right" size={18} className="shrink-0 text-primary" />
        </>
    );

    return (
        <section role="alert" className="flex flex-col gap-2 rounded-3xl border border-error/25 bg-error-container/40 p-space-md">
            <div className="flex items-center gap-2 text-on-error-container">
                <Icon name="checklist" size={20} />
                <h3 className="text-label-lg">Masih ada {missing.length} isian wajib yang belum diisi</h3>
            </div>
            <p className="text-body-sm text-on-error-container/80">Ketuk salah satu untuk langsung ke kolomnya. Kolom yang belum diisi diberi tanda merah.</p>
            <ul className="flex flex-col gap-1.5">
                {missing.map((item) => (
                    <li key={`${item.slot}.${item.field}`}>
                        {onJump ? (
                            <button type="button" onClick={() => onJump(item)} className={rowClass}>
                                {row(item)}
                            </button>
                        ) : (
                            <Link href={missingHref(greetingId, item)} className={rowClass}>
                                {row(item)}
                            </Link>
                        )}
                    </li>
                ))}
            </ul>
        </section>
    );
}
