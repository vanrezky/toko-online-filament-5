import { describe, expect, it, beforeEach, afterEach } from "vitest";
import { mount } from "@vue/test-utils";
import Profile from "../../resources/js/frontend/pages/Account/Profile.vue";

const deferredStub = (resolved) => ({
    props: ["data"],
    data: () => ({ resolved }),
    template: '<div data-test="deferred"><slot v-if="resolved" /><slot v-else name="fallback" /></div>',
});

const mountProfile = (overrides = {}, { deferredResolved = true } = {}) =>
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
                Deferred: deferredStub(deferredResolved),
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
        expect(walletWrapper.get('[data-test="account-page-header"] h1').text()).toContain("Wallet Balance");
        expect(walletWrapper.get('[data-test="account-page-header"] p').text()).toContain("balance");

        window.history.pushState({}, "", "/account?section=overview");
        const overviewWrapper = mountProfile();

        expect(overviewWrapper.find('[data-test="balance-history"]').exists()).toBe(false);
        expect(overviewWrapper.get('[data-test="account-page-header"] h1').text()).toContain("My Account");
    });

    it("does not render wallet content when the balance feature is disabled", () => {
        const wrapper = mountProfile({ balanceEnabled: false });

        expect(wrapper.find('[data-test="balance-section"]').exists()).toBe(false);
    });

    it("uses a stacked mobile-friendly layout for recent orders", () => {
        window.history.pushState({}, "", "/account?section=overview");
        const wrapper = mountProfile({
            recentOrders: [
                {
                    id: "order-1",
                    code: "TRX-202609-RUT53E",
                    status: "cancelled",
                    total: "3800030.00",
                    created_at: "2026-09-04T00:00:00.000000Z",
                },
            ],
        });

        const order = wrapper.get('[data-test="account-recent-order"]');

        expect(order.classes()).toEqual(expect.arrayContaining(["flex-col", "sm:flex-row", "bg-background"]));
        expect(order.text()).toContain("TRX-202609-RUT53E");
    });

    it("keeps the account shell visible while secondary data is pending", () => {
        window.history.pushState({}, "", "/account?section=overview");
        const wrapper = mountProfile({}, { deferredResolved: false });

        expect(wrapper.get('[data-test="account-page-header"]').exists()).toBe(true);
        expect(wrapper.findAll('[role="status"]').length).toBeGreaterThan(0);
        expect(wrapper.find('[data-test="account-recent-order"]').exists()).toBe(false);
    });

    it("uses the Wishlist empty-state treatment for shipping addresses", () => {
        window.history.pushState({}, "", "/account?section=addresses");
        const addressesWrapper = mountProfile({ balanceEnabled: false });

        expect(addressesWrapper.get('[data-test="addresses-empty-state"]').classes()).toEqual(
            expect.arrayContaining(["border-border", "space-y-8", "rounded-2xl", "bg-background", "py-20"]),
        );
    });
});
