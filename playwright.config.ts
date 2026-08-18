import { defineConfig, devices } from '@playwright/test'

export default defineConfig({
    testDir: './tests/e2e',

    timeout: 30_000,

    use: {
        baseURL: 'http://127.0.0.1:81',

        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
    },

    projects: [
        {
            name: 'chromium',
            use: {
                ...devices['Desktop Chrome'],
            },
        },
    ],
})