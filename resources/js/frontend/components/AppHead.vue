<script setup>
import { computed } from "vue";
import { Head, usePage } from "@inertiajs/vue3";

const props = defineProps({
    title: String,
    description: String,
    keywords: String,
    socialImage: String,
});

const isProductionEnv = import.meta.env.PROD;
const settings = computed(() => usePage().props.settings);

const ogTitle = computed(() => props.title || settings.value.social_title || settings.value.site_name);
const ogDescription = computed(() => props.description || settings.value.social_description || settings.value.site_description);
const ogImage = computed(() => props.socialImage || settings.value.social_image || settings.value.logo);
</script>

<template>
    <Head :title="title ? `${title} - ${settings.site_name}` : settings.site_name">
        <link v-if="settings.favicon" rel="shortcut icon" type="image/x-icon" :href="settings.favicon" />

        <meta v-if="description" name="description" :content="description" />
        <meta v-if="keywords" name="keywords" :content="keywords" />

        <meta property="og:title" :content="ogTitle" />
        <meta property="og:description" :content="ogDescription" />
        <meta property="og:type" content="website" />
        <meta v-if="ogImage" property="og:image" :content="ogImage" />
        <meta property="og:site_name" :content="settings.site_name" />

        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="ogTitle" />
        <meta name="twitter:description" :content="ogDescription" />
        <meta v-if="ogImage" name="twitter:image" :content="ogImage" />

        <meta v-if="isProductionEnv" http-equiv="Content-Security-Policy" content="upgrade-insecure-requests" />

        <slot />
    </Head>
</template>