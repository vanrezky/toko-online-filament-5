import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function useTranslations() {
    const page = usePage();

    const messages = computed(() => page.props.translations?.messages || {});
    const labels = computed(() => page.props.translations?.labels || {});
    const placeholders = computed(() => page.props.translations?.placeholders || {});

    /**
     * Get a translation value by dot-notation key.
     * Supports parameterized replacements like {name}.
     *
     * @param {string} key - Dot-notation key, e.g. 'messages.success.saved'
     * @param {object} params - Object of replacements, e.g. { name: 'John' }
     * @returns {string}
     */
    function t(key, params = {}) {
        const parts = key.split('.');
        let value = page.props.translations;

        for (const part of parts) {
            if (value && typeof value === 'object' && part in value) {
                value = value[part];
            } else {
                return key; // fallback to key if translation missing
            }
        }

        if (typeof value !== 'string') {
            return key;
        }

        return value.replace(/:([a-zA-Z_]+)/g, (match, paramKey) => {
            return params[paramKey] !== undefined ? params[paramKey] : match;
        });
    }

    return {
        messages,
        labels,
        placeholders,
        t,
    };
}
