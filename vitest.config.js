import { configDefaults, defineConfig } from "vitest/config";
import vue from "@vitejs/plugin-vue";
import { resolve } from "path";

export default defineConfig({
    plugins: [vue()],
    test: {
        environment: "jsdom",
        globals: true,
        setupFiles: ["./tests/Frontend/setup.js"],
        exclude: [...configDefaults.exclude, "tests/e2e/**"],
    },
    resolve: {
        alias: {
            "@": resolve(__dirname, "resources/js"),
            "@frontend": resolve(__dirname, "resources/js/frontend"),
        },
    },
});
