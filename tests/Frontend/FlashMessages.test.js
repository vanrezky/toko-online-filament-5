import { nextTick, reactive } from "vue";
import { describe, expect, it, vi } from "vitest";
import { mount } from "@vue/test-utils";
import { usePage } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import FlashMessages from "../../resources/js/frontend/components/FlashMessages.vue";

vi.mock("vue-sonner", () => ({
    toast: {
        success: vi.fn(),
        error: vi.fn(),
        warning: vi.fn(),
        info: vi.fn(),
    },
}));

describe("FlashMessages", () => {
    it("does not repeat a toast when Inertia replaces flash with the same values", async () => {
        const props = reactive({
            flash: { success: "Added to cart", error: null, warning: null, info: null },
        });
        vi.mocked(usePage).mockReturnValue({ props });

        mount(FlashMessages);
        expect(toast.success).toHaveBeenCalledTimes(1);

        props.flash = { ...props.flash };
        await nextTick();

        expect(toast.success).toHaveBeenCalledTimes(1);
    });
});
