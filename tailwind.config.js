import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    // GSES navy mapped to brand.green keys for full-app theme
                    green: {
                        DEFAULT: 'rgb(26, 54, 124)',
                        dark: 'rgb(20, 42, 98)',
                        darker: 'rgb(13, 31, 74)',
                        light: 'rgb(42, 74, 154)',
                        50: 'rgb(240, 243, 250)',
                        100: 'rgb(214, 222, 240)',
                        200: 'rgb(168, 184, 220)',
                    },
                    gold: {
                        DEFAULT: '#d4af37',
                        light: '#e5c65a',
                    },
                },
            },
        },
    },

    plugins: [forms],
};
