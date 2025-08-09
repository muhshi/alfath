import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            '@tabler': path.resolve(__dirname, 'node_modules/@tabler'),
            '@icons-webfont': path.resolve(__dirname, 'node_modules/@tabler/icons-webfont'),
        },
    },
});
