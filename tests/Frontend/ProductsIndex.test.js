import { mount } from "@vue/test-utils";
import { describe, expect, it, vi } from "vitest";
import { router } from "@inertiajs/vue3";
import ProductsIndex from "../../resources/js/frontend/pages/Products/Index.vue";

const baseProps = {
    products: {
        data: [{ id: "product-1", name: "Demo Product", slug: "demo-product" }],
        links: { prev: null, next: null },
        meta: { from: 1, to: 1, total: 1, links: [] },
    },
    categories: [{ id: "category-1", slug: "fashion", name: "Fashion" }],
    filters: {},
};

const mountProducts = (props = {}) => mount(ProductsIndex, {
    props: { ...baseProps, ...props },
    global: {
        stubs: {
            TemplateWrapper: { template: "<div><slot /></div>" },
            PageShell: { template: "<div><slot name='actions' /><slot /></div>" },
            ProductCard: { template: "<article />" },
            Card: { template: "<aside><slot /></aside>" },
        },
    },
});

describe("ProductsIndex", () => {
    it("shows the newest sort option by default without a duplicate page search field", () => {
        const wrapper = mountProducts();

        expect(wrapper.find("#product-sort").element.value).toBe("newest");
        expect(wrapper.findAll("input").some((input) => input.attributes("aria-label") === "Search")).toBe(false);
    });

    it("opens the mobile filter sheet and submits the shared variant state", async () => {
        const get = vi.spyOn(router, "get").mockImplementation(() => undefined);
        const wrapper = mountProducts();

        await wrapper.get('[aria-controls="mobile-product-filters"]').trigger("click");
        const sizeOption = wrapper.findAll("#mobile-product-filters label").find((label) => label.text().trim() === "M").find("input");
        await sizeOption.setValue(true);
        await wrapper.findAll("#mobile-product-filters button").find((button) => button.text().includes("Apply Filters")).trigger("click");

        expect(get).toHaveBeenCalledWith("/frontend.products", expect.objectContaining({ variant_size: "M" }), expect.any(Object));
        expect(wrapper.find("#mobile-product-filters").exists()).toBe(false);
        get.mockRestore();
    });

    it("removes an active filter chip through the same query path", async () => {
        const get = vi.spyOn(router, "get").mockImplementation(() => undefined);
        const wrapper = mountProducts({ filters: { variant_size: ["M"] } });

        await wrapper.get('[aria-label="Remove M filter"]').trigger("click");

        expect(get).toHaveBeenCalledWith("/frontend.products", {}, expect.any(Object));
        expect(wrapper.find('[aria-label="Remove M filter"]').exists()).toBe(false);
        get.mockRestore();
    });

    it("renders numbered pagination links from the paginator metadata", () => {
        const wrapper = mountProducts({
            products: {
                ...baseProps.products,
                links: { prev: null, next: "/frontend.products?page=2" },
                meta: {
                    from: 1,
                    to: 12,
                    total: 24,
                    links: [
                        { label: "1", url: "/frontend.products?page=1", active: true },
                        { label: "2", url: "/frontend.products?page=2", active: false },
                    ],
                },
            },
        });

        expect(wrapper.findAll('[aria-label="Product page navigation"] a')).toHaveLength(2);
        expect(wrapper.find('[aria-label="Product page navigation"] a').text()).toBe("1");
    });
});
