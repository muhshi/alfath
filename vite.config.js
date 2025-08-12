import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/js/app.js", "resources/css/app.css"], // Letak file JavaScript dan CSS utama
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            "@": "/resources",
        },
    },
    css: {
        preprocessorOptions: {
            scss: {
                additionalData: `@use "sass:math";`, // Tambahkan ini jika ada masalah dengan Sass
            },
        },
    },
    build: {
        outDir: "public/build", // Folder output build
    },
});
