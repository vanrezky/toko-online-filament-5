import { flushPromises, mount } from "@vue/test-utils";
import { describe, expect, it, vi } from "vitest";
import VoucherSection from "../../resources/js/frontend/components/UI/VoucherSection.vue";
import VoucherCard from "../../resources/js/frontend/components/UI/VoucherCard.vue";

vi.mock("../../resources/js/frontend/services/voucherService", () => ({
    default: {
        getVouchers: vi.fn().mockResolvedValue({
            data: [{
                id: "voucher-1",
                code: "PROMO1",
                formatted_discount: "Rp 1.000",
                name: "Voucher Hemat",
                description: "Deskripsi voucher yang tidak perlu mengambil ruang pada layar kecil.",
                min_purchase_formatted: "Tanpa minimum",
                remaining_days: 22,
                remaining_hours: 0,
                is_expiring_soon: false,
                usage_count: 0,
                is_fully_used: false,
                is_shipping: false,
                image: null,
            }],
        }),
    },
}));

describe("Homepage voucher section", () => {
    it("uses compact voucher cards in a responsive grid", async () => {
        const wrapper = mount(VoucherSection);
        await flushPromises();

        const voucherGrid = wrapper.findAll("div").find((element) => element.classes().includes("lg:grid-cols-4"));

        expect(voucherGrid).toBeDefined();
        expect(voucherGrid.classes()).toContain("grid-cols-1");
        expect(voucherGrid.classes()).toContain("sm:grid-cols-2");
        expect(wrapper.findComponent(VoucherCard).props("variant")).toBe("compact");
    });
});
