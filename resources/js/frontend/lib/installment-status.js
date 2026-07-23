const INSTALLMENT_STATUS_CONFIG = {
    active: { labelKey: "labels.installment.status.active", colorClass: "bg-yellow-100 text-yellow-800" },
    completed: { labelKey: "labels.installment.status.completed", colorClass: "bg-green-100 text-green-800" },
    overdue: { labelKey: "labels.installment.status.overdue", colorClass: "bg-red-100 text-red-800" },
    defaulted: { labelKey: "labels.installment.status.defaulted", colorClass: "bg-gray-100 text-gray-800" },
    pending: { labelKey: "labels.installment.status.pending", colorClass: "bg-yellow-100 text-yellow-800" },
    submitted: { labelKey: "labels.installment.status.submitted", colorClass: "bg-blue-100 text-blue-800" },
    failed: { labelKey: "labels.installment.status.failed", colorClass: "bg-red-100 text-red-800" },
    unpaid: { labelKey: "labels.installment.status.unpaid", colorClass: "bg-yellow-100 text-yellow-800" },
    partial: { labelKey: "labels.installment.status.partial", colorClass: "bg-blue-100 text-blue-800" },
    paid: { labelKey: "labels.installment.status.paid", colorClass: "bg-green-100 text-green-800" },
    cancelled: { labelKey: "labels.installment.status.cancelled", colorClass: "bg-gray-100 text-gray-800" },
};

export function getInstallmentStatusColor(status, fallbackClass = "bg-gray-100 text-gray-800") {
    return INSTALLMENT_STATUS_CONFIG[status]?.colorClass || fallbackClass;
}

export function getInstallmentStatusLabel(status, t) {
    const labelKey = INSTALLMENT_STATUS_CONFIG[status]?.labelKey;

    return labelKey ? t(labelKey) : status;
}
