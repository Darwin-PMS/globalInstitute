import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                // Loading utilities for shells that do not load app.js.
                'resources/js/ui/index.js',
                'resources/js/online-learning/app.js',
                // The Live Class Room is its own bundle; its panels code-split
                // further via dynamic import().
                'resources/js/online-learning/room/app.js',
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
