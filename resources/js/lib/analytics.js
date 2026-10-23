/**
 * Kunjungan halaman ke Google Analytics. Aplikasi ini satu halaman (Inertia), jadi setiap
 * perpindahan halaman dikirim manual. Bagian URL yang berisi rahasia dibersihkan dulu agar
 * tidak tersimpan di Google.
 */
const PRIVATE_PARAMS = ['token', 'tamu', 'signature', 'expires', 'email'];

const PRIVATE_PATHS = [
    [/^\/patungan\/[^/]+/, '/patungan/:token'],
    [/^\/reset-password\/[^/]+/, '/reset-password/:token'],
    [/^\/media\/[^/]+/, '/media/:id'],
];

export function sanitizedLocation(href) {
    const url = new URL(href);

    PRIVATE_PARAMS.forEach((param) => url.searchParams.delete(param));
    PRIVATE_PATHS.forEach(([pattern, replacement]) => {
        url.pathname = url.pathname.replace(pattern, replacement);
    });

    return url.toString();
}

export function trackPageViews(router) {
    router.on('navigate', () => {
        if (typeof window.gtag !== 'function') {
            return;
        }

        // Tunggu <Head> Inertia memperbarui judul halaman.
        setTimeout(() => {
            window.gtag('event', 'page_view', {
                page_location: sanitizedLocation(window.location.href),
                page_title: document.title,
            });
        }, 0);
    });
}
