import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/error-pages.css',
                'resources/js/app.js',
                'resources/js/cafe-storefront.js',
                'resources/css/plumber-storefront.css',
                'resources/js/plumber-storefront.js',
                'resources/css/receipt.css',
                'resources/js/receipt.js',
                'resources/css/analytics-dashboard.css',
                'resources/js/analytics-dashboard.js',
                'resources/css/foundation-landing.css',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
