import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/website.css',
                'resources/css/website-mobile.css',
                'resources/css/website-theme.css',
                'resources/js/app.js',
                'resources/js/website-lottie.js',
            ],
            refresh: true,
        }),
    ],
});
