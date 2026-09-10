import { describe, expect, it, vi } from "vitest";
import { mount } from "@vue/test-utils";
import { router, usePage } from "@inertiajs/vue3";
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

    it("refreshes only wishlist props when toggling wishlist", async () => {
        vi.mocked(usePage).mockReturnValue({
            props: {
                auth: { user: { id: "customer-1" } },
                wishlist_product_ids: [],
            },
        });
        const post = vi.spyOn(router, "post").mockImplementation(() => undefined);
        const wrapper = mount(ProductCard, {
            props: {
                product: {
                    id: "product-1",
                    name: "Produk",
                    slug: "produk",
                    price: 100_000,
                    sale_price: 90_000,
                    rating_average: 0,
                    review_count: 0,
                    sold_count: 0,
                },
            },
        });

        await wrapper.find("button").trigger("click");

        expect(post).toHaveBeenCalledWith(
            "/frontend.wishlist.toggle",
            { product_id: "product-1" },
            expect.objectContaining({
                preserveScroll: true,
                only: ["wishlist_product_ids"],
            }),
        );

        post.mockRestore();
    });
});
