import { config } from '@vue/test-utils';
import { vi } from 'vitest';

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

// Global config for Vue Test Utils
config.global.mocks = {
    route: global.route,
};

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