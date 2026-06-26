import { config } from '@vue/test-utils';
import { vi } from 'vitest';
import { createI18n } from 'vue-i18n';
import en from '../../resources/js/locales/en.json';
import id from '../../resources/js/locales/id.json';

// Mock global route function used by Ziggy
global.route = vi.fn((name, params) => {
    if (params) {
        const paramStr = typeof params === 'object' && params.token
            ? `/${params.token}`
            : '';
        return `/${name}${paramStr}`;
    }
    return `/${name}`;
});

// Mock vue-sonner
global.toast = {
    success: vi.fn(),
    error: vi.fn(),
    info: vi.fn(),
};

// Setup i18n for tests
const i18n = createI18n({
    legacy: false,
    locale: 'en',
    fallbackLocale: 'en',
    globalInjection: true,
    messages: {
        en: {
            ...en,
            labels: en,
            meta: {
                newsletter_manage: {
                    title: 'Newsletter Management'
                }
            }
        },
        id: {
            ...id,
            labels: id,
        },
    },
});

// Global config for Vue Test Utils
config.global.mocks = {
    route: global.route,
};

config.global.plugins = [i18n];

// Mock Inertia's usePage
vi.mock('@inertiajs/vue3', async () => {
    const actual = await vi.importActual('@inertiajs/vue3');
    return {
        ...actual,
        usePage: () => ({
            props: {
                settings: { site_name: 'Test Store', logo: null },
                menu: { footer: [] },
                promotions: { data: [] },
            },
        }),
        useForm: (data) => ({
            ...data,
            errors: {},
            processing: false,
            post: vi.fn(function(url, options) {
                if (options?.onSuccess) options.onSuccess();
                return Promise.resolve();
            }),
            reset: vi.fn(),
        }),
        Link: {
            props: ['href'],
            template: '<a :href="href"><slot /></a>',
        },
    };
});