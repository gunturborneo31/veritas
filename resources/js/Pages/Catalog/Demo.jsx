import Icon from '@/Components/Icon';
import PhoneFrame from '@/Components/Greeting/PhoneFrame';
import Player from '@/Components/Greeting/Player';
import { rupiah } from '@/lib/format';
import { resolveTheme, themeVars } from '@/lib/theme';
import { Head, Link, router } from '@inertiajs/react';

/**
 * Demo template dengan data contoh, selalu berwatermark. `?bagian=N` membuka langsung adegan ke-N.
 */
export default function Demo({ template, greeting }) {
    const theme = resolveTheme(template.theme);
    const initialScene = Math.max(0, Number(new URLSearchParams(window.location.search).get('bagian') ?? 1) - 1);

    return (
        <div
            className="relative flex min-h-screen flex-col items-center gap-space-md overflow-hidden px-margin py-space-lg"
            style={{ ...themeVars(theme), background: `linear-gradient(180deg, ${theme.gradient[0]}, ${theme.soft})` }}
        >
            <Head title={`Demo ${template.name}`} />
            <div className="pointer-events-none absolute -top-24 left-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-primary/15 blur-3xl" />

            <div className="relative flex w-full max-w-[340px] items-center justify-between">
                <Link
                    href={route('catalog.show', template.slug)}
                    className="flex h-10 items-center gap-1 rounded-full bg-white/70 px-3 text-label-md text-on-surface shadow-sm backdrop-blur"
                >
                    <Icon name="arrow_back" size={18} />
                    Kembali
                </Link>
                <span className="max-w-[220px] truncate rounded-full bg-white/70 px-3 py-1.5 text-label-sm text-primary shadow-sm backdrop-blur">
                    {theme.emoji} Demo • {template.design_name ?? template.name}
                </span>
            </div>

            <PhoneFrame className="relative">
                <Player greeting={greeting} mode="demo" watermark initialScene={initialScene} />
            </PhoneFrame>

            <div className="relative flex w-full max-w-[340px] flex-col items-center gap-2">
                <button
                    type="button"
                    onClick={() => router.post(route('studio.store', template.slug))}
                    className="flex h-12 w-full items-center justify-center gap-1.5 rounded-full bg-primary text-label-lg text-on-primary shadow-lg shadow-primary/30 active:scale-95"
                >
                    Pakai Kartu Ini • {rupiah(template.price)}
                    <Icon name="arrow_forward" size={18} />
                </button>
                <p className="text-label-sm text-on-surface-variant">Isi sendiri tanpa daftar • Bayar hanya saat siap kirim</p>
            </div>
        </div>
    );
}
