import { describe, expect, it } from "vitest";
import { mount } from "@vue/test-utils";
import Register from "../../resources/js/frontend/pages/Auth/Register.vue";
import en from "../../resources/js/locales/en.json";
import id from "../../resources/js/locales/id.json";

describe("Register legal links and wallet copy", () => {
    it("opens both legal links in a safe new tab", () => {
        const wrapper = mount(Register, {
            props: { secure_password: false },
            global: {
                stubs: {
                    PageShellAuth: { template: "<div><slot /></div>" },
                },
            },
        });
        const legalLinks = wrapper.findAll("a").filter((link) => ["Terms & Conditions", "Privacy Policy"].includes(link.text()));

        expect(legalLinks).toHaveLength(2);
        legalLinks.forEach((link) => {
            expect(link.attributes("target")).toBe("_blank");
            expect(link.attributes("rel")).toBe("noopener noreferrer");
        });
    });

    it("keeps wallet terminology aligned in both locales", () => {
        expect(id.labels.account.balance_description).toBe("Saldo dompet yang dapat digunakan untuk membayar penuh.");
        expect(en.account.balance_description).toBe("Wallet balance available for full payments.");
        expect(id.labels.account.menu.balance).toBe("Saldo Dompet");
        expect(en.account.menu.balance).toBe("Wallet Balance");
    });
});
