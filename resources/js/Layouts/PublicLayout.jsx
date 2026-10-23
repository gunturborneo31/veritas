import Brand from '@/Components/Brand';
import FlashToast from '@/Components/FlashToast';
import Icon from '@/Components/Icon';
import { Head, Link, usePage } from '@inertiajs/react';

/**
 * Empat bagian utama aplikasi. `sections` menentukan tab mana yang menyala, termasuk saat berada di sub-halaman
 * (mis. detail template tetap menyalakan "Katalog").
 */
const NAV_ITEMS = [
    { label: 'Jelajah', icon: 'explore', route: 'home', sections: ['home'] },
    { label: 'Katalog', icon: 'style', route: 'catalog.index', sections: ['catalog.*'] },
    { label: 'Kartu Saya', icon: 'mark_email_read', route: 'dashboard', sections: ['dashboard', 'orders.*', 'result.*', 'invitations.*'] },
    { label: 'Profil', icon: 'face', route: 'profile.edit', sections: ['profile.*'] },
];

const isSectionActive = (item) => item.sections.some((pattern) => route().current(pattern));

/**
 * Kerangka halaman publik. Navigasi selalu sama di semua halaman jelajah:
 * - HP: tab bawah; mulai `md`: menu di header.
 * - Sub-halaman memberi `back` ({ href, label }) untuk tautan kembali yang jelas ke induknya.
 * - `actionBar` adalah bilah tombol aksi yang menempel di atas tab bawah (mis. "Buat sekarang").
 * - `wide` melebarkan konten untuk halaman etalase.
 */
export default function PublicLayout({ title, back = null, actionBar = null, wide = false, children }) {
    const { auth, resume_draft } = usePage().props;
    const containerWidth = wide ? 'max-w-3xl lg:max-w-5xl' : 'max-w-3xl';

    return (
        <div className="flex min-h-screen flex-col bg-surface">
            {title && <Head title={title} />}

            <header className="pt-safe fixed top-0 z-50 w-full bg-surface/85 shadow-[0_4px_20px_-2px_rgba(40,21,38,0.05)] backdrop-blur-xl">
                <div className={`mx-auto flex h-16 items-center justify-between gap-space-md px-margin ${containerWidth}`}>
                    <Brand />
                    <nav aria-label="Menu utama" className="hidden items-center gap-1 md:flex">
                        {NAV_ITEMS.map((item) => {
                            const isActive = isSectionActive(item);

                            return (
                                <Link
                                    key={item.route}
                                    href={route(item.route)}
                                    aria-current={isActive ? 'page' : undefined}
                                    className={`flex h-10 items-center rounded-full px-space-md text-label-md transition-colors ${
                                        isActive ? 'bg-primary-fixed text-on-primary-fixed' : 'text-on-surface-variant hover:bg-surface-container'
                                    }`}
                                >
                                    {item.label}
                                </Link>
                            );
                        })}
                    </nav>
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
                                        <span className="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[11px] font-bold text-on-primary ring-2 ring-surface">
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
                                    href={route('profile.edit')}
                                    aria-label="Profil"
                                    className="hidden h-9 w-9 items-center justify-center rounded-full bg-primary font-display text-label-lg text-on-primary shadow-sm md:flex"
                                >
                                    {auth.user.name.charAt(0).toUpperCase()}
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

            <main className={`mx-auto w-full flex-1 pt-16 ${containerWidth} ${actionBar ? 'pb-48 md:pb-32' : 'pb-24 md:pb-space-xl'}`}>
                {resume_draft && (
                    <div className="px-margin pt-space-md">
                        <Link
                            href={resume_draft.url}
                            className="flex items-center gap-3 rounded-2xl bg-primary-fixed px-4 py-3 text-on-primary-fixed shadow-sm active:scale-[0.99]"
                        >
                            <Icon name="edit_note" size={22} className="shrink-0 text-primary" />
                            <span className="min-w-0 flex-1">
                                <span className="block text-label-md">Lanjutkan kartumu</span>
                                <span className="block truncate text-body-sm opacity-80">
                                    {resume_draft.template_name}
                                    {resume_draft.recipient_name ? ` untuk ${resume_draft.recipient_name}` : ' • draf tersimpan di perangkat ini'}
                                </span>
                            </span>
                            <Icon name="arrow_forward" size={20} className="shrink-0 text-primary" />
                        </Link>
                    </div>
                )}
                {back && (
                    <div className="px-margin pt-space-md">
                        <Link
                            href={back.href}
                            className="-ml-1 inline-flex min-h-10 items-center gap-1 rounded-full py-1 pl-1 pr-3 text-label-md text-on-surface-variant transition-colors hover:bg-surface-container hover:text-on-surface"
                        >
                            <Icon name="arrow_back" size={20} />
                            {back.label}
                        </Link>
                    </div>
                )}
                {children}
            </main>

            {actionBar && (
                <div className="fixed inset-x-0 bottom-[calc(env(safe-area-inset-bottom)_+_4rem)] z-40 border-t border-outline-variant/30 bg-surface/95 px-margin py-3 backdrop-blur-xl md:bottom-0 md:pb-[calc(env(safe-area-inset-bottom)_+_0.75rem)] md:shadow-bar">
                    <div className="mx-auto max-w-lg">{actionBar}</div>
                </div>
            )}

            <nav aria-label="Menu utama" className="pb-safe fixed bottom-0 z-50 w-full bg-surface/95 shadow-bar backdrop-blur-xl md:hidden">
                <div className="mx-auto flex h-16 max-w-3xl items-center justify-around px-margin">
                    {NAV_ITEMS.map((item) => {
                        const isActive = isSectionActive(item);

                        return (
                            <Link
                                key={item.route}
                                href={route(item.route)}
                                aria-current={isActive ? 'page' : undefined}
                                className={`relative flex min-h-[44px] min-w-[64px] flex-col items-center justify-center gap-0.5 transition-colors ${
                                    isActive ? 'text-label-md text-primary' : 'text-label-sm text-on-surface-variant'
                                }`}
                            >
                                <span className={`flex h-7 w-12 items-center justify-center rounded-full transition-colors ${isActive ? 'bg-primary-fixed' : ''}`}>
                                    <Icon name={item.icon} size={22} filled={isActive} />
                                </span>
                                <span>{item.label}</span>
                            </Link>
                        );
                    })}
                </div>
            </nav>
        </div>
    );
}
