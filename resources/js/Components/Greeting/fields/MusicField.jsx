import Icon from '@/Components/Icon';
import { duration } from '@/lib/format';
import { useMemo, useRef, useState } from 'react';

/** Urutan tab kategori; kategori lain (bila ada) ditaruh di belakang. */
const CATEGORY_ORDER = ['Romantis', 'Ceria', 'Santai & Jazz', 'Teduh & Piano', 'Klasik & Pernikahan', 'Khidmat & Religi', 'Haru & Rindu', 'Semangat & Bangga'];
const OTHER = 'Lainnya';

/**
 * Pilih lagu dari pustaka internal berlisensi (tidak ada unggah lagu bebas), dikelompokkan per kategori.
 */
export default function MusicField({ value, onChange, musicTracks }) {
    const audioRef = useRef(null);
    const [playingId, setPlayingId] = useState(null);
    const selected = musicTracks.find((track) => track.id === value);
    const [activeCategory, setActiveCategory] = useState(selected?.category ?? null);

    const categories = useMemo(() => {
        const present = [...new Set(musicTracks.map((track) => track.category || OTHER))];

        return present.sort((a, b) => {
            const rank = (name) => (CATEGORY_ORDER.includes(name) ? CATEGORY_ORDER.indexOf(name) : CATEGORY_ORDER.length);

            return rank(a) - rank(b) || a.localeCompare(b);
        });
    }, [musicTracks]);

    const visibleTracks = activeCategory ? musicTracks.filter((track) => (track.category || OTHER) === activeCategory) : musicTracks;

    const preview = (track) => {
        if (playingId === track.id) {
            audioRef.current?.pause();
            setPlayingId(null);

            return;
        }

        audioRef.current.src = track.url;
        audioRef.current.play().catch(() => {});
        setPlayingId(track.id);
    };

    const tabClass = (isActive) =>
        `shrink-0 rounded-full px-3 py-1.5 text-label-sm transition-colors ${isActive ? 'bg-primary text-on-primary shadow-sm' : 'bg-surface-container-low text-on-surface-variant hover:bg-surface-container'}`;

    return (
        <div className="flex flex-col gap-2">
            <audio ref={audioRef} onEnded={() => setPlayingId(null)} />
            {selected && (
                <div className="flex flex-col gap-1.5 rounded-2xl bg-surface-container-low p-3.5">
                    <div className="flex items-center justify-between gap-2">
                        <div className="flex min-w-0 items-center gap-3">
                            <button
                                type="button"
                                onClick={() => preview(selected)}
                                aria-label={playingId === selected.id ? 'Jeda' : 'Putar'}
                                className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary text-on-primary shadow-sm"
                            >
                                <Icon name={playingId === selected.id ? 'pause' : 'play_arrow'} size={20} />
                            </button>
                            <div className="min-w-0">
                                <p className="truncate text-label-lg text-on-surface">{selected.title}</p>
                                <p className="truncate text-label-sm text-on-surface-variant">
                                    {duration(selected.duration)} • <span className="text-primary">{selected.category || selected.mood}</span>
                                </p>
                            </div>
                        </div>
                        <span className="shrink-0 rounded-full bg-surface-container px-2.5 py-0.5 text-label-sm text-on-surface-variant">Dipilih</span>
                    </div>
                    {selected.credit && <p className="text-[11px] leading-snug text-on-surface-variant/80">{selected.credit}</p>}
                </div>
            )}

            <span className="text-label-sm text-on-surface-variant">Pustaka musik instrumental ({musicTracks.length} lagu):</span>
            {categories.length > 1 && (
                <div className="no-scrollbar -mx-1 flex gap-1.5 overflow-x-auto px-1 pb-1">
                    <button type="button" onClick={() => setActiveCategory(null)} className={tabClass(activeCategory === null)}>
                        Semua
                    </button>
                    {categories.map((category) => (
                        <button key={category} type="button" onClick={() => setActiveCategory(category)} className={tabClass(activeCategory === category)}>
                            {category}
                        </button>
                    ))}
                </div>
            )}

            <div className="grid max-h-[360px] grid-cols-2 gap-2 overflow-y-auto pr-0.5">
                {visibleTracks.map((track) => (
                    <button
                        key={track.id}
                        type="button"
                        onClick={() => onChange(track.id)}
                        className={`flex items-center gap-2 rounded-xl p-2.5 text-left shadow-sm transition-colors ${
                            track.id === value ? 'bg-primary-fixed ring-2 ring-primary' : 'bg-surface-container-lowest hover:bg-surface-container-low'
                        }`}
                    >
                        <span
                            role="button"
                            tabIndex={-1}
                            aria-label={playingId === track.id ? 'Jeda cuplikan' : 'Putar cuplikan'}
                            onClick={(event) => {
                                event.stopPropagation();
                                preview(track);
                            }}
                            className="text-primary"
                        >
                            <Icon name={playingId === track.id ? 'pause_circle' : 'play_circle'} size={22} />
                        </span>
                        <span className="min-w-0">
                            <span className="block truncate text-label-sm text-on-surface">{track.title}</span>
                            <span className="block truncate text-label-sm font-normal text-on-surface-variant">
                                {duration(track.duration)} • {activeCategory ? track.mood || track.category : track.category || track.mood}
                            </span>
                        </span>
                    </button>
                ))}
            </div>
        </div>
    );
}
