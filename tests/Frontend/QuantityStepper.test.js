import { describe, expect, it } from "vitest";
import { mount } from "@vue/test-utils";
import QuantityStepper from "../../resources/js/frontend/components/UI/QuantityStepper.vue";

const render = (props = {}) =>
    mount(QuantityStepper, {
        props: {
            modelValue: 2,
            min: 1,
            max: 3,
            ...props,
        },
    });

describe("QuantityStepper", () => {
    it("renders the value and emits the next value when a control is clicked", async () => {
        const wrapper = render();
        const buttons = wrapper.findAll("button");

        expect(wrapper.get("output").text()).toBe("2");

        await buttons[0].trigger("click");
        await buttons[1].trigger("click");

        expect(wrapper.emitted("update:modelValue")).toEqual([[1], [3]]);
        expect(wrapper.emitted("change")).toEqual([[1], [3]]);
    });

    it("disables controls at the configured boundaries and when disabled", async () => {
        const wrapper = render({ modelValue: 1, max: 2 });
        let buttons = wrapper.findAll("button");

        expect(buttons[0].element.disabled).toBe(true);
        expect(buttons[1].element.disabled).toBe(false);

        await wrapper.setProps({ modelValue: 2 });
        buttons = wrapper.findAll("button");
        expect(buttons[0].element.disabled).toBe(false);
        expect(buttons[1].element.disabled).toBe(true);

        await wrapper.setProps({ disabled: true });
        buttons = wrapper.findAll("button");
        expect(buttons.every((button) => button.element.disabled)).toBe(true);
    });
});
