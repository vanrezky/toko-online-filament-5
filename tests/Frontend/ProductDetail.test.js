import { nextTick, reactive } from "vue";
import { describe, expect, it, vi } from "vitest";
import { mount } from "@vue/test-utils";
import { router, usePage } from "@inertiajs/vue3";
import ProductDetail from "../../resources/js/frontend/pages/Products/Show.vue";

let currentPageProps;

const DeferredStub = {
    props: { data: { type: [String, Array], required: true } },
    computed: {
        isPending() {
            const keys = Array.isArray(this.data) ? this.data : [this.data];

            return keys.some((key) => currentPageProps[key] === undefined);
        },
    },
    template: `
        <template v-if="isPending"><slot name="fallback" /></template>
        <template v-else><slot /></template>
    `,
};

const product = {
    id: "product-1",
    name: "Demo Product",
    slug: "demo-product",
    code: "DEMO-1",
    description: "A demo product.",
    digital: false,
    stock: 10,
    price: 100000,
    sale_price: 90000,
    min_order: 1,
    fake_sold_count: 0,
    sold_count: 0,
    weight: 100,
    thumbnail: null,
    images: [],
    image_thumbnails: [],
    category: { id: "category-1", name: "Demo", slug: "demo" },
    warehouse: null,
    variants: [],
    wholesales: [],
    faqs: [],
    pricing: { final_price: 90000, original_price: 100000, source: "sale" },
};

const mountProductDetail = (relatedProducts) => {
    currentPageProps = reactive({
        settings: { site_name: "Test Store" },
        wishlist_product_ids: [],
        relatedProducts,
    });
    vi.mocked(usePage).mockReturnValue({ props: currentPageProps });

    return mount(ProductDetail, {
        props: { product, relatedProducts },
        global: {
            stubs: {
                Deferred: DeferredStub,
                TemplateWrapper: { template: "<div><slot /></div>" },
                PageShell: { template: "<main><slot /></main>" },
                QuantityStepper: { template: "<div />" },
                ProductRecommendationCard: {
                    props: ["product"],
                    template: '<article data-test="related-card">{{ product.name }}</article>',
                },
                Button: { template: "<button><slot /></button>" },
            },
        },
    });
};

describe("Product detail related products", () => {
    beforeEach(() => {
        global.IntersectionObserver = class {
            observe() {}
            unobserve() {}
            disconnect() {}
        };
    });

    it("keeps primary content visible with an accessible loading fallback", () => {
        const wrapper = mountProductDetail(undefined);

        expect(wrapper.find("h1").text()).toBe("Demo Product");
        expect(wrapper.get('[role="status"]').text()).toContain("Loading...");
        expect(wrapper.get('[role="status"]').attributes("aria-live")).toBe("polite");
    });

    it("renders related products after the deferred prop resolves", async () => {
        const wrapper = mountProductDetail(undefined);

        currentPageProps.relatedProducts = [{ id: "related-1", name: "Related Product" }];
        await wrapper.setProps({ relatedProducts: currentPageProps.relatedProducts });
        await nextTick();

        expect(wrapper.find('[role="status"]').exists()).toBe(false);
        expect(wrapper.get('[data-test="related-card"]').text()).toBe("Related Product");
    });

    it("refreshes only cart total and flash props when adding to cart", async () => {
        const post = vi.spyOn(router, "post").mockImplementation(() => undefined);
        const wrapper = mountProductDetail([]);
        const addToCartButton = wrapper.findAll("button").find((button) => button.text().includes("Add to Cart"));

        await addToCartButton.trigger("click");

        expect(post).toHaveBeenCalledWith(
            "/frontend.cart.store",
            { product_id: "product-1", product_variant_id: undefined, quantity: 1 },
            expect.objectContaining({
                preserveScroll: true,
                preserveState: true,
                only: ["cart_total", "flash"],
            }),
        );

        post.mockRestore();
    });
});
