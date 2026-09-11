import { nextTick, reactive } from "vue";
import { describe, expect, it, vi } from "vitest";
import { mount } from "@vue/test-utils";
import { usePage } from "@inertiajs/vue3";
import HomeIndex from "../../resources/js/frontend/pages/Home/Index.vue";

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

const sectionStub = (name) => ({
    template: `<section data-home-section="${name}"></section>`,
});

const mountHome = (props = {}, { unresolved = [] } = {}) => {
    const resolvedProps = {
        products: { data: [{ id: "product-1", name: "Demo Product", slug: "demo-product" }] },
        categories: [{ id: "category-1", slug: "fashion", name: "Fashion" }],
        filters: {},
        template: null,
        sliders: { data: [] },
        flashsales: null,
        ...props,
    };

    currentPageProps = reactive({
        settings: { site_name: "Test Store" },
        ...Object.fromEntries(
            ["products", "categories", "sliders", "flashsales"].map((key) => [key, unresolved.includes(key) ? undefined : resolvedProps[key]]),
        ),
    });
    vi.mocked(usePage).mockReturnValue({ props: currentPageProps });

    return mount(HomeIndex, {
        props: resolvedProps,
        global: {
            stubs: {
                Deferred: DeferredStub,
                TemplateWrapper: { template: "<div data-test=template-wrapper><slot /></div>" },
                HeroSection: sectionStub("hero"),
                FlashSaleSection: sectionStub("flash-sale"),
                CategoryMenu: sectionStub("categories"),
                HeroCarousel: sectionStub("carousel"),
                FeaturedProducts: sectionStub("featured-products"),
                HomeProductsSection: sectionStub("all-products"),
                VoucherSection: sectionStub("vouchers"),
                NewsletterSection: sectionStub("newsletter"),
                HomeTrustStrip: sectionStub("trust"),
            },
        },
    });
};

describe("Homepage composition", () => {
    it("keeps the shell and static sections usable while datasets load", () => {
        const wrapper = mountHome({ flashsales: undefined }, { unresolved: ["products", "categories", "sliders", "flashsales"] });

        expect(wrapper.findAll('[role="status"]')).toHaveLength(5);
        expect(wrapper.find('[data-home-section="hero"]').exists()).toBe(true);
        expect(wrapper.find('[data-home-section="vouchers"]').exists()).toBe(true);
        expect(wrapper.find('[data-home-section="newsletter"]').exists()).toBe(true);
        expect(wrapper.find('[data-home-section="trust"]').exists()).toBe(true);
        expect(wrapper.findAll(".skeleton-shimmer").length).toBeGreaterThan(0);
    });

    it("replaces product fallbacks when the deferred prop resolves", async () => {
        const wrapper = mountHome({ flashsales: undefined }, { unresolved: ["products", "categories", "sliders", "flashsales"] });

        currentPageProps.products = { data: [{ id: "product-1", name: "Demo Product", slug: "demo-product" }] };
        await nextTick();

        expect(wrapper.findAll('[role="status"]')).toHaveLength(3);
        expect(wrapper.find('[data-home-section="featured-products"]').exists()).toBe(true);
        expect(wrapper.find('[data-home-section="all-products"]').exists()).toBe(true);
    });

    it("keeps the reference-led section order and commerce sections", () => {
        const wrapper = mountHome({ flashsales: { id: "flash-sale-1" } });
        const sections = wrapper.findAll("[data-home-section]").map((section) => section.attributes("data-home-section"));

        expect(sections).toEqual([
            "hero",
            "flash-sale",
            "categories",
            "carousel",
            "featured-products",
            "all-products",
            "vouchers",
            "newsletter",
            "trust",
        ]);
    });

    it("hides optional flash-sale and featured sections for a filtered category view", () => {
        const wrapper = mountHome({ filters: { category: "fashion" } });
        const sections = wrapper.findAll("[data-home-section]").map((section) => section.attributes("data-home-section"));

        expect(sections).not.toContain("flash-sale");
        expect(sections).not.toContain("featured-products");
        expect(sections).toContain("all-products");
    });

    it("follows active template order and skips inactive or unknown sections", () => {
        const wrapper = mountHome({
            template: {
                sections: [
                    { uuid: "products", type: "products_grid", is_active: true, contents: {} },
                    { uuid: "unknown", type: "unsupported", is_active: true, contents: {} },
                    { uuid: "newsletter", type: "newsletter", is_active: false, contents: {} },
                    { uuid: "hero", type: "hero", is_active: true, contents: {} },
                ],
            },
        });
        const sections = wrapper.findAll("[data-home-section]").map((section) => section.attributes("data-home-section"));

        expect(sections.slice(0, 2)).toEqual(["all-products", "hero"]);
        expect(sections).not.toContain("newsletter");
        expect(sections).not.toContain("unsupported");
    });
});
