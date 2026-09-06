import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Livewire/**/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Light theme surfaces (KAF layout, white background)
                canvas: '#F7F8FA',
                surface: '#FFFFFF',
                line: '#E7EAF0',
                ink: {
                    DEFAULT: '#0B0F1A',
                    muted: '#5A6478',
                    faint: '#94A0B4',
                },
                brand: {
                    50: '#FFF3ED',
                    100: '#FFE2D2',
                    200: '#FFC3A6',
                    300: '#FF9C70',
                    400: '#FB7A45',
                    500: '#F2551B',
                    600: '#DC3F0C',
                    700: '#B22F0B',
                    800: '#8C2710',
                    900: '#722311',
                },
                up: '#16A34A',
                warn: '#F97316',
                down: '#EF4444',
            },
            boxShadow: {
                card: '0 1px 2px rgba(16, 24, 40, 0.04), 0 1px 3px rgba(16, 24, 40, 0.06)',
                pop: '0 12px 32px rgba(16, 24, 40, 0.12)',
            },
            borderRadius: {
                xl2: '1rem',
            },
        },
    },

    plugins: [forms],
};
