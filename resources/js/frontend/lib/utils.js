import { clsx } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs) {
    return twMerge(clsx(inputs));
}

export function getSectionContent(template, sectionType, key, defaultValue = "") {
    const section = template?.sections?.find((item) => item.type === sectionType);

    return section?.contents?.[key] || defaultValue;
}

export function valueUpdater(updaterOrValue, ref) {
    ref.value = typeof updaterOrValue === "function" ? updaterOrValue(ref.value) : updaterOrValue;
}

export function assets(pathfile) {
    return `${import.meta.env.BASE_URL}${pathfile}`;
}

export function formatCurrency(amount, locale = "id-ID", currency = "IDR") {
    return new Intl.NumberFormat(locale, {
        style: "currency",
        currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount || 0);
}

export function formatPrice(amount) {
    return formatCurrency(amount).replace("Rp", "").trim();
}

export function formatDate(date, options = {}, locale = "id-ID") {
    if (!date) return "-";

    return new Date(date).toLocaleDateString(locale, options);
}

export function formatTime(date, options = {}, locale = "id-ID") {
    if (!date) return "-";

    return new Date(date).toLocaleTimeString(locale, options);
}

export function formatPhone(phone) {
    return phone ? String(phone).replace(/\s+/g, " ").trim() : "";
}

export function formatCompactNumber(value, language = "id") {
    const number = Number(value) || 0;
    const locale = language === "id" ? "id-ID" : "en-US";

    if (number < 1000) {
        return new Intl.NumberFormat(locale).format(number);
    }

    const compact = new Intl.NumberFormat(locale, {
        notation: "compact",
        compactDisplay: "short",
        maximumFractionDigits: 0,
    })
        .format(number)
        .replace(/\s/g, "")
        .toLocaleUpperCase(locale);

    return `${compact}+`;
}
