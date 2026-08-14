import { beforeEach, describe, expect, it } from "vitest";
import { mount } from "@vue/test-utils";
import AccountShell from "../../resources/js/frontend/components/Account/AccountShell.vue";

const destinations = ["overview", "settings", "password", "addresses"];

const mountShell = (activeDestination = "overview") =>
    mount(AccountShell, {
        props: {
            activeDestination,
            user: {
                full_name: "Customer Test",
                email: "customer@example.test",
            },
        },
        slots: {
            default: "<p>Account content</p>",
        },
    });

describe("AccountShell navigation", () => {
    beforeEach(() => {
        global.route.mockReset();
        global.route.mockImplementation((name, params) =>
            params?.section ? `/${name}?section=${params.section}` : `/${name}`,
        );
    });

    it("renders every account and shopping destination in desktop and mobile navigation", () => {
        const wrapper = mountShell();

        expect(wrapper.find("aside.hidden.lg\\:block").exists()).toBe(true);
        expect(wrapper.find("main > nav").classes()).toEqual(
            expect.arrayContaining(["overflow-x-auto", "lg:hidden"]),
        );

        for (const destination of destinations) {
            expect(wrapper.findAll(`a[href="/frontend.account?section=${destination}"]`)).toHaveLength(2);
        }

        expect(wrapper.findAll('a[href="/frontend.orders"]')).toHaveLength(2);
        expect(wrapper.findAll('a[href="/frontend.wishlist"]')).toHaveLength(2);
    });

    it("marks the URL-backed active destination in both responsive navigation variants", () => {
        const wrapper = mountShell("password");
        const passwordLinks = wrapper.findAll('a[href="/frontend.account?section=password"]');

        expect(passwordLinks).toHaveLength(2);
        passwordLinks.forEach((link) => {
            expect(link.classes()).toContain("bg-primary");
        });
    });

    it("keeps address navigation active while its URL-backed form is open", () => {
        const wrapper = mountShell("address_form");
        const addressLinks = wrapper.findAll('a[href="/frontend.account?section=addresses"]');

        expect(addressLinks).toHaveLength(2);
        addressLinks.forEach((link) => {
            expect(link.classes()).toContain("bg-primary");
        });
    });
});
