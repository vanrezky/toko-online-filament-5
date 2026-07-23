export const ORDER_STATUSES = ["packed", "in_transit", "shipped", "picked_up", "delivered", "completed", "cancelled"];

export const ORDER_STATUS_FILTERS = ["all", ...ORDER_STATUSES];

const ORDER_STATUS_CONFIG = {
    packed: {
        labelKey: "labels.order.status.packed",
        colorClass: "text-muted-foreground bg-secondary border-border",
    },
    in_transit: {
        labelKey: "labels.order.status.in_transit",
        colorClass: "text-primary bg-primary/10 border-primary/20",
    },
    shipped: {
        labelKey: "labels.order.status.shipped",
        colorClass: "text-primary bg-primary/10 border-primary/20",
    },
    picked_up: {
        labelKey: "labels.order.status.picked_up",
        colorClass: "text-accent-foreground bg-accent/10 border-accent/30",
    },
    delivered: {
        labelKey: "labels.order.status.delivered",
        colorClass: "text-primary bg-primary/10 border-primary/20",
    },
    completed: {
        labelKey: "labels.order.status.completed",
        colorClass: "text-primary bg-primary/10 border-primary/20",
    },
    cancelled: {
        labelKey: "labels.order.status.cancelled",
        colorClass: "text-destructive bg-destructive/10 border-destructive/20",
    },
};

export function getOrderStatusLabel(status, t) {
    const labelKey = status === "all" ? "labels.filters.all" : ORDER_STATUS_CONFIG[status]?.labelKey;

    return labelKey ? t(labelKey) : status;
}

export function getOrderStatusColor(status, fallbackClass = "bg-secondary text-muted-foreground") {
    return ORDER_STATUS_CONFIG[status]?.colorClass || fallbackClass;
}
