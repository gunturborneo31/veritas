import '../css/app.css';
import './bootstrap';

import { trackPageViews } from '@/lib/analytics';
import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

trackPageViews(router);

// Nama merek dari server (APP_NAME), agar ganti nama cukup lewat .env tanpa build ulang.
let appName = import.meta.env.VITE_APP_NAME || 'Kitoo';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx'),
        ),
    setup({ el, App, props }) {
        appName = props.initialPage.props.brand || appName;
        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});
