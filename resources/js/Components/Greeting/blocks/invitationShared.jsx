import Icon from '@/Components/Icon';

/**
 * Waktu acara selalu ditampilkan dalam zona waktu lokasi acara (WIB/WITA/WIT),
 * bukan zona waktu HP tamu, agar tamu dari kota lain tidak salah jam.
 */
export function eventDate(iso, timezone) {
    return new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', timeZone: timezone }).format(new Date(iso));
}

export function eventClock(iso, timezone) {
    return new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit', timeZone: timezone }).format(new Date(iso));
}

/**
 * Catatan kecil di Studio/pratinjau bahwa isi bagian ini diatur di halaman Kelola Undangan.
 */
export function ManagedHint({ mode, children }) {
    if (mode !== 'studio') {
        return null;
    }

    return (
        <p className="flex items-start gap-1.5 rounded-xl bg-tertiary-fixed/60 px-3 py-2 text-left text-label-sm text-on-tertiary-fixed">
            <Icon name="info" size={14} className="mt-0.5 shrink-0" />
            <span>{children}</span>
        </p>
    );
}

/**
 * Formulir di blok undangan hanya aktif untuk tamu sungguhan; mode lain menampilkan contoh.
 */
export const isInteractive = (mode, slug) => mode === 'recipient' && Boolean(slug);
