import { useI18n } from "vue-i18n";

export function useTranslations() {
    const { t } = useI18n();

    return {
        t,
        messages: {},
        labels: {},
        placeholders: {},
    };
}
