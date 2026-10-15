import Icon from '@/Components/Icon';
import Toggle from '@/Components/Toggle';
import { router } from '@inertiajs/react';
import { useState } from 'react';

/**
 * Mode patungan di Studio: buka link untuk teman, pantau dan saring tulisan yang masuk.
 */
export default function CollaborationPanel({ greetingId, recipientName, collaboration }) {
    const [copied, setCopied] = useState(false);
    const isOpen = Boolean(collaboration.url);
    const visibleCount = collaboration.items.filter((item) => !item.is_hidden).length;
    const shareText = `Hai! Aku lagi siapin kejutan buat ${recipientName || 'seseorang'} 🤫 Titip satu pesan di sini ya, nanti muncul di kartunya: ${collaboration.url}`;

    const toggleOpen = (open) =>
        open
            ? router.post(route('studio.contributions.enable', greetingId), {}, { preserveScroll: true, preserveState: true, only: ['collaboration', 'flash'] })
            : confirm('Tutup link patungan? Tulisan yang sudah masuk tetap tersimpan.') &&
              router.delete(route('studio.contributions.disable', greetingId), { preserveScroll: true, preserveState: true, only: ['collaboration', 'flash'] });

    const copy = () => {
        navigator.clipboard?.writeText(collaboration.url);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <section className="space-y-space-md rounded-3xl bg-gradient-to-br from-primary-fixed/60 to-surface-container-lowest p-space-lg shadow-[0_4px_24px_rgba(46,26,44,0.05)]">
            <div className="flex items-start justify-between gap-3">
                <div className="flex items-center gap-2.5">
                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-on-primary">
                        <Icon name="groups" size={18} />
                    </div>
                    <div>
                        <h3 className="text-headline-sm font-bold text-on-surface">Ajak teman ikut menulis</h3>
                        <p className="text-label-sm text-on-surface-variant">Mode patungan • tanpa akun • gratis</p>
                    </div>
                </div>
                <Toggle checked={isOpen} onChange={toggleOpen} label="Mode patungan" />
            </div>

            <p className="text-body-sm text-on-surface-variant">
                Bagikan link ke teman/keluarga. Pesan mereka muncul sebagai “dinding ucapan” sebelum penutup. Link otomatis tertutup saat kartu dibayar.
            </p>

            {isOpen && (
                <div className="flex flex-col gap-2">
                    <div className="flex items-center gap-2 rounded-2xl bg-surface-container-lowest p-2">
                        <span className="min-w-0 flex-1 truncate pl-2 text-label-md text-on-surface">{collaboration.url}</span>
                        <button type="button" onClick={copy} className="flex shrink-0 items-center gap-1 rounded-full bg-primary px-3 py-1.5 text-label-sm text-on-primary">
                            <Icon name={copied ? 'done' : 'content_copy'} size={14} />
                            {copied ? 'Tersalin' : 'Salin'}
                        </button>
                    </div>
                    <a
                        href={`https://api.whatsapp.com/send?text=${encodeURIComponent(shareText)}`}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex h-11 items-center justify-center gap-2 rounded-full bg-whatsapp text-label-md text-white shadow-sm"
                    >
                        <Icon name="chat" size={18} />
                        Bagikan ke grup WhatsApp
                    </a>
                </div>
            )}

            {collaboration.items.length > 0 && (
                <div className="flex flex-col gap-2">
                    <p className="text-label-md text-on-surface">
                        {visibleCount} pesan tampil{collaboration.items.length > visibleCount && ` • ${collaboration.items.length - visibleCount} disembunyikan`}
                    </p>
                    {collaboration.items.map((item) => (
                        <div key={item.id} className={`flex items-start gap-3 rounded-2xl bg-surface-container-lowest p-3 ${item.is_hidden ? 'opacity-50' : ''}`}>
                            {item.photo?.url ? (
                                <img src={item.photo.url} alt="" className="h-12 w-12 shrink-0 rounded-xl object-cover" />
                            ) : (
                                <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary-fixed text-headline-sm text-primary">
                                    {item.name.charAt(0).toUpperCase()}
                                </span>
                            )}
                            <div className="min-w-0 flex-1">
                                <p className="text-label-md text-on-surface">{item.name}</p>
                                <p className="line-clamp-3 text-body-sm text-on-surface-variant">{item.message}</p>
                            </div>
                            <button
                                type="button"
                                onClick={() => router.patch(route('studio.contributions.toggle', [greetingId, item.id]), {}, { preserveScroll: true, preserveState: true, only: ['collaboration', 'flash'] })}
                                aria-label={item.is_hidden ? 'Tampilkan' : 'Sembunyikan'}
                                className="text-on-surface-variant hover:text-primary"
                            >
                                <Icon name={item.is_hidden ? 'visibility_off' : 'visibility'} size={20} />
                            </button>
                        </div>
                    ))}
                </div>
            )}
        </section>
    );
}
