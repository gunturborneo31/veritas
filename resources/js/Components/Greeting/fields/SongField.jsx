import Icon from '@/Components/Icon';
import { requestJson } from '@/lib/http';
import { usePage } from '@inertiajs/react';
import { useState } from 'react';

const inputClass =
    'w-full rounded-2xl border-0 bg-surface-container-low px-4 py-3 text-body-md text-on-surface placeholder:text-on-surface-variant/60 focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary/40';

const PROVIDERS = {
    spotify: { label: 'Spotify', color: '#1DB954' },
    youtube: { label: 'YouTube', color: '#FF0000' },
};

function SongRow({ song, action }) {
    const provider = PROVIDERS[song.provider];

    return (
        <div className="flex items-center gap-3">
            {song.image ? (
                <img src={song.image} alt="" loading="lazy" className="h-12 w-12 shrink-0 rounded-lg object-cover" />
            ) : (
                <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-surface-container text-primary">
                    <Icon name="music_note" size={22} />
                </span>
            )}
            <span className="min-w-0 flex-1">
                <span className="block truncate text-label-lg text-on-surface">{song.title}</span>
                <span className="flex items-center gap-1.5 truncate text-label-sm font-normal text-on-surface-variant">
                    <span className="h-2 w-2 shrink-0 rounded-full" style={{ backgroundColor: provider?.color }} />
                    {provider?.label}
                    {song.artist && ` • ${song.artist}`}
                </span>
            </span>
            {action}
        </div>
    );
}

/**
 * Field `song`: satu lagu Spotify/YouTube. Kotak input menerima kata kunci (dicari di Spotify bila aktif)
 * atau link lagu Spotify / video YouTube.
 */
export default function SongField({ value, onChange }) {
    const { song_search_enabled: isSearchEnabled } = usePage().props;
    const [query, setQuery] = useState('');
    const [results, setResults] = useState(null);
    const [error, setError] = useState(null);
    const [isLoading, setIsLoading] = useState(false);

    const submit = async () => {
        const text = query.trim();
        setError(null);
        setResults(null);

        if (!text) {
            return;
        }

        const isLink = /^https?:\/\//i.test(text) || /^(open\.spotify\.com|youtu\.?be)/i.test(text);

        if (!isLink && !isSearchEnabled) {
            setError('Tempel link lagu dari Spotify atau YouTube (tombol "Bagikan" → "Salin link").');

            return;
        }

        setIsLoading(true);

        try {
            if (isLink) {
                const { song } = await requestJson(route('songs.link', { url: text.startsWith('http') ? text : `https://${text}` }));
                onChange(song);
                setQuery('');
            } else {
                const data = await requestJson(route('songs.search', { q: text }));
                setResults(data.results);
            }
        } catch (exception) {
            setError(exception.message);
        } finally {
            setIsLoading(false);
        }
    };

    if (value?.id) {
        return (
            <div className="rounded-2xl bg-surface-container-low p-3">
                <SongRow
                    song={value}
                    action={
                        <button
                            type="button"
                            onClick={() => onChange(null)}
                            className="shrink-0 rounded-full bg-surface-container-lowest px-3 py-2 text-label-md text-primary shadow-sm"
                        >
                            Ganti
                        </button>
                    }
                />
            </div>
        );
    }

    return (
        <div className="flex flex-col gap-2">
            <div className="flex gap-2">
                <input
                    type="search"
                    value={query}
                    placeholder={isSearchEnabled ? 'Judul lagu / penyanyi, atau tempel link' : 'Tempel link Spotify atau YouTube'}
                    onChange={(event) => setQuery(event.target.value)}
                    onKeyDown={(event) => {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            submit();
                        }
                    }}
                    className={`${inputClass} min-w-0 flex-1`}
                />
                <button
                    type="button"
                    onClick={submit}
                    disabled={isLoading}
                    aria-label={isSearchEnabled ? 'Cari lagu' : 'Pakai link'}
                    className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-primary text-on-primary disabled:opacity-60"
                >
                    <Icon name={isLoading ? 'progress_activity' : isSearchEnabled ? 'search' : 'link'} size={20} className={isLoading ? 'animate-spin' : ''} />
                </button>
            </div>
            <p className="text-label-sm font-normal text-on-surface-variant">
                {isSearchEnabled
                    ? 'Lagu dicari di Spotify. Untuk YouTube, tempel link videonya.'
                    : 'Buka lagunya di Spotify/YouTube → Bagikan → Salin link, lalu tempel di sini.'}
            </p>
            {error && <span className="text-label-sm text-error">{error}</span>}
            {results !== null && (
                <div className="flex flex-col overflow-hidden rounded-2xl bg-surface-container-lowest shadow-sm">
                    {results.length === 0 && <p className="px-4 py-3 text-body-sm text-on-surface-variant">Lagu tidak ketemu. Coba kata kunci lain atau tempel link-nya.</p>}
                    {results.map((song) => (
                        <button
                            key={song.id}
                            type="button"
                            onClick={() => {
                                onChange(song);
                                setResults(null);
                                setQuery('');
                            }}
                            className="border-t border-surface-container px-3 py-2.5 text-left first:border-t-0 hover:bg-surface-container-low"
                        >
                            <SongRow song={song} action={<Icon name="add_circle" size={22} className="shrink-0 text-primary" />} />
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}
