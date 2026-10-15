import Brand from '@/Components/Brand';
import FlashToast from '@/Components/FlashToast';
import Icon from '@/Components/Icon';
import { Head, Link, usePage } from '@inertiajs/react';

const NAV_ITEMS = [
    { label: 'Jelajah', icon: 'explore', route: 'home' },
    { label: 'Katalog', icon: 'style', route: 'catalog.index' },
    { label: 'Kartu Saya', icon: 'mark_email_read', route: 'dashboard' },
    { label: 'Profil', icon: 'face', route: 'profile.edit' },
];

/**
 * Kerangka halaman publik mobile-first: header kaca + navigasi bawah (desain Beranda/Katalog/Dashboard).
 */
export default function PublicLayout({ title, header = null, hideBottomNav = false, children }) {
    const { auth } = usePage().props;

    return (
        <div className="flex min-h-screen flex-col bg-surface">
            {title && <Head title={title} />}

            <header className="pt-safe fixed top-0 z-50 w-full bg-surface/85 shadow-[0_4px_20px_-2px_rgba(40,21,38,0.05)] backdrop-blur-xl">
                <div className="mx-auto flex h-16 max-w-3xl items-center justify-between px-margin">
                    {header ?? <Brand />}
                    <div className="flex items-center gap-space-sm">
                        {auth.user ? (
                            <>
                                <Link
                                    href={`${route('dashboard')}#aktivitas`}
                                    aria-label="Aktivitas"
                                    className="relative flex h-10 w-10 items-center justify-center rounded-full text-on-surface-variant hover:bg-surface-container"
                                >
                                    <Icon name="notifications" size={22} />
                                    {auth.unread_notifications > 0 && (
                                        <span className="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-bold text-on-primary ring-2 ring-surface">
                                            {auth.unread_notifications}
                                        </span>
                                    )}
                                </Link>
                                {auth.user.is_admin && (
                                    <Link
                                        href={route('admin.home')}
                                        className="flex h-10 items-center rounded-full bg-surface-container px-space-md text-label-md text-on-surface"
                                    >
                                        Admin
                                    </Link>
                                )}
                                <Link
                                    href={route('dashboard')}
                                    aria-label="Kartu saya"
                                    className="flex h-9 w-9 items-center justify-center rounded-full bg-primary text-on-primary shadow-sm"
                                >
                                    <Icon name="person" size={18} />
                                </Link>
                            </>
                        ) : (
                            <Link
                                href={route('login')}
                                className="flex h-10 items-center rounded-full bg-surface-container px-space-md text-label-md text-on-surface transition-transform active:scale-95"
                            >
                                Masuk
                            </Link>
                        )}
                    </div>
                </div>
            </header>

            <FlashToast />

            <main className={`mx-auto w-full max-w-3xl flex-1 pt-16 ${hideBottomNav ? '' : 'pb-24'}`}>{children}</main>

            {!hideBottomNav && (
                <nav className="pb-safe fixed bottom-0 z-50 w-full bg-surface/90 shadow-bar backdrop-blur-xl">
                    <div className="mx-auto flex h-16 max-w-3xl items-center justify-around px-margin">
                        {NAV_ITEMS.map((item) => {
                            const isActive = route().current(item.route);

                            return (
                                <Link
                                    key={item.route}
                                    href={route(item.route)}
                                    aria-current={isActive ? 'page' : undefined}
                                    className={`flex min-h-[44px] min-w-[56px] flex-col items-center justify-center gap-space-xs transition-colors ${
                                        isActive ? 'text-label-md text-primary' : 'text-label-sm text-on-surface-variant'
                                    }`}
                                >
                                    <Icon name={item.icon} size={22} filled={isActive} />
                                    <span>{item.label}</span>
                                </Link>
                            );
                        })}
                    </div>
                </nav>
            )}
        </div>
    );
}
