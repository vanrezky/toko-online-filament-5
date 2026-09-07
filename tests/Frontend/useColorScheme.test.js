import { describe, expect, it } from "vitest";
import { normalizeColorScheme, rgbToHsl } from "../../resources/js/frontend/composables/useColorScheme.js";

describe("template color scheme contract", () => {
    it("maps admin text to storefront foreground and preserves five configured colors", () => {
        expect(normalizeColorScheme({
            primary: "#112233",
            secondary: "#223344",
            accent: "#334455",
            background: "#FDFDFD",
            text: "#101010",
        })).toMatchObject({
            primary: "#112233",
            secondary: "#223344",
            accent: "#334455",
            background: "#FDFDFD",
            foreground: "#101010",
        });
    });

    it("falls back for invalid values and emits HSL channels for Tailwind", () => {
        const colors = normalizeColorScheme({ primary: "not-a-color" });

        expect(colors.primary).toBe("#F97316");
        expect(colors.foreground).toBe("#2D1B0E");
        expect(rgbToHsl({ r: 255, g: 255, b: 255 })).toBe("0 0% 100%");
    });

    it("uses the safe theme when the template color payload is missing", () => {
        expect(normalizeColorScheme()).toEqual({
            primary: "#F97316",
            secondary: "#F5F3FC",
            accent: "#FB923C",
            destructive: "#F43F5E",
            background: "#FCFCFE",
            foreground: "#2D1B0E",
        });
    });
});
