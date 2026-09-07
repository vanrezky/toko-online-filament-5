import { beforeEach, describe, expect, it } from "vitest";
import { mount } from "@vue/test-utils";
import CategoryMenu from "../../resources/js/frontend/components/UI/CategoryMenu.vue";

describe("Homepage category menu", () => {
    beforeEach(() => {
        global.route.mockReset();
        global.route.mockImplementation((name, params) => (params?.category ? `/${name}?category=${params.category}` : `/${name}`));
    });

    it("keeps all-category and category links filterable", () => {
        const wrapper = mount(CategoryMenu, {
            props: {
                activeCategory: "fashion",
                categories: [
                    { id: "fashion", slug: "fashion", name: "Fashion" },
                    { id: "home", slug: "home", name: "Home" },
                ],
            },
        });

        expect(wrapper.find('a[href="/frontend.home"]').exists()).toBe(true);
        expect(wrapper.find('a[href="/frontend.home?category=fashion"]').exists()).toBe(true);
        expect(wrapper.find('a[href="/frontend.home?category=home"]').exists()).toBe(true);
        expect(wrapper.find('a[href="/frontend.home?category=fashion"]').classes()).toContain("bg-primary");
    });

    it("provides a horizontal mobile rail with focusable category tiles", () => {
        const wrapper = mount(CategoryMenu, {
            props: { categories: [{ id: "fashion", slug: "fashion", name: "Fashion" }] },
        });

        expect(wrapper.find(".overflow-x-auto").exists()).toBe(true);
        expect(wrapper.findAll("a")).toHaveLength(3);
        expect(wrapper.findAll("a").every((link) => link.classes().includes("focus-visible:ring-2"))).toBe(true);
    });
});
