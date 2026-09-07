<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import TemplateWrapper from "../../components/TemplateWrapper.vue";
import { resolveHomeSections } from "./sectionRegistry";

const props = defineProps({
    products: { type: Object, default: () => ({ data: [] }) },
    categories: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    template: { type: Object, default: null },
    sliders: { type: Object, default: () => ({ data: [] }) },
    flashsales: { type: Object, default: null },
    templatePreview: { type: Boolean, default: false },
});

const page = usePage();
const { t } = useI18n();
const settings = computed(() => page.props.settings ?? {});
const colorScheme = computed(() => page.props.colorScheme);
const sections = computed(() => resolveHomeSections(props));
</script>

<template>
    <TemplateWrapper
        :shell="false"
        :title="settings.social_title || settings.site_name"
        :description="settings.site_description"
        :keywords="settings.site_keywords"
        :social-image="settings.social_image"
        :color-scheme="colorScheme"
    >
        <div v-if="templatePreview" class="border-primary/20 bg-primary/10 text-foreground mx-auto my-4 max-w-[1440px] rounded-xl border px-4 py-3 text-center text-sm font-semibold" role="status">
            {{ t("home.template_preview") }}
        </div>
        <component
            :is="section.component"
            v-for="section in sections"
            :key="section.key"
            v-bind="section.props"
        />
    </TemplateWrapper>
</template>
