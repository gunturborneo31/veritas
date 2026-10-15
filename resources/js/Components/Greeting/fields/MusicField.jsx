import Icon from '@/Components/Icon';
import { duration } from '@/lib/format';
import { useRef, useState } from 'react';

/**
 * Pilih lagu dari pustaka internal berlisensi (tidak ada unggah lagu bebas).
 */
export default function MusicField({ value, onChange, musicTracks }) {
    const audioRef = useRef(null);
    const [playingId, setPlayingId] = useState(null);
    const selected = musicTracks.find((track) => track.id === value);

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

    return (
        <div className="flex flex-col gap-2">
            <audio ref={audioRef} onEnded={() => setPlayingId(null)} />
            {selected && (
                <div className="flex items-center justify-between rounded-2xl bg-surface-container-low p-3.5">
                    <div className="flex min-w-0 items-center gap-3">
                        <button
                            type="button"
                            onClick={() => preview(selected)}
                            className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary text-on-primary shadow-sm"
                        >
                            <Icon name={playingId === selected.id ? 'pause' : 'play_arrow'} size={20} />
                        </button>
                        <div className="min-w-0">
                            <p className="truncate text-label-lg text-on-surface">{selected.title}</p>
                            <p className="text-label-sm text-on-surface-variant">
                                {duration(selected.duration)} • <span className="text-primary">{selected.mood}</span>
                            </p>
                        </div>
                    </div>
                    <span className="rounded-full bg-surface-container px-2.5 py-0.5 text-label-sm text-on-surface-variant">Dipilih</span>
                </div>
            )}
            <span className="text-label-sm text-on-surface-variant">Pustaka lagu:</span>
            <div className="grid grid-cols-2 gap-2">
                {musicTracks.map((track) => (
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
                                {duration(track.duration)} • {track.mood}
                            </span>
                        </span>
                    </button>
                ))}
            </div>
        </div>
    );
}
