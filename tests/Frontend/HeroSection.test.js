import { mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import HeroSection from "../../resources/js/frontend/components/UI/HeroSection.vue";

describe("HeroSection", () => {
    it("keeps template content and links reactive and uses the generated image as a fallback", async () => {
        const contents = {
            title: "Pilihan untuk rumah Anda",
            subtitle: "Koleksi pilihan minggu ini.",
            eyebrow: "Koleksi terbaru",
            image_url: "/custom-hero.webp",
            button_text: "Jelajahi koleksi",
            button_link: "/products?category=rumah",
            secondary_text: "Promo pekan ini",
            secondary_link: "/flash-sale",
            badge: "Spesial",
            promo_value: "35%",
            trust_points: JSON.stringify([{ title: "Layanan khusus", description: "Hubungi tim kami." }]),
        };
        const template = (content) => ({ sections: [{ type: "hero", contents: content }] });
        const wrapper = mount(HeroSection, { props: { template: template(contents) } });

        expect(wrapper.get("h1").text()).toBe(contents.title);
        expect(wrapper.text()).toContain(contents.subtitle);
        expect(wrapper.text()).toContain(contents.eyebrow);
        expect(wrapper.get("img").attributes("src")).toBe(contents.image_url);
        expect(wrapper.findAll("a").map((link) => [link.text(), link.attributes("href")])).toEqual([
            [contents.button_text, contents.button_link],
            [contents.secondary_text, contents.secondary_link],
        ]);
        expect(wrapper.get(".hero-promo").text()).toContain("Spesial");
        expect(wrapper.get(".hero-promo").text()).toContain("35%");
        expect(wrapper.get("aside").text()).toContain("Layanan khusus");
        expect(wrapper.get("aside").text()).toContain("Hubungi tim kami.");

        await wrapper.setProps({ template: template({ ...contents, title: "Judul baru", image_url: "" }) });
        expect(wrapper.get("h1").text()).toBe("Judul baru");
        expect(wrapper.get("img").attributes("src")).toBe("/images/hero/bristol-products.webp");
    });
});
