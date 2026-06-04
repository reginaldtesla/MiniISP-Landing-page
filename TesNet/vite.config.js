import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/portal.css',
                'resources/js/portal-theme.js',
                'resources/js/portal-announcements.js',
                'resources/js/portal-custom-calculator.js',
                'resources/js/portal-plan-countdown.js',
                'resources/js/portal-data-refresh.js',
                'resources/js/portal-live-usage.js',
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
