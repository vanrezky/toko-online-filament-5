import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import NewsletterSection from "../../resources/js/frontend/components/UI/NewsletterSection.vue";

describe("Homepage Newsletter Form", () => {
    it("renders newsletter form", () => {
        const wrapper = mount(NewsletterSection);
        const control = wrapper.find("[data-newsletter-control]");

        expect(control.find('input[type="email"]').exists()).toBe(true);
        expect(control.find('button[type="submit"]').exists()).toBe(true);
    });

    it("has email input with correct placeholder", () => {
        const wrapper = mount(NewsletterSection);
        const input = wrapper.find('input[type="email"]');

        expect(input.attributes("placeholder")).toBe("Enter your email address");
    });

    it("can type email into input", async () => {
        const wrapper = mount(NewsletterSection);
        const input = wrapper.find('input[type="email"]');

        await input.setValue("test@example.com");

        expect(input.element.value).toBe("test@example.com");
    });

    it("uses the configured newsletter content from the active template", () => {
        const wrapper = mount(NewsletterSection, {
            props: {
                template: {
                    sections: [{
                        type: "newsletter",
                        contents: {
                            title: "Dapatkan Penawaran Spesial",
                            subtitle: "Konten dari admin template",
                            button_text: "Berlangganan",
                            placeholder: "Masukkan email Anda",
                        },
                    }],
                },
            },
        });

        expect(wrapper.find("#newsletter-title").text()).toBe("Dapatkan Penawaran Spesial");
        expect(wrapper.find("p.text-muted-foreground").text()).toBe("Konten dari admin template");
        expect(wrapper.find('input[type="email"]').attributes("placeholder")).toBe("Masukkan email Anda");
        expect(wrapper.find('button[type="submit"]').text()).toContain("Berlangganan");
    });
});
