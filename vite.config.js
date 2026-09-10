import { defineConfig, loadEnv } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import path from "path";

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), "");
    const devServerUrl = new URL(env.VITE_DEV_SERVER_URL ?? "http://localhost:5173");
    const appUrl = new URL(env.APP_URL ?? "http://localhost:81");
    const appPort = appUrl.port || (appUrl.protocol === "https:" ? "443" : "80");
    const appOrigins = [appUrl.origin, `http://127.0.0.1:${appPort}`, `http://${devServerUrl.hostname}:${appPort}`];
    const devServerPort = Number(devServerUrl.port || 5173);

    return {
        plugins: [
            laravel({
                input: ["resources/css/app.css", "resources/css/filament/admin/theme.css", "resources/js/frontend.js"],
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
        server: {
            host: "0.0.0.0",
            port: devServerPort,
            strictPort: true,
            origin: devServerUrl.origin,
            cors: {
                origin: [...new Set(appOrigins)],
                credentials: true,
            },
            hmr: {
                host: devServerUrl.hostname,
                port: devServerPort,
                clientPort: devServerPort,
                protocol: devServerUrl.protocol === "https:" ? "wss" : "ws",
            },
        },
    };
});
