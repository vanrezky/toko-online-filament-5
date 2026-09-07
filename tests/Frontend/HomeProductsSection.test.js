import { mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import HomeProductsSection from "../../resources/js/frontend/components/UI/HomeProductsSection.vue";

const product = {
    id: "product-1",
    slug: "product-1",
    name: "Product 1",
    price: 100000,
    sale_price: 90000,
    rating_average: 0,
    review_count: 0,
    thumbnail: null,
};

describe("Homepage all-products section", () => {
    it("uses the same responsive default grid as featured products", () => {
        const wrapper = mount(HomeProductsSection, {
            props: { products: { data: [product] } },
        });

        const productGrid = wrapper.findAll("div").find((element) => element.classes().includes("xl:grid-cols-6"));

        expect(productGrid).toBeDefined();
        expect(productGrid.classes()).toContain("grid-cols-2");
        expect(productGrid.classes()).toContain("sm:grid-cols-3");
        expect(productGrid.classes()).not.toContain("md:grid-cols-4");
    });
});
