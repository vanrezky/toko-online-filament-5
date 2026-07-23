<script setup>
import { computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import { Toaster } from "vue-sonner";
import { ArrowLeft } from "lucide-vue-next";
import { useI18n } from "vue-i18n";
import AppHead from "./AppHead.vue";
import FlashMessages from "./FlashMessages.vue";

const props = defineProps({
    class: { type: [String, Array, Object], default: "" },
    title: { type: String, default: "" },
    description: { type: String, default: "" },
    container: { type: Boolean, default: false },
});

const settings = computed(() => usePage().props.settings ?? {});
const isPrivateStore = computed(() => settings.value.is_private_store ?? false);
const { t } = useI18n();
</script>

<template>
    <AppHead :title="props.title" :description="props.description" />
    <Toaster position="top-right" richColors closeButton />
    <FlashMessages />
    <vue3-confirm-dialog />

    <main
        class="bg-muted/30 flex min-h-screen items-center justify-center px-4 py-10 sm:px-6 sm:py-12"
        :class="props.class"
    >
        <div class="w-full max-w-lg">
            <div v-if="settings.logo || settings.site_name" class="mb-8 flex flex-col items-center text-center sm:mb-10">
                <img
                    v-if="settings.logo"
                    :src="settings.logo"
                    :alt="settings.site_name || props.title"
                    decoding="async"
                    class="mb-5 max-h-16 w-auto max-w-[13rem] object-contain sm:max-h-20"
                />
                <div v-else-if="settings.site_name" class="text-primary mb-5 text-2xl font-bold tracking-tight sm:text-3xl">
                    {{ settings.site_name }}
                </div>
            </div>

            <div class="space-y-6 sm:space-y-8">
                <slot />
            </div>

            <Link
                v-if="!isPrivateStore"
                :href="route('frontend.home')"
            class="border-border text-muted-foreground hover:border-primary hover:text-primary mt-8 flex min-h-11 w-full items-center justify-center gap-2 rounded-full border px-5 py-3 text-sm font-semibold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/25 focus-visible:ring-offset-2 focus-visible:ring-offset-background"
            >
                <ArrowLeft class="h-4 w-4" />
                {{ t("labels.actions.back_to_home") }}
            </Link>
        </div>
    </main>
</template>
