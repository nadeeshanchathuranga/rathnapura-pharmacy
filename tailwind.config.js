import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.jsx', // Add this if using React
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },

            // ⭐ ADD YOUR GLOBAL COLORS HERE ⭐
            colors: {
                primary: '#2563eb',   // Blue (Professional)
                secondary: '#1e293b', // Dark Slate
                accent: '#38bdf8',    // Light Sky Blue
                light: '#f8fafc',     // Light background
                dark: '#0f172a',      // Dark background
            },
        },
    },

    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
        require('@tailwindcss/aspect-ratio'),
    ],
};
