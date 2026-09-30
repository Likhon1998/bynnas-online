import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

const shades = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];

/**
 * Palette backed by CSS variables (see resources/css/app.css) so the admin panel
 * can re-skin slate/gray/indigo/blue to the storefront theme without touching views.
 */
const cssVarPalette = (name) =>
    Object.fromEntries(shades.map((s) => [s, `rgb(var(--c-${name}-${s}) / <alpha-value>)`]));

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
                sans: ['Nunito', 'Figtree', ...defaultTheme.fontFamily.sans],
                display: ['Fredoka', 'Nunito', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                slate: cssVarPalette('slate'),
                gray: cssVarPalette('gray'),
                indigo: cssVarPalette('indigo'),
                violet: cssVarPalette('violet'),
                blue: cssVarPalette('blue'),
            },
        },
    },

    plugins: [forms],
};
