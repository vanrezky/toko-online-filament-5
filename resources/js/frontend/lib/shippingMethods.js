const courierLogos = Object.freeze({
    ANTERAJA: "/assets/images/courier/anteraja.webp",
    IDEXPRESS: "/assets/images/courier/idexpress.webp",
    IDLITE: "/assets/images/courier/idexpress.webp",
    JNE: "/assets/images/courier/jne.webp",
    JNECARGO: "/assets/images/courier/jne.webp",
    JT: "/assets/images/courier/jnt.webp",
    "J&T": "/assets/images/courier/jnt.webp",
    KURIRTOKO: "/assets/images/courier/toko.webp",
    LION: "/assets/images/courier/lion.webp",
    NINJA: "/assets/images/courier/ninja.png",
    PAXEL: "/assets/images/courier/paxel.webp",
    POS: "/assets/images/courier/pos.png",
    SAP: "/assets/images/courier/sap.png",
    SAPCARGO: "/assets/images/courier/sap.png",
    SAPLITE: "/assets/images/courier/sap.png",
    SICEPAT: "/assets/images/courier/sicepat.webp",
    TIKI: "/assets/images/courier/tiki.webp",
    WAHANA: "/assets/images/courier/wahana.png",
});

export function getCourierLogo(courierCode) {
    return (
        courierLogos[
            String(courierCode || "")
                .trim()
                .toUpperCase()
        ] || null
    );
}

export function reconcileShippingMethods(currentMethods, shippingResults) {
    const methods = {};
    let hasFallback = false;

    shippingResults.forEach((warehouse) => {
        if (!warehouse.options?.length) {
            return;
        }

        const previousMethod = currentMethods?.[warehouse.warehouse_id];
        const selectedOption = warehouse.options.find((option) => option.courier_code === previousMethod?.courier_code);
        const method = selectedOption || warehouse.options[0];

        methods[warehouse.warehouse_id] = {
            ...method,
            weight: warehouse.weight,
        };

        if (previousMethod && !selectedOption) {
            hasFallback = true;
        }
    });

    return { methods, hasFallback };
}

export function createLatestRequestGate() {
    let latestRequestId = 0;

    return {
        start() {
            latestRequestId += 1;

            return latestRequestId;
        },
        isLatest(requestId) {
            return requestId === latestRequestId;
        },
    };
}
