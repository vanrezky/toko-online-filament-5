import { describe, expect, it } from "vitest";
import { mount } from "@vue/test-utils";
import WishlistIndex from "../../resources/js/frontend/pages/Wishlist/Index.vue";

const mountWishlist = (products = []) =>
    mount(WishlistIndex, {
        props: { products },
        global: {
            stubs: {
                TemplateWrapper: { template: "<div><slot /></div>" },
                AccountShell: { template: "<section><slot /></section>" },
                ProductCard: { template: '<div data-test="product-card" />' },
            },
        },
    });

describe("WishlistIndex empty state", () => {
    it("uses the shared box treatment when wishlist is empty", () => {
        const wrapper = mountWishlist();

        expect(wrapper.find(".border-border.space-y-8.rounded-2xl").exists()).toBe(true);
        expect(wrapper.text()).toContain("Your wishlist is empty");
    });

    it("keeps the empty state hidden when wishlist contains products", () => {
        const wrapper = mountWishlist([{ uuid: "product-1" }]);

        expect(wrapper.find(".border-border.space-y-8.rounded-2xl").exists()).toBe(false);
        expect(wrapper.find('[data-test="product-card"]').exists()).toBe(true);
    });
});
