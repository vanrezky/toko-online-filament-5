import { mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import FlashSaleSection from "../../resources/js/frontend/components/UI/FlashSaleSection.vue";

const product = (id) => ({
    id,
    slug: `product-${id}`,
    name: `Product ${id}`,
    price: 100000,
    sale_price: 90000,
    rating_average: 0,
    review_count: 0,
    thumbnail: null,
});

describe("Flash sale section", () => {
    it("uses six desktop columns for flash sale products", () => {
        const wrapper = mount(FlashSaleSection, {
            props: {
                flashsales: {
                    name: "Flash Sale",
                    description: "Promo terbatas",
                    end_time: new Date(Date.now() + 60 * 60 * 1000).toISOString(),
                    products: Array.from({ length: 6 }, (_, index) => ({ product: product(index + 1) })),
                },
            },
        });

        const productGrid = wrapper.find(".flash-sale-products-grid");

        expect(productGrid).toBeDefined();
        expect(productGrid.findAll("article")).toHaveLength(6);
        expect(productGrid.findAll("div").some((element) => element.classes().includes("lg:w-auto"))).toBe(true);
    });
});
