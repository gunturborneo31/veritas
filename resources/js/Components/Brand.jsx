import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link, usePage } from '@inertiajs/react';

export default function Brand({ showName = true, className = '' }) {
    const { brand } = usePage().props;

    return (
        <Link href={route('home')} className={`flex items-center gap-space-sm ${className}`}>
            <ApplicationLogo className="h-8 w-8 shrink-0" />
            {showName && <span className="font-display text-headline-sm tracking-tight text-on-surface">{brand}</span>}
        </Link>
    );
}
