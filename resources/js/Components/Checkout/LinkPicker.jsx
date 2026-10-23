import Icon from '@/Components/Icon';
import { requestJson } from '@/lib/http';
import { useEffect, useState } from 'react';

const toSlug = (value) =>
    value
        .toLowerCase()
        .normalize('NFKD')
        .replace(/[̀-ͯ]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+/, '')
        .slice(0, 40);

/**
 * Nama link kartu (`/u/<nama>`). Ketersediaan dicek ke server saat mengetik (debounce),
 * dan diperiksa sekali lagi saat pesanan dibuat. Kosong = link acak.
 */
export default function LinkPicker({ greetingId, link, value, onChange, error }) {
    const [status, setStatus] = useState(null);

    useEffect(() => {
        const slug = value.replace(/-+$/, '');

        if (slug === '') {
            setStatus(null);

            return undefined;
        }

        setStatus({ state: 'checking' });
        const timer = setTimeout(() => {
            requestJson(`${route('checkout.slug.check', greetingId)}?slug=${encodeURIComponent(slug)}`)
                .then((result) => setStatus({ state: result.available ? 'available' : 'taken', ...result }))
                .catch(() => setStatus(null));
        }, 400);

        return () => clearTimeout(timer);
    }, [greetingId, value]);

    const message = error ?? status?.message;
    const tone = error || status?.state === 'taken' ? 'text-error' : status?.state === 'available' ? 'text-success' : 'text-on-surface-variant';

    return (
        <section className="flex flex-col gap-space-sm rounded-3xl bg-surface-container-lowest p-space-md shadow-card">
            <div className="flex items-center gap-2">
                <Icon name="link" size={20} className="text-primary" />
                <h2 className="text-headline-sm text-on-surface">Nama Link</h2>
                <span className="rounded-full bg-surface-container px-2 py-0.5 text-label-sm text-on-surface-variant">Opsional</span>
            </div>
            <p className="text-body-sm text-on-surface-variant">Buat link yang mudah diingat. Setelah terbit, link tidak bisa diubah lagi.</p>

            <label className="flex items-center overflow-hidden rounded-2xl bg-surface-container-low focus-within:bg-surface-container-lowest focus-within:ring-2 focus-within:ring-primary/40">
                <span className="shrink-0 select-none pl-4 text-body-sm text-on-surface-variant">{link.prefix.replace(/^https?:\/\//, '')}</span>
                <input
                    value={value}
                    onChange={(event) => onChange(toSlug(event.target.value))}
                    placeholder={link.suggestion || 'nama-link'}
                    autoCapitalize="none"
                    autoComplete="off"
                    spellCheck={false}
                    className="min-w-0 flex-1 border-0 bg-transparent py-3 pl-0.5 pr-3 text-body-md font-semibold text-on-surface focus:ring-0"
                />
                {status?.state === 'checking' && <Icon name="progress_activity" size={18} className="mr-3 animate-spin text-on-surface-variant" />}
                {status?.state === 'available' && !error && <Icon name="check_circle" size={20} filled className="mr-3 text-success" />}
                {(status?.state === 'taken' || error) && <Icon name="cancel" size={20} filled className="mr-3 text-error" />}
            </label>

            {message && status?.state !== 'checking' && <p className={`text-label-sm ${tone}`}>{message}</p>}
            {!value && <p className="text-label-sm font-normal text-on-surface-variant">Kosongkan bila ingin link acak.</p>}

            {(status?.suggestions?.length > 0 || (!value && link.suggestion)) && (
                <div className="flex flex-wrap gap-1.5">
                    <span className="text-label-sm font-normal text-on-surface-variant">Coba:</span>
                    {(status?.suggestions?.length > 0 ? status.suggestions : [link.suggestion]).map((suggestion) => (
                        <button
                            key={suggestion}
                            type="button"
                            onClick={() => onChange(suggestion)}
                            className="rounded-full bg-primary-fixed px-3 py-1 text-label-sm text-on-primary-fixed active:scale-95"
                        >
                            {suggestion}
                        </button>
                    ))}
                </div>
            )}
        </section>
    );
}
