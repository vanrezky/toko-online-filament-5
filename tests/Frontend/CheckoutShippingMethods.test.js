import { describe, expect, it } from "vitest";
import { createLatestRequestGate, getCourierLogo, reconcileShippingMethods } from "../../resources/js/frontend/lib/shippingMethods";

const option = (courierCode, price, estimation = "1-2 days") => ({
    courier_code: courierCode,
    courier_name: courierCode,
    price,
    estimation,
});

describe("checkout courier logos", () => {
    it("resolves known courier assets and falls back for unknown codes", () => {
        expect(getCourierLogo("JNE")).toBe("/assets/images/courier/jne.webp");
        expect(getCourierLogo("saplite")).toBe("/assets/images/courier/sap.png");
        expect(getCourierLogo("PICKUP")).toBeNull();
        expect(getCourierLogo("unknown")).toBeNull();
    });
});

describe("checkout shipping method reconciliation", () => {
    it("preserves an available courier and refreshes its shipping details", () => {
        const { methods, hasFallback } = reconcileShippingMethods(
            {
                1: { ...option("JNE", 10_000), weight: 100 },
            },
            [
                {
                    warehouse_id: 1,
                    weight: 250,
                    options: [option("POS", 12_000), option("JNE", 15_000, "2-3 days")],
                },
            ],
        );

        expect(methods).toEqual({
            1: { ...option("JNE", 15_000, "2-3 days"), weight: 250 },
        });
        expect(hasFallback).toBe(false);
    });

    it("falls back only when the selected courier is unavailable", () => {
        const { methods, hasFallback } = reconcileShippingMethods(
            {
                1: { ...option("JNE", 10_000), weight: 100 },
            },
            [
                {
                    warehouse_id: 1,
                    weight: 250,
                    options: [option("POS", 12_000)],
                },
            ],
        );

        expect(methods).toEqual({
            1: { ...option("POS", 12_000), weight: 250 },
        });
        expect(hasFallback).toBe(true);
    });

    it("reconciles each warehouse independently and drops stale selections", () => {
        const { methods, hasFallback } = reconcileShippingMethods(
            {
                1: { ...option("JNE", 10_000), weight: 100 },
                2: { ...option("TIKI", 20_000), weight: 100 },
                3: { ...option("STALE", 30_000), weight: 100 },
            },
            [
                {
                    warehouse_id: 1,
                    weight: 250,
                    options: [option("JNE", 15_000)],
                },
                {
                    warehouse_id: 2,
                    weight: 500,
                    options: [option("POS", 18_000)],
                },
            ],
        );

        expect(methods).toEqual({
            1: { ...option("JNE", 15_000), weight: 250 },
            2: { ...option("POS", 18_000), weight: 500 },
        });
        expect(methods[3]).toBeUndefined();
        expect(hasFallback).toBe(true);
    });

    it("accepts only the newest shipping-cost request", () => {
        const gate = createLatestRequestGate();
        const firstRequest = gate.start();
        const secondRequest = gate.start();

        expect(gate.isLatest(firstRequest)).toBe(false);
        expect(gate.isLatest(secondRequest)).toBe(true);
    });
});
