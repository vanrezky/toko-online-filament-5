<script setup>
import { computed, watch, onMounted } from "vue";
import { useColorScheme } from "../composables/useColorScheme";
import DefaultLayout from "./Templates/Default/BaseLayout.vue";
import PageShell from "./PageShell.vue";

const props = defineProps({
    title: String,
    description: String,
    keywords: String,
    socialImage: String,
    template: {
        type: String,
        default: "default",
    },
    colorScheme: {
        type: Object,
        default: null,
    },
    shell: { type: Boolean, default: true },
});

const { colorScheme, setColorScheme, applyColorScheme } = useColorScheme();

onMounted(() => {
    if (props.colorScheme) {
        setColorScheme(props.colorScheme);
    } else {
        applyColorScheme();
    }
});

watch(
    () => props.colorScheme,
    (newScheme) => {
        if (newScheme) {
            setColorScheme(newScheme);
        }
    },
    { immediate: true },
);

const Layout = computed(() => {
    return DefaultLayout;
});
</script>

<template>
    <component :is="Layout" :title="title" :description="description" :keywords="keywords" :social-image="socialImage">
        <PageShell v-if="props.shell"><slot /></PageShell>
        <slot v-else />
    </component>
</template>
