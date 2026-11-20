import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    DEFAULT: '#059669', // Fresh Emerald Green
                    hover: '#047857', // Deep Emerald Hover
                    light: '#10b981', // Vibrant Mint Green
                    dark: '#065f46', // Forest Emerald
                },
                secondary: {
                    DEFAULT: '#f0fdf4', // Fresh Soft Mint
                    hover: '#dcfce7', // Soft Green Hover
                },
            },
        },
    },

    plugins: [forms],
};
