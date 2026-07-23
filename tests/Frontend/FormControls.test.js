import { describe, expect, it } from "vitest";
import { mount } from "@vue/test-utils";
import FormCheckbox from "../../resources/js/frontend/components/UI/FormCheckbox.vue";
import FormInput from "../../resources/js/frontend/components/UI/FormInput.vue";
import FormSelect from "../../resources/js/frontend/components/UI/FormSelect.vue";
import FormTextarea from "../../resources/js/frontend/components/UI/FormTextarea.vue";

describe("form controls", () => {
    it("forwards native attributes and merges custom input classes", () => {
        const wrapper = mount(FormInput, {
            props: {
                modelValue: "",
                class: "rounded-none py-2",
            },
            attrs: {
                autocomplete: "email",
                required: true,
                type: "email",
            },
        });

        const input = wrapper.find("input");

        expect(input.attributes("autocomplete")).toBe("email");
        expect(input.attributes("required")).toBeDefined();
        expect(input.classes()).toContain("rounded-none");
        expect(input.classes()).toContain("py-2");
    });

    it("emits model updates for text, textarea, select, and checkbox controls", async () => {
        const input = mount(FormInput, { props: { modelValue: "" } });
        const textarea = mount(FormTextarea, { props: { modelValue: "" } });
        const select = mount(FormSelect, {
            props: { modelValue: "" },
            slots: { default: '<option value="newest">Newest</option>' },
        });
        const checkbox = mount(FormCheckbox, { props: { modelValue: false } });

        await input.find("input").setValue("test@example.com");
        await textarea.find("textarea").setValue("Message");
        await select.find("select").setValue("newest");
        await checkbox.find("input").setValue(true);

        expect(input.emitted("update:modelValue")[0]).toEqual(["test@example.com"]);
        expect(textarea.emitted("update:modelValue")[0]).toEqual(["Message"]);
        expect(select.emitted("update:modelValue")[0]).toEqual(["newest"]);
        expect(checkbox.emitted("update:modelValue")[0]).toEqual([true]);
    });
});
