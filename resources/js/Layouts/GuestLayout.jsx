import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link, usePage } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    const { brand } = usePage().props;

    return (
        <div className="relative flex min-h-screen flex-col items-center overflow-hidden bg-surface px-margin pt-10 sm:justify-center sm:pt-0">
            <div className="pointer-events-none absolute -left-16 -top-16 h-72 w-72 rounded-full bg-primary-fixed-dim/30 blur-3xl" />
            <div className="pointer-events-none absolute -right-20 bottom-10 h-72 w-72 rounded-full bg-tertiary-fixed-dim/25 blur-3xl" />

            <Link href="/" className="relative flex flex-col items-center gap-2">
                <ApplicationLogo className="h-16 w-16" />
                <span className="font-display text-headline-md text-on-surface">{brand}</span>
            </Link>

            <p className="relative mt-2 max-w-xs text-center text-body-sm text-on-surface-variant">
                Masuk untuk menyimpan ucapanmu secara permanen dan melanjutkan pembayaran. Draf yang sudah kamu buat akan ikut tersimpan.
            </p>

            <div className="relative mt-6 w-full overflow-hidden rounded-3xl bg-surface-container-lowest px-6 py-6 shadow-card sm:max-w-md">{children}</div>
        </div>
    );
}
