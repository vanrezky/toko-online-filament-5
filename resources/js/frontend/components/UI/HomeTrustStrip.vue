<script setup>
import { computed } from "vue";
import { CreditCard, Headphones, ShieldCheck, Truck } from "lucide-vue-next";
import { useI18n } from "vue-i18n";

const { t } = useI18n();
const trustItems = computed(() => [
    { icon: Truck, title: t("labels.home.trust.shipping_title"), description: t("labels.home.trust.shipping_description") },
    { icon: ShieldCheck, title: t("labels.home.trust.original_title"), description: t("labels.home.trust.original_description") },
    { icon: Headphones, title: t("labels.home.trust.support_title"), description: t("labels.home.trust.support_description") },
    { icon: CreditCard, title: t("labels.home.trust.secure_title"), description: t("labels.home.trust.secure_description") },
]);
</script>

<template>
    <section class="border-border bg-background border-y py-6 md:py-7" :aria-label="t('labels.home.trust.services_label')">
        <div class="container mx-auto px-4 md:px-8">
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4 lg:gap-0">
                <div
                    v-for="(item, index) in trustItems"
                    :key="item.title"
                    class="flex items-center gap-3 lg:px-8"
                    :class="index > 0 ? 'lg:border-border lg:border-l' : 'lg:pl-0'"
                >
                    <span class="bg-secondary text-primary flex h-12 w-12 shrink-0 items-center justify-center rounded-full">
                        <component :is="item.icon" class="h-5 w-5" aria-hidden="true" />
                    </span>
                    <div>
                        <h2 class="text-foreground text-sm font-bold leading-5">{{ item.title }}</h2>
                        <p class="text-muted-foreground mt-1 max-w-[18ch] text-xs leading-5">{{ item.description }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
