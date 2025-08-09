import { defineConfig } from "vite";
import path from "path";

export default defineConfig({
    resolve: {
        alias: {
            "@": path.resolve(__dirname, "./src"),
            "@tabler/core": path.resolve(
                __dirname,
                "./node_modules/@tabler/core"
            ),
        },
    },
    css: {
        preprocessorOptions: {
            scss: {
                silenceDeprecations: [
                    "import",
                    "legacy-js-api",
                    "global-builtin",
                    "color-functions",
                ],
            },
        },
    },
});
