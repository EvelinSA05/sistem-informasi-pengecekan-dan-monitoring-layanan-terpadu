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
                    DEFAULT: '#001166', // Atlantic Depths
                    hover: '#000c4d', // Slightly darker for hover
                },
                secondary: {
                    DEFAULT: '#f0f0e7', // Aspen Snow
                    hover: '#e2e2d9',
                },
            },
        },
    },

    plugins: [forms],
};
