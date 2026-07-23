<script setup>
import Button from "@frontend/components/UI/Button.vue";
import { Link } from "@inertiajs/vue3";
import { X, User } from "lucide-vue-next";
import { computed, ref, watch } from "vue";
import { useI18n } from "vue-i18n";

const props = defineProps({
    isOpen: Boolean,
    settings: { type: Object, default: () => ({}) },
    cartItemCount: { type: Number, default: 0 },
    isLoggedIn: Boolean,
});

const emit = defineEmits(["close"]);
const { t } = useI18n();
const hasLogoError = ref(false);
const siteName = computed(() => props.settings.site_name?.trim() || "UMKM");
const logo = computed(() => props.settings.logo || "");

watch(logo, () => {
    hasLogoError.value = false;
});

const close = () => emit("close");
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-[60] bg-black/50 backdrop-blur-sm" @click="close"></div>

    <Transition
        enter-active-class="transition-transform duration-300 ease-out"
        enter-from-class="translate-x-full"
        enter-to-class="translate-x-0"
        leave-active-class="transition-transform duration-200 ease-in"
        leave-from-class="translate-x-0"
        leave-to-class="translate-x-full"
    >
        <aside
            v-if="isOpen"
            id="mobile-navigation"
            class="fixed top-0 right-0 bottom-0 z-[70] flex w-[80%] max-w-xs flex-col bg-background shadow-2xl"
            role="dialog"
            aria-modal="true"
            aria-labelledby="mobile-navigation-title"
        >
            <div class="flex h-full flex-col p-6">
                <div class="mb-8 flex items-center justify-between gap-4">
                    <Link id="mobile-navigation-title" :href="route('frontend.home')" class="min-w-0" @click="close">
                        <img
                            v-if="logo && !hasLogoError"
                            :src="logo"
                            :alt="siteName"
                            decoding="async"
                            class="h-9 w-auto max-w-[10rem] object-contain"
                            @error="hasLogoError = true"
                        />
                        <span v-else class="text-primary block truncate text-xl font-bold">{{ siteName }}</span>
                    </Link>
                    <Button type="button" class="text-foreground shrink-0 p-2" :aria-label="t('labels.order.review.close')" @click="close">
                        <X class="h-6 w-6" />
                    </Button>
                </div>

                <nav class="flex flex-col space-y-1">
                <Link :href="route('frontend.home')" class="border-border/50 text-foreground border-b py-3 text-sm font-semibold" @click="close">
                    {{ t("labels.header.home") }}
                </Link>
                <Link :href="route('frontend.products')" class="border-border/50 text-foreground border-b py-3 text-sm font-semibold" @click="close">
                    {{ t("labels.header.products") }}
                </Link>
                <Link :href="route('frontend.blog.index')" class="border-border/50 text-foreground border-b py-3 text-sm font-semibold" @click="close">
                    {{ t("labels.header.blog") }}
                </Link>
                <Link :href="route('frontend.wishlist')" class="border-border/50 text-foreground border-b py-3 text-sm font-semibold" @click="close">
                    {{ t("labels.header.wishlist") }}
                </Link>
                <Link :href="route('frontend.contact')" class="border-border/50 text-foreground border-b py-3 text-sm font-semibold" @click="close">
                    {{ t("labels.header.contact") }}
                </Link>
                </nav>

                <div class="border-border mt-auto border-t pt-8">
                    <Link :href="route('frontend.account')" class="text-foreground/70 hover:text-primary flex min-h-11 items-center gap-3 text-sm font-semibold" @click="close">
                        <User class="h-5 w-5" />
                        <span>{{ isLoggedIn ? t("labels.header.account") : t("labels.header.login") }}</span>
                    </Link>
            </div>
        </div>
        </aside>
    </Transition>
</template>
