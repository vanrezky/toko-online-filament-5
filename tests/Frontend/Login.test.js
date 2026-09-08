import { beforeEach, describe, expect, it, vi } from "vitest";
import { mount } from "@vue/test-utils";
import { usePage } from "@inertiajs/vue3";
import Login from "../../resources/js/frontend/Pages/Auth/Login.vue";

const mountLogin = (socialLoginEnabled, registration = true) => {
    vi.mocked(usePage).mockReturnValue({
        props: {
            settings: {
                is_private_store: false,
                registration,
                social_login_enabled: socialLoginEnabled,
            },
        },
    });

    return mount(Login, {
        global: {
            stubs: {
                Button: { template: "<button><slot /></button>" },
                Card: { template: "<div><slot /></div>" },
                FormCheckbox: { template: "<input type='checkbox' />" },
                FormInput: { template: "<input />" },
                PageShellAuth: { template: "<div><slot /></div>" },
            },
        },
    });
};

describe("Login social authentication controls", () => {
    beforeEach(() => {
        vi.mocked(usePage).mockReset();
    });

    it("renders social login controls when enabled", () => {
        const wrapper = mountLogin(true);

        expect(wrapper.findAll("button").some((button) => button.text().includes("Google"))).toBe(true);
        expect(wrapper.findAll("button").some((button) => button.text().includes("GitHub"))).toBe(true);
        expect(wrapper.findAll('div[aria-hidden="true"]').length).toBeGreaterThan(0);
    });

    it("hides social login controls when disabled", () => {
        const wrapper = mountLogin(false);

        expect(wrapper.findAll("button").some((button) => button.text().includes("Google"))).toBe(false);
        expect(wrapper.findAll("button").some((button) => button.text().includes("GitHub"))).toBe(false);
        expect(wrapper.findAll('div[aria-hidden="true"]')).toHaveLength(0);
    });

    it("hides the registration call to action when registration is disabled", () => {
        const wrapper = mountLogin(true, false);

        expect(wrapper.find('a[href="/frontend.registration-closed"]').exists()).toBe(true);
        expect(wrapper.find('a[href="/frontend.signup"]').exists()).toBe(false);
    });
});
