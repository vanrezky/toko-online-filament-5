import { mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import ProductFilters from "../../resources/js/frontend/components/UI/ProductFilters.vue";

const mountFilters = (modelValue = {}) =>
    mount(ProductFilters, {
        props: {
            modelValue: {
                categories: [],
                price_min: "",
                price_max: "",
                rating_min: "",
                promos: [],
            variant_color: [],
            variant_size: [],
            variant_gender: [],
            ...modelValue,
        },
        categories: [{ id: 1, slug: "fashion", name: "Fashion" }],
    },
});

describe("ProductFilters", () => {
    it("renders variant options and emits the selected variant filter", async () => {
        const wrapper = mountFilters();
        const sizeOption = wrapper.findAll("label").find((label) => label.text().trim() === "M").find("input");

        await sizeOption.setValue(true);

        expect(wrapper.emitted("update:modelValue")).toBeTruthy();
        expect(wrapper.emitted("update:modelValue").at(-1)[0].variant_size).toEqual(["M"]);
        expect(wrapper.emitted("change")).toHaveLength(1);
    });

    it("collapses each filter section and renders radio controls", async () => {
        const wrapper = mountFilters();
        const categoryHeader = wrapper.find("button[aria-controls='filter-section-categories']");

        expect(categoryHeader.attributes("aria-expanded")).toBe("true");
        expect(wrapper.findAll("input[type='checkbox']")).toHaveLength(0);
        expect(wrapper.findAll("input[type='radio']").length).toBeGreaterThan(0);

        await categoryHeader.trigger("click");

        expect(categoryHeader.attributes("aria-expanded")).toBe("false");
        expect(wrapper.find("#filter-section-categories").exists()).toBe(false);
    });

    it("renders supported variant groups in the filter order with color swatches", () => {
        const wrapper = mount(ProductFilters, {
            props: {
                modelValue: {
                    categories: [],
                    price_min: "",
                    price_max: "",
                    rating_min: "",
                    promos: [],
                    variant_color: [],
                    variant_size: [],
                    variant_gender: [],
                },
            },
        });

        expect(wrapper.findAll("section h3").map((heading) => heading.text())).toEqual([
            "Category",
            "Price",
            "Minimum Rating",
            "Color",
            "Size",
            "Gender",
            "Promotions",
        ]);
        expect(wrapper.find("label[title='Hitam'] span.h-6.w-6").exists()).toBe(true);
    });
});
