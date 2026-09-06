import { describe, expect, it } from "vitest";
import { mount } from "@vue/test-utils";
import ProductCard from "../../resources/js/frontend/components/UI/ProductCard.vue";

describe("ProductCard flashsale pricing", () => {
    it("renders the flashsale final price instead of the regular sale price", () => {
        const wrapper = mount(ProductCard, {
            props: {
                product: {
                    id: "product-1",
                    name: "Produk Flashsale",
                    slug: "produk-flashsale",
                    price: 100_000,
                    sale_price: 80_000,
                    pricing: {
                        original_price: 100_000,
                        final_price: 70_000,
                        discount: 30_000,
                        source: "flashsale",
                        flashsale: {
                            id: 1,
                            discount_percentage: 30,
                            stock: 5,
                        },
                    },
                    rating_average: 0,
                    review_count: 0,
                    sold_count: 0,
                },
            },
        });

        expect(wrapper.text()).toContain("70.000");
        expect(wrapper.text()).not.toContain("80.000");
        expect(wrapper.text()).toContain("30%");
        expect(wrapper.find("span.z-10").classes()).toContain("bg-destructive");
        expect(wrapper.find(".text-primary.flex.items-center").text()).toContain("0.0");
    });
});
