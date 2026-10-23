import forms from '@tailwindcss/forms';

/**
 * Token desain diambil dari konsep Stitch di folder conceptdesigns/.
 *
 * @type {import('tailwindcss').Config}
 */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            colors: {
                // Keluarga warna primary memakai CSS variable agar bisa diganti per tema template
                // (lihat lib/theme.js). Nilai bawaan ada di resources/css/app.css.
                primary: 'rgb(var(--color-primary) / <alpha-value>)',
                'on-primary': '#ffffff',
                'primary-container': 'rgb(var(--color-primary-container) / <alpha-value>)',
                'on-primary-container': '#fffbff',
                'primary-fixed': 'rgb(var(--color-primary-fixed) / <alpha-value>)',
                'primary-fixed-dim': 'rgb(var(--color-primary-fixed-dim) / <alpha-value>)',
                'on-primary-fixed': 'rgb(var(--color-on-primary-fixed) / <alpha-value>)',
                'on-primary-fixed-variant': 'rgb(var(--color-on-primary-fixed) / <alpha-value>)',
                'inverse-primary': '#ffb4a8',
                secondary: '#635d59',
                'on-secondary': '#ffffff',
                'secondary-container': '#e7ded9',
                'on-secondary-container': '#68615e',
                'secondary-fixed': '#eae1dc',
                'secondary-fixed-dim': '#cec5c0',
                'on-secondary-fixed': '#1f1b18',
                'on-secondary-fixed-variant': '#4b4642',
                tertiary: '#7b5500',
                'on-tertiary': '#ffffff',
                'tertiary-container': '#9b6b00',
                'on-tertiary-container': '#fffbff',
                'tertiary-fixed': '#ffdead',
                'tertiary-fixed-dim': '#fabc4d',
                'on-tertiary-fixed': '#281900',
                'on-tertiary-fixed-variant': '#604100',
                error: '#ba1a1a',
                'on-error': '#ffffff',
                'error-container': '#ffdad6',
                'on-error-container': '#93000a',
                background: '#fff7f9',
                'on-background': '#281526',
                surface: '#fff7f9',
                'surface-bright': '#fff7f9',
                'surface-dim': '#f1d1e9',
                'surface-tint': '#ab3425',
                'surface-variant': '#fad9f2',
                'surface-container-lowest': '#ffffff',
                'surface-container-low': '#ffeff8',
                'surface-container': '#ffe7f8',
                'surface-container-high': '#ffdff7',
                'surface-container-highest': '#fad9f2',
                'on-surface': '#281526',
                'on-surface-variant': '#58413d',
                'inverse-surface': '#3f293c',
                'inverse-on-surface': '#ffebf8',
                outline: '#8c716c',
                'outline-variant': '#e0bfba',
                whatsapp: '#25D366',
                success: '#1b873f',
            },
            borderRadius: {
                DEFAULT: '0.25rem',
                lg: '0.5rem',
                xl: '0.75rem',
                full: '9999px',
            },
            spacing: {
                'space-xs': '0.25rem',
                'space-sm': '0.5rem',
                'space-md': '1rem',
                'space-lg': '1.5rem',
                'space-xl': '2.5rem',
                margin: '1.25rem',
                gutter: '1rem',
            },
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                display: ['var(--font-display)', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            },
            fontSize: {
                'display-hero': ['40px', { lineHeight: '48px', letterSpacing: '-0.03em', fontWeight: '800' }],
                'display-hero-mobile': ['32px', { lineHeight: '38px', letterSpacing: '-0.02em', fontWeight: '800' }],
                'headline-lg': ['30px', { lineHeight: '38px', letterSpacing: '-0.02em', fontWeight: '700' }],
                'headline-lg-mobile': ['24px', { lineHeight: '32px', letterSpacing: '-0.01em', fontWeight: '700' }],
                'headline-md': ['22px', { lineHeight: '28px', letterSpacing: '-0.01em', fontWeight: '600' }],
                'headline-sm': ['18px', { lineHeight: '24px', fontWeight: '600' }],
                'body-lg': ['18px', { lineHeight: '28px', fontWeight: '400' }],
                'body-md': ['16px', { lineHeight: '24px', fontWeight: '400' }],
                'body-sm': ['14px', { lineHeight: '20px', fontWeight: '400' }],
                'label-lg': ['15px', { lineHeight: '20px', letterSpacing: '0.01em', fontWeight: '600' }],
                'label-md': ['13px', { lineHeight: '18px', letterSpacing: '0.01em', fontWeight: '600' }],
                'label-sm': ['12px', { lineHeight: '16px', letterSpacing: '0.02em', fontWeight: '700' }],
            },
            keyframes: {
                'scene-in': {
                    '0%': { opacity: '0', transform: 'translateY(14px) scale(0.98)' },
                    '100%': { opacity: '1', transform: 'translateY(0) scale(1)' },
                },
                flicker: {
                    '0%': { transform: 'scaleY(1) rotate(-2deg)', opacity: '1' },
                    '100%': { transform: 'scaleY(0.88) rotate(3deg)', opacity: '0.85' },
                },
                'layer-in': {
                    '0%': { opacity: '0', transform: 'translateY(-10px) rotate(-1deg)' },
                    '100%': { opacity: '1', transform: 'translateY(0) rotate(0)' },
                },
            },
            animation: {
                'scene-in': 'scene-in 0.45s cubic-bezier(0.2, 0.8, 0.2, 1) both',
                'layer-in': 'layer-in 0.4s ease-out both',
            },
            boxShadow: {
                card: '0 4px 20px -2px rgba(40,21,38,0.06), 0 2px 6px -1px rgba(168,50,35,0.04)',
                bar: '0 -4px 24px rgba(46,26,44,0.06)',
            },
        },
    },

    plugins: [forms],
};
