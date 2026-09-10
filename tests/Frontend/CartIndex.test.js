import { nextTick, reactive } from "vue";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { mount } from "@vue/test-utils";
import { router } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import CartIndex from "../../resources/js/frontend/pages/Cart/Index.vue";

vi.mock("vue-sonner", () => ({ toast: { error: vi.fn() } }));
const items = [
    {
        id: "cart-a",
        price: 149000,
        original_price: 199000,
        quantity: 1,
        available_stock: 3,
        product: { slug: "linen-shirt", name: "Linen Shirt", stock: 10, thumbnail: "/shirt.jpg" },
        product_variant: { variant_name: "Cream / M" },
    },
    { id: "cart-b", price: 279000, original_price: 279000, quantity: 2, available_stock: 5, product: { slug: "backpack", name: "Backpack", stock: 5 } },
];
const recommendations = [
    {
        id: "product-c",
        name: "Canvas Shoes",
        slug: "canvas-shoes",
        price: 349000,
        thumbnail: "/canvas-shoes.jpg",
        rating_average: 4.8,
        review_count: 1250,
        pricing: { original_price: 399000, final_price: 349000 },
    },
];
let wrapper;
let confirm;
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

const render = (cartItems = items, recommendedProducts = recommendations, { recommendationsPending = false } = {}) => {
    currentPageProps = reactive({ recommendations: recommendationsPending ? undefined : recommendedProducts });
    wrapper = mount(CartIndex, {
        props: { cart: { items: structuredClone(cartItems) }, recommendations: structuredClone(recommendedProducts) },
        global: {
            stubs: { Deferred: DeferredStub, TemplateWrapper: { template: "<div><slot /></div>" } },
            config: { globalProperties: { $confirm: confirm } },
        },
    });
    return wrapper;
};
const select = (index) => wrapper.findAll('.cart-item input[type="checkbox"]').at(index);
const checkout = () => wrapper.get('[data-test="cart-checkout"]');
const plus = (index) => wrapper.findAll(".cart-quantity button:last-child").at(index);

beforeEach(() => {
    vi.useFakeTimers();
    confirm = vi.fn();
    global.route.mockReset();
    global.route.mockImplementation((name, params) => `/${name}${typeof params === "string" ? `/${params}` : ""}`);
    vi.spyOn(router, "visit").mockImplementation(() => {});
    vi.spyOn(router, "patch").mockImplementation(() => {});
    vi.spyOn(router, "delete").mockImplementation(() => {});
    vi.spyOn(router, "post").mockImplementation(() => {});
});
afterEach(() => {
    wrapper?.unmount();
    vi.restoreAllMocks();
    vi.useRealTimers();
});

describe("Cart mockup presentation preserves cart behavior", () => {
    it("keeps the cart shell usable while recommendations load", () => {
        render(items, recommendations, { recommendationsPending: true });

        const fallback = wrapper.get('[role="status"]');

        expect(fallback.attributes("aria-live")).toBe("polite");
        expect(fallback.text()).toContain("Loading...");
        expect(wrapper.find(".cart-recommendations").exists()).toBe(false);
        expect(wrapper.find(".cart-summary").exists()).toBe(true);
        expect(checkout().exists()).toBe(true);
    });

    it("replaces the recommendation fallback after the deferred prop resolves", async () => {
        render(items, recommendations, { recommendationsPending: true });

        currentPageProps.recommendations = recommendations;
        await nextTick();

        expect(wrapper.find('[role="status"]').exists()).toBe(false);
        expect(wrapper.find(".cart-recommendations").exists()).toBe(true);
        expect(wrapper.text()).toContain("Canvas Shoes");
    });

    it("omits checkout-only sections while retaining the shopping summary", () => {
        render();
        expect(wrapper.find(".cart-voucher").exists()).toBe(false);
        expect(wrapper.find(".cart-methods").exists()).toBe(false);
        expect(wrapper.find(".cart-security").exists()).toBe(false);
        expect(wrapper.text()).not.toContain("Voucher Discount");
        expect(wrapper.get(".cart-summary").text()).toContain("Calculated at checkout");
        expect(checkout().exists()).toBe(true);
    });

    it("uses real items and subtotal and submits all selected IDs through one checkout action", async () => {
        render();
        expect(wrapper.findAll(".cart-item")).toHaveLength(2);
        expect(wrapper.text()).toContain("Cream / M");
        expect(wrapper.findAll(".cart-variant")).toHaveLength(1);
        expect(wrapper.findAll(".cart-item")[1].find(".cart-variant").exists()).toBe(false);
        expect(wrapper.get(".cart-variant").element.tagName).toBe("SPAN");
        expect(wrapper.get(".cart-variant").find("svg").exists()).toBe(false);
        const recommendation = wrapper.get('.cart-recommendations a.product-recommendation-name[href="/frontend.product-detail/canvas-shoes"]');
        expect(recommendation.text()).toContain("Canvas Shoes");
        expect(recommendation.attributes("href")).toBe("/frontend.product-detail/canvas-shoes");
        expect(wrapper.find(".cart-recommendation-shop").exists()).toBe(false);
        expect(wrapper.find(".cart-recommendations .product-recommendation-rating").exists()).toBe(false);
        expect(wrapper.find(".cart-recommendations .product-recommendation-save").exists()).toBe(false);
        expect(wrapper.get(".cart-recommendation-grid").classes()).toEqual(expect.arrayContaining(["flex", "overflow-x-auto", "lg:grid-cols-4"]));
        expect(wrapper.text()).not.toContain("TWS Wireless Bluetooth");
        expect(wrapper.get('[data-test="cart-subtotal"]').text()).toMatch(/707[,.]000/);
        expect(wrapper.findAll('[data-test="cart-checkout"]')).toHaveLength(1);
        await checkout().trigger("click");
        expect(global.route).toHaveBeenCalledWith("frontend.checkout", { cart_item_ids: ["cart-a", "cart-b"] });
        expect(router.visit).toHaveBeenCalledWith("/frontend.checkout");
    });
    it("recalculates a subset and prevents navigation when nothing is selected", async () => {
        render();
        await select(1).setValue(false);
        expect(wrapper.get('[data-test="cart-subtotal"]').text()).toMatch(/149[,.]000/);
        expect(wrapper.get(".cart-select-all input").element.indeterminate).toBe(true);
        await checkout().trigger("click");
        expect(global.route).toHaveBeenCalledWith("frontend.checkout", { cart_item_ids: ["cart-a"] });
        router.visit.mockClear();
        await select(0).setValue(false);
        expect(checkout().element.disabled).toBe(true);
        await checkout().trigger("click");
        expect(router.visit).not.toHaveBeenCalled();
        await wrapper.get(".cart-select-all input").setValue(true);
        expect(select(0).element.checked && select(1).element.checked).toBe(true);
    });
    it("coalesces clicks, preserves the minimum, and blocks checkout until quantity is saved", async () => {
        render();
        expect(wrapper.find(".cart-quantity button").element.disabled).toBe(true);
        await plus(0).trigger("click");
        await plus(0).trigger("click");
        expect(checkout().element.disabled).toBe(true);
        expect(items[0].quantity).toBe(1);
        await vi.advanceTimersByTimeAsync(300);
        expect(router.patch).toHaveBeenCalledTimes(1);
        expect(router.patch).toHaveBeenCalledWith("/frontend.cart.update/cart-a", { quantity: 3 }, expect.objectContaining({ preserveScroll: true }));
        await wrapper.setProps({ cart: { items: [{ ...items[0], quantity: 3 }, items[1]] } });
        router.patch.mock.calls[0][2].onFinish();
        await wrapper.vm.$nextTick();
        expect(checkout().element.disabled).toBe(false);
    });
    it("serializes updates for different rows and preserves the second optimistic value across a refresh", async () => {
        render();
        await plus(0).trigger("click");
        await plus(1).trigger("click");
        await vi.advanceTimersByTimeAsync(300);
        expect(router.patch).toHaveBeenCalledTimes(1);
        await wrapper.setProps({ cart: { items: [{ ...items[0], quantity: 2 }, items[1]] } });
        expect(wrapper.findAll(".cart-quantity output")[1].text()).toBe("3");
        router.patch.mock.calls[0][2].onFinish();
        expect(router.patch).toHaveBeenCalledTimes(2);
        expect(router.patch.mock.calls[1].slice(0, 2)).toEqual(["/frontend.cart.update/cart-b", { quantity: 3 }]);
    });
    it("restores the server quantity and exposes an error when an update fails", async () => {
        render();
        await plus(0).trigger("click");
        await vi.advanceTimersByTimeAsync(300);
        router.patch.mock.calls[0][2].onError();
        router.patch.mock.calls[0][2].onFinish();
        await wrapper.vm.$nextTick();
        expect(wrapper.find(".cart-quantity output").text()).toBe("1");
        expect(toast.error).toHaveBeenCalled();
    });
    it("uses the existing delete endpoint and reconciles selection after deletion", async () => {
        render();
        await wrapper.find(".cart-delete").trigger("click");
        expect(router.delete).toHaveBeenCalledWith("/frontend.cart.destroy/cart-a", expect.objectContaining({ preserveScroll: true }));
        await wrapper.setProps({ cart: { items: [items[1]] } });
        router.delete.mock.calls[0][1].onFinish();
        await wrapper.vm.$nextTick();
        expect(wrapper.findAll(".cart-item")).toHaveLength(1);
        await checkout().trigger("click");
        expect(global.route).toHaveBeenCalledWith("frontend.checkout", { cart_item_ids: ["cart-b"] });
    });
    it("requires confirmation to clear all and stops sequential deletion on failure", async () => {
        render();
        await wrapper.get(".cart-remove-all").trigger("click");
        confirm.mock.calls[0][0].callback(false);
        expect(router.delete).not.toHaveBeenCalled();
        confirm.mock.calls[0][0].callback(true);
        expect(router.delete).toHaveBeenCalledTimes(1);
        router.delete.mock.calls[0][1].onError();
        router.delete.mock.calls[0][1].onFinish();
        expect(router.delete).toHaveBeenCalledTimes(1);
    });
    it("keeps cart item saved state local without changing totals or checkout payload", async () => {
        render();
        const before = wrapper.get('[data-test="cart-subtotal"]').text();
        await wrapper.find(".cart-save").trigger("click");
        expect(wrapper.find(".cart-save").attributes("aria-pressed")).toBe("true");
        expect(wrapper.get('[data-test="cart-subtotal"]').text()).toBe(before);
        expect(router.post).not.toHaveBeenCalled();
        expect(router.patch).not.toHaveBeenCalled();
        await checkout().trigger("click");
        expect(global.route).toHaveBeenLastCalledWith("frontend.checkout", { cart_item_ids: ["cart-a", "cart-b"] });
    });
    it("shows an empty state after removing the last item without adding fixture items", async () => {
        render();
        await wrapper.setProps({ cart: { items: [] } });
        expect(wrapper.find(".cart-empty").exists()).toBe(true);
        expect(wrapper.find(".cart-empty a").attributes("href")).toBe("/frontend.products");
        expect(wrapper.find('[data-test="cart-checkout"]').exists()).toBe(false);
        expect(wrapper.find(".cart-recommendations").exists()).toBe(false);
    });
    it("renders Indonesian and English cart copy without missing keys", async () => {
        render();
        expect(wrapper.get("h1").text()).toBe("My Cart");
        wrapper.vm.$i18n.locale = "id";
        await wrapper.vm.$nextTick();
        expect(wrapper.get("h1").text()).toBe("Keranjang Saya");
        expect(checkout().text()).toContain("Lanjut ke Checkout");
        expect(wrapper.text()).not.toContain("labels.cart");
        wrapper.vm.$i18n.locale = "en";
        await wrapper.vm.$nextTick();
        expect(checkout().text()).toContain("Continue to Checkout");
        expect(wrapper.text()).not.toContain("labels.cart");
    });
    it("uses each cart line's effective stock as its quantity maximum", async () => {
        render([
            { ...items[0], quantity: 1, available_stock: 2 },
            { ...items[1], quantity: 1, available_stock: 1 },
        ]);

        const quantityButtons = wrapper.findAll(".cart-quantity button");
        await quantityButtons[1].trigger("click");
        await quantityButtons[3].trigger("click");

        expect(wrapper.findAll(".cart-quantity output")[0].text()).toBe("2");
        expect(wrapper.findAll(".cart-quantity output")[1].text()).toBe("1");
    });

    it("clears scheduled quantity updates when leaving the page", async () => {
        render();
        await plus(0).trigger("click");
        wrapper.unmount();
        await vi.advanceTimersByTimeAsync(500);
        expect(router.patch).not.toHaveBeenCalled();
    });
});
