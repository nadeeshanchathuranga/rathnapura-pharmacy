import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    server: {
        watch: {
            ignored: ['**/.env'],
        },
    },
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: [
                'resources/routes/**',
                'resources/views/**',
            ],
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});
