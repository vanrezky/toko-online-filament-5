<script setup>
import { Link } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";

const props = defineProps({
    logo: { type: String, default: "" },
    siteName: { type: String, default: "" },
});

const hasLogoError = ref(false);
const displayName = computed(() => props.siteName.trim() || "UMKM");
const initials = computed(() => {
    const words = displayName.value.split(/\s+/).filter(Boolean);

    return words.length === 1 ? words[0].charAt(0).toUpperCase() : `${words[0].charAt(0)}${words[1].charAt(0)}`.toUpperCase();
});

watch(
    () => props.logo,
    () => {
        hasLogoError.value = false;
    },
);
</script>

<template>
    <Link
        :href="route('frontend.home')"
        class="flex min-h-11 items-center gap-2 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 focus-visible:ring-offset-2"
    >
        <img
            v-if="logo && !hasLogoError"
            :src="logo"
            :alt="displayName"
            decoding="async"
            class="h-8 w-auto max-w-[8rem] object-contain md:h-10 md:max-w-[12rem]"
            @error="hasLogoError = true"
        />
        <template v-else>
            <span class="bg-primary text-primary-foreground flex h-8 w-8 items-center justify-center rounded-md text-sm font-semibold md:hidden">
                {{ initials }}
            </span>
            <span class="font-display text-primary hidden max-w-[12rem] truncate text-2xl leading-none md:inline">
                {{ displayName }}
            </span>
        </template>
    </Link>
</template>
