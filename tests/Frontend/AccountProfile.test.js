import { describe, expect, it, beforeEach, afterEach } from "vitest";
import { mount } from "@vue/test-utils";
import Profile from "../../resources/js/frontend/pages/Account/Profile.vue";

const mountProfile = (overrides = {}) =>
    mount(Profile, {
        props: {
            user: { balance: "125000.00" },
            addresses: [],
            provinces: [],
            totalOrders: 0,
            recentOrders: [],
            balanceEnabled: true,
            balanceHistory: [
                {
                    id: 1,
                    amount: "25000.00",
                    trx_type: "+",
                    notes: "Top up",
                    created_at: "2026-09-01T00:00:00.000000Z",
                },
            ],
            passwordRequirementsEnabled: false,
            ...overrides,
        },
        global: {
            stubs: {
                TemplateWrapper: { template: "<div><slot /></div>" },
                PageShell: { template: "<div><slot /></div>" },
                AccountShell: {
                    props: ["activeDestination", "balanceEnabled"],
                    template: '<section data-test="account-shell" :data-active-destination="activeDestination"><slot /></section>',
                },
                AccountProfileSettings: { template: "<div />" },
                AccountPasswordForm: { template: "<div />" },
            },
        },
    });

describe("Account Profile wallet and empty states", () => {
    beforeEach(() => {
        window.history.pushState({}, "", "/account?section=balance");
    });

    afterEach(() => {
        window.history.pushState({}, "", "/account");
    });

    it("renders the wallet section and history outside the overview", () => {
        const walletWrapper = mountProfile();

        expect(walletWrapper.get('[data-test="balance-section"]').exists()).toBe(true);
        expect(walletWrapper.get('[data-test="balance-history"]').text()).toContain("Top up");
        expect(walletWrapper.text()).toContain("125,000");

        window.history.pushState({}, "", "/account?section=overview");
        const overviewWrapper = mountProfile();

        expect(overviewWrapper.find('[data-test="balance-history"]').exists()).toBe(false);
    });

    it("does not render wallet content when the balance feature is disabled", () => {
        const wrapper = mountProfile({ balanceEnabled: false });

        expect(wrapper.find('[data-test="balance-section"]').exists()).toBe(false);
    });

    it("uses the Wishlist empty-state treatment for shipping addresses", () => {
        window.history.pushState({}, "", "/account?section=addresses");
        const addressesWrapper = mountProfile({ balanceEnabled: false });

        expect(addressesWrapper.get('[data-test="addresses-empty-state"]').classes()).toEqual(
            expect.arrayContaining(["border-border", "space-y-8", "rounded-2xl", "bg-background", "py-20"]),
        );
    });
});
