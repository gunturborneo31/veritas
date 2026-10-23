import Icon from '@/Components/Icon';
import { SceneLabel } from './shared';

/**
 * URL pemutar resmi. YouTube memakai domain nocookie agar tidak memasang cookie pelacak sebelum diputar.
 */
export function embedUrl(song) {
    if (song.provider === 'spotify') {
        return `https://open.spotify.com/embed/track/${song.id}?utm_source=kartukita&theme=0`;
    }

    return `https://www.youtube-nocookie.com/embed/${song.id}?rel=0&playsinline=1&modestbranding=1`;
}

/**
 * Lagu Kita: pemutar resmi Spotify/YouTube + catatan pengirim. Penerima menekan putar sendiri;
 * musik latar kartu dijeda otomatis oleh Player selama adegan ini tampil.
 */
export default function SongEmbed({ slot, values, mode }) {
    const song = values.song;
    const title = values.title || slot?.title || 'Lagu Kita';

    if (!song?.id) {
        return (
            <div className="flex w-full flex-col items-center gap-3 text-center">
                <SceneLabel icon="queue_music">{title}</SceneLabel>
                <div className="flex h-40 w-full flex-col items-center justify-center gap-2 rounded-3xl bg-surface-container-lowest/80 p-6 text-on-surface-variant shadow-sm">
                    <Icon name="library_music" size={36} className="text-primary" />
                    <p className="text-body-sm">{mode === 'studio' ? 'Pilih lagu di formulir untuk melihat pemutarnya.' : 'Belum ada lagu.'}</p>
                </div>
            </div>
        );
    }

    const isYoutube = song.provider === 'youtube';

    return (
        <div className="flex w-full flex-col gap-3">
            <SceneLabel icon="queue_music">{title}</SceneLabel>

            <div className={`w-full overflow-hidden rounded-2xl bg-on-surface/90 shadow-[0_14px_30px_-18px_rgba(40,21,38,0.6)] ${isYoutube ? 'aspect-video' : 'h-[152px]'}`}>
                <iframe
                    title={`${song.title}${song.artist ? ` — ${song.artist}` : ''}`}
                    src={embedUrl(song)}
                    className="h-full w-full border-0"
                    loading="lazy"
                    allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture"
                    allowFullScreen
                />
            </div>

            {values.note && (
                <div className="flex animate-layer-in gap-2.5 rounded-2xl bg-surface-container-lowest/90 p-4 shadow-sm">
                    <Icon name="format_quote" size={22} className="shrink-0 text-primary" />
                    <p className="font-display text-body-md italic leading-relaxed text-on-surface">{values.note}</p>
                </div>
            )}

            <p className="flex items-center justify-center gap-1 text-center text-label-sm font-normal text-on-surface-variant">
                <Icon name="touch_app" size={14} />
                Ketuk putar di atas. {song.provider === 'spotify' ? 'Lagu penuh butuh login Spotify; tanpa login diputar cuplikannya.' : 'Diputar dari YouTube.'}
            </p>
        </div>
    );
}
