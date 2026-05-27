import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import path from "path";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/css/filament/admin/theme.css",
                "resources/js/frontend.js",
            ],
            refresh: true,
        }),
        vue(),
    ],
    resolve: {
        alias: {
            "@frontend": path.resolve(__dirname, "resources/js/frontend"),
            "@styles": path.resolve(__dirname, "resources/css/frontend"),
            "ziggy-js": path.resolve("vendor/tightenco/ziggy/dist/index.esm.js"),
        },
    },
    // server: {
    //     host: "0.0.0.0",
    //     port: 5174,
    //     strictPort: true,
    //     origin: "http://localhost:81",
    //     cors: {
    //         origin: ["http://100.100.216.84:81", "http://localhost:81", "http://127.0.0.1:81"],
    //         credentials: true,
    //     },
    //     hmr: {
    //         host: "100.100.216.84",
    //         port: 5174,
    //         protocol: "ws",
    //     },
    // },
});
