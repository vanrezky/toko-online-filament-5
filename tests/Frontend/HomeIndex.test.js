import { describe, expect, it } from "vitest";
import { mount } from "@vue/test-utils";
import HomeIndex from "../../resources/js/frontend/pages/Home/Index.vue";

const sectionStub = (name) => ({
    template: `<section data-home-section="${name}"></section>`,
});

const mountHome = (props = {}) =>
    mount(HomeIndex, {
        props: {
            products: { data: [{ id: "product-1", name: "Demo Product", slug: "demo-product" }] },
            categories: [{ id: "category-1", slug: "fashion", name: "Fashion" }],
            filters: {},
            template: null,
            sliders: { data: [] },
            flashsales: null,
            ...props,
        },
        global: {
            stubs: {
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

describe("Homepage composition", () => {
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
