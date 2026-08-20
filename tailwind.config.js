import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['Poppins', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Quotaire marketing-site palette (public pages only — the
                // logged-in app keeps its existing indigo). "cream" is the
                // mint-green hero background, "navy" is the dark heading/
                // footer color, from the brand's own template.
                cream: '#c6f0d0',
                navy: '#2f327d',
            },
        },
    },

    plugins: [forms],
};
