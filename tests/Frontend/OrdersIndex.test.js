import { describe, expect, it } from "vitest";
import { mount } from "@vue/test-utils";
import OrdersIndex from "../../resources/js/frontend/pages/Orders/Index.vue";

describe("OrdersIndex", () => {
    it("renders the shared account navigation with Orders active", () => {
        const wrapper = mount(OrdersIndex, {
            props: {
                orders: {
                    data: [],
                    links: { next: null },
                    meta: { current_page: 1 },
                },
            },
            global: {
                stubs: {
                    TemplateWrapper: { template: "<div><slot /></div>" },
                    AccountShell: {
                        props: ["activeDestination"],
                        template: '<section data-test="account-shell" :data-active-destination="activeDestination"><slot /></section>',
                    },
                },
            },
        });

        expect(wrapper.get('[data-test="account-shell"]').attributes("data-active-destination")).toBe("orders");
        expect(wrapper.text()).toContain("Track the status and details of every order.");
        expect(wrapper.get('[data-test="orders-empty-state"]').classes()).toEqual(
            expect.arrayContaining(["border-border", "space-y-8", "rounded-2xl", "bg-background", "py-20"]),
        );
    });
});
