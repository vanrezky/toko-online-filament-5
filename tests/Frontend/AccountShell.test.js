import { beforeEach, describe, expect, it } from "vitest";
import { mount } from "@vue/test-utils";
import AccountShell from "../../resources/js/frontend/components/Account/AccountShell.vue";

const destinations = ["overview", "settings", "password", "addresses"];

const mountShell = (activeDestination = "overview") =>
    mount(AccountShell, {
        props: {
            activeDestination,
            balanceEnabled: activeDestination === "balance",
            user: {
                full_name: "Customer Test",
                email: "customer@example.test",
            },
        },
        slots: {
            default: '<p data-test="account-content">Account content</p>',
        },
    });

describe("AccountShell navigation", () => {
    beforeEach(() => {
        global.route.mockReset();
        global.route.mockImplementation((name, params) => (params?.section ? `/${name}?section=${params.section}` : `/${name}`));
    });

    it("renders every account and shopping destination in desktop and mobile navigation", () => {
        const wrapper = mountShell();

        expect(wrapper.find("aside.hidden.lg\\:block").exists()).toBe(true);
        expect(wrapper.find('[data-test="account-mobile-navigation"]').exists()).toBe(true);
        expect(wrapper.find('[data-test="account-mobile-navigation"]').classes()).not.toContain("overflow-x-auto");

        for (const destination of destinations) {
            expect(wrapper.findAll(`a[href="/frontend.account?section=${destination}"]`)).toHaveLength(2);
        }

        expect(wrapper.findAll('a[href="/frontend.orders"]')).toHaveLength(2);
        expect(wrapper.findAll('a[href="/frontend.wishlist"]')).toHaveLength(2);
        expect(wrapper.find('[data-test="mobile-account-logout"]').exists()).toBe(true);
    });

    it("marks the URL-backed active destination in both responsive navigation variants", () => {
        const wrapper = mountShell("password");
        const passwordLinks = wrapper.findAll('a[href="/frontend.account?section=password"]');

        expect(passwordLinks).toHaveLength(1);
        expect(passwordLinks[0].classes()).toContain("bg-primary");
        expect(wrapper.find('[data-test="mobile-account-context"]').exists()).toBe(true);
    });

    it("renders wallet balance in both navigation variants when enabled", () => {
        const wrapper = mountShell("balance");
        const balanceLinks = wrapper.findAll('a[href="/frontend.account?section=balance"]');

        expect(balanceLinks).toHaveLength(1);
        expect(balanceLinks[0].classes()).toContain("bg-primary");
        expect(wrapper.find('[data-test="mobile-account-context"]').exists()).toBe(true);
    });

    it("does not render wallet balance when disabled", () => {
        const wrapper = mount(AccountShell, {
            props: {
                activeDestination: "overview",
                balanceEnabled: false,
                user: { full_name: "Customer Test", email: "customer@example.test" },
            },
            slots: { default: "<p>Account content</p>" },
        });

        expect(wrapper.findAll('a[href="/frontend.account?section=balance"]')).toHaveLength(0);
    });

    it("keeps address navigation active while its URL-backed form is open", () => {
        const wrapper = mountShell("address_form");
        const addressLinks = wrapper.findAll('a[href="/frontend.account?section=addresses"]');

        expect(addressLinks).toHaveLength(1);
        expect(addressLinks[0].classes()).toContain("bg-primary");
    });

    it("places the mobile identity summary before the account menu on the overview", () => {
        const wrapper = mountShell();
        const mainHtml = wrapper.find("main").html();

        expect(wrapper.find('[data-test="mobile-account-identity"]').exists()).toBe(true);
        expect(mainHtml.indexOf('data-test="mobile-account-identity"')).toBeLessThan(mainHtml.indexOf('data-test="account-mobile-navigation"'));
        expect(mainHtml.indexOf('data-test="account-mobile-navigation"')).toBeLessThan(mainHtml.indexOf('data-test="account-content"'));
    });

    it("shows the mobile account menu only on the overview", () => {
        const wrapper = mountShell("settings");

        expect(wrapper.find('[data-test="account-mobile-navigation"]').exists()).toBe(false);
        expect(wrapper.find('[data-test="mobile-account-context"]').text()).toContain("Settings");
        expect(wrapper.find('[data-test="mobile-account-context-logout"]').exists()).toBe(false);
    });
});
