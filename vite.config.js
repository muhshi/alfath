import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            // kalau kamu memang pakai SCSS, pakai file scss sebagai entry
            input: ["resources/js/app.js", "resources/sass/tabler.scss"],
            refresh: true,
        }),
    ],
    resolve: {
        alias: { "@": "/resources" },
    },
    css: {
        preprocessorOptions: {
            scss: {
                // bantu Sass menemukan partial bootstrap
                includePaths: ["node_modules", "node_modules/bootstrap/scss"],
            },
        },
    },
    build: { outDir: "public/build" },
});
