import { ref, onMounted, watch } from "vue";
import { usePage } from "@inertiajs/vue3";

const DEFAULT_COLOR_SCHEME = {
    primary: "#F97316",
    secondary: "#F5F3FC",
    accent: "#FB923C",
    destructive: "#F43F5E",
    background: "#FCFCFE",
    foreground: "#2D1B0E",
};

const colorScheme = ref({ ...DEFAULT_COLOR_SCHEME });
const applied = ref(false);

const normalizeHex = (value, fallback) => {
    const normalized = typeof value === "string" ? value.trim() : "";

    return /^#(?:[a-f\d]{3}|[a-f\d]{6})$/i.test(normalized) ? normalized : fallback;
};

const normalizeColorScheme = (colors = {}) => ({
    primary: normalizeHex(colors.primary, DEFAULT_COLOR_SCHEME.primary),
    secondary: normalizeHex(colors.secondary, DEFAULT_COLOR_SCHEME.secondary),
    accent: normalizeHex(colors.accent, DEFAULT_COLOR_SCHEME.accent),
    destructive: normalizeHex(colors.destructive, DEFAULT_COLOR_SCHEME.destructive),
    background: normalizeHex(colors.background, DEFAULT_COLOR_SCHEME.background),
    foreground: normalizeHex(colors.foreground || colors.text, DEFAULT_COLOR_SCHEME.foreground),
});

const hexToRgb = (hex) => {
    const normalized = hex.replace("#", "");
    const value = normalized.length === 3
        ? normalized.split("").map((part) => `${part}${part}`).join("")
        : normalized;

    return {
        r: parseInt(value.slice(0, 2), 16),
        g: parseInt(value.slice(2, 4), 16),
        b: parseInt(value.slice(4, 6), 16),
    };
};

const rgbToHsl = ({ r, g, b }) => {
    const red = r / 255;
    const green = g / 255;
    const blue = b / 255;
    const max = Math.max(red, green, blue);
    const min = Math.min(red, green, blue);
    const lightness = (max + min) / 2;

    if (max === min) {
        return `0 0% ${Math.round(lightness * 100)}%`;
    }

    const delta = max - min;
    const saturation = lightness > 0.5 ? delta / (2 - max - min) : delta / (max + min);
    let hue;

    switch (max) {
        case red:
            hue = (green - blue) / delta + (green < blue ? 6 : 0);
            break;
        case green:
            hue = (blue - red) / delta + 2;
            break;
        default:
            hue = (red - green) / delta + 4;
            break;
    }

    return `${Math.round(hue * 60)} ${Math.round(saturation * 100)}% ${Math.round(lightness * 100)}%`;
};

const readableForeground = (hex) => {
    const { r, g, b } = hexToRgb(hex);
    const yiq = (r * 299 + g * 587 + b * 114) / 1000;

    return yiq >= 160 ? "#2D1B0E" : "#FFFFFF";
};

export function useColorScheme() {
    const page = usePage();

    const setColorScheme = (colors) => {
        colorScheme.value = normalizeColorScheme(colors);
        applyColorScheme();
    };

    const applyColorScheme = () => {
        const root = document.documentElement;
        const tokens = ["primary", "secondary", "accent", "destructive", "background", "foreground"];

        tokens.forEach((token) => {
            const value = colorScheme.value[token];
            root.style.setProperty(`--${token}`, rgbToHsl(hexToRgb(value)));
            root.style.setProperty(`--color-${token}`, value);

            if (!["background", "foreground"].includes(token)) {
                root.style.setProperty(`--${token}-foreground`, rgbToHsl(hexToRgb(readableForeground(value))));
            }
        });

        const background = rgbToHsl(hexToRgb(colorScheme.value.background));
        const foreground = rgbToHsl(hexToRgb(colorScheme.value.foreground));
        root.style.setProperty("--card", background);
        root.style.setProperty("--card-foreground", foreground);
        root.style.setProperty("--ring", rgbToHsl(hexToRgb(colorScheme.value.primary)));

        const primaryRGB = hexToRgb(colorScheme.value.primary);
        const secondaryRGB = hexToRgb(colorScheme.value.secondary);
        const foregroundRGB = hexToRgb(colorScheme.value.foreground);
        root.style.setProperty("--color-primary-rgb", `${primaryRGB.r}, ${primaryRGB.g}, ${primaryRGB.b}`);
        root.style.setProperty("--color-secondary-rgb", `${secondaryRGB.r}, ${secondaryRGB.g}, ${secondaryRGB.b}`);
        root.style.setProperty("--color-foreground-rgb", `${foregroundRGB.r}, ${foregroundRGB.g}, ${foregroundRGB.b}`);

        applied.value = true;
    };

    const initColorScheme = () => {
        const pageColorScheme = page.props.colorScheme;

        if (pageColorScheme) {
            setColorScheme(pageColorScheme);
        } else if (!applied.value) {
            applyColorScheme();
        }
    };

    onMounted(initColorScheme);

    watch(
        () => page.props.colorScheme,
        (newScheme) => {
            if (newScheme) {
                setColorScheme(newScheme);
            }
        },
        { immediate: true },
    );

    return {
        colorScheme,
        setColorScheme,
        applyColorScheme,
    };
}

export { DEFAULT_COLOR_SCHEME, normalizeColorScheme, rgbToHsl };
