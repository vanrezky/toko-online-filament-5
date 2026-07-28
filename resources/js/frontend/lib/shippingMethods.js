export function reconcileShippingMethods(currentMethods, shippingResults) {
    const methods = {};
    let hasFallback = false;

    shippingResults.forEach((warehouse) => {
        if (!warehouse.options?.length) {
            return;
        }

        const previousMethod = currentMethods?.[warehouse.warehouse_id];
        const selectedOption = warehouse.options.find(
            (option) => option.courier_code === previousMethod?.courier_code,
        );
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
