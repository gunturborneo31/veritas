import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link, usePage } from '@inertiajs/react';

export default function Brand({ showName = true, showTagline = true, className = '' }) {
    const { brand, tagline } = usePage().props;

    return (
        <Link href={route('home')} className={`flex items-center gap-space-sm ${className}`}>
            <ApplicationLogo className="h-8 w-8 shrink-0" />
            {showName && (
                <span className="flex min-w-0 flex-col leading-none">
                    <span className="font-display text-headline-sm tracking-tight text-on-surface">{brand}</span>
                    {showTagline && tagline && <span className="mt-0.5 truncate text-[11px] font-medium text-on-surface-variant">{tagline}</span>}
                </span>
            )}
        </Link>
    );
}
