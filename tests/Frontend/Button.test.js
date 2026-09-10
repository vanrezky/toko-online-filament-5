import { mount } from "@vue/test-utils";
import { describe, expect, it, vi } from "vitest";
import { Heart } from "lucide-vue-next";
import Button from "../../resources/js/frontend/components/UI/Button.vue";

describe("Button", () => {
    it("accepts and renders function component icons without a Vue warning", () => {
        const warn = vi.spyOn(console, "warn").mockImplementation(() => {});

        const wrapper = mount(Button, { props: { icon: Heart, size: "icon" } });

        expect(wrapper.find("svg").exists()).toBe(true);
        expect(warn).not.toHaveBeenCalledWith(expect.stringContaining('Invalid prop: type check failed for prop "icon"'));

        warn.mockRestore();
    });
});
