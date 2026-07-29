import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/js/app.js", "resources/sass/tabler.scss", "resources/css/app.css"],
            refresh: true,
        }),
    ],
    resolve: {
        alias: { "@": "/resources" },
    },
    css: {
        preprocessorOptions: {
            scss: {
                includePaths: ["node_modules", "node_modules/bootstrap/scss"],
                quietDeps: true,
                silenceDeprecations: ['import', 'global-builtin', 'legacy-js-api', 'color-functions'],
            },
        },
    },
    build: { outDir: "public/build" },
});
