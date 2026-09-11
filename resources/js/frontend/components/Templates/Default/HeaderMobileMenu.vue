<script setup>
import Button from "@frontend/components/UI/Button.vue";
import { Link, usePage } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import { Gift, Home, Mail, NotebookText, ShoppingBag, ShoppingCart, Tag, User, X } from "lucide-vue-next";
import { useI18n } from "vue-i18n";

const props = defineProps({
    isOpen: Boolean,
    settings: { type: Object, default: () => ({}) },
    cartItemCount: { type: Number, default: 0 },
    isLoggedIn: Boolean,
});

const emit = defineEmits(["close"]);
const page = usePage();
const { t } = useI18n();
const hasLogoError = ref(false);
const isLogoLoaded = ref(false);
const siteName = computed(() => props.settings.site_name?.trim() || "UMKM");
const logo = computed(() => props.settings.logo || "");
const siteInitials = computed(() => {
    const words = siteName.value.split(/\s+/).filter(Boolean);

    return words.length === 1 ? words[0].charAt(0).toUpperCase() : `${words[0].charAt(0)}${words[1].charAt(0)}`.toUpperCase();
});
const currentUrl = computed(() => page.url);
const navigationItems = computed(() => [
    { href: route("frontend.home"), icon: Home, label: t("labels.header.home"), path: "/" },
    { href: route("frontend.products"), icon: ShoppingBag, label: t("labels.header.products"), path: "/products" },
    { href: route("frontend.flashsales"), icon: Tag, label: t("labels.header.promo"), path: "/flash-sale" },
    { href: route("frontend.blog.index"), icon: NotebookText, label: t("labels.header.blog"), path: "/blog" },
    { href: route("frontend.contact"), icon: Mail, label: t("labels.header.contact"), path: "/contact" },
]);

watch(logo, () => {
    hasLogoError.value = false;
    isLogoLoaded.value = false;
});

const close = () => emit("close");

const isCurrentRoute = (path) => {
    const pathWithoutQuery = currentUrl.value.split("?")[0];

    return path === "/" ? pathWithoutQuery === "/" : pathWithoutQuery.startsWith(path);
};
</script>

<template>
    <Teleport to="body">
        <div v-if="isOpen" class="fixed inset-0 z-[60] bg-black/50 backdrop-blur-[2px]" @click="close"></div>

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
                class="border-border bg-background fixed top-0 right-0 bottom-0 z-[70] flex w-full flex-col border-l shadow-2xl"
                role="dialog"
                aria-modal="true"
                aria-labelledby="mobile-navigation-title"
            >
                <div class="flex h-full flex-col overflow-y-auto p-5">
                    <div class="border-border mb-6 flex items-center justify-between gap-4 border-b pb-5">
                        <Link id="mobile-navigation-title" :href="route('frontend.home')" class="min-w-0" :on-start="close">
                            <img
                                v-if="logo && !hasLogoError"
                                :src="logo"
                                :alt="siteName"
                                decoding="async"
                                class="h-9 w-auto max-w-[10rem] object-contain"
                                :class="isLogoLoaded ? '' : 'absolute opacity-0'"
                                @load="isLogoLoaded = true"
                                @error="hasLogoError = true"
                            />
                            <span v-if="!logo || hasLogoError || !isLogoLoaded" class="flex min-w-0 items-center gap-2">
                                <span
                                    class="bg-primary text-primary-foreground flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-sm font-semibold"
                                >
                                    {{ siteInitials }}
                                </span>
                                <span class="text-primary block truncate text-xl font-bold">{{ siteName }}</span>
                            </span>
                        </Link>
                        <Button
                            type="button"
                            class="text-foreground hover:bg-primary/10 hover:text-primary focus-visible:ring-primary shrink-0 rounded-full p-2 focus-visible:ring-2 focus-visible:outline-none"
                            :aria-label="t('labels.order.review.close')"
                            @click="close"
                        >
                            <X class="h-6 w-6" />
                        </Button>
                    </div>

                    <nav class="flex flex-col gap-2" :aria-label="t('labels.header.home')">
                        <Link
                            v-for="item in navigationItems"
                            :key="item.path"
                            :href="item.href"
                            class="group focus-visible:ring-primary flex min-h-12 items-center gap-4 rounded-xl px-4 text-base font-semibold transition-colors focus-visible:ring-2 focus-visible:outline-none"
                            :class="
                                isCurrentRoute(item.path) ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-primary/10 hover:text-primary'
                            "
                            :on-start="close"
                        >
                            <component :is="item.icon" class="h-5 w-5 shrink-0 transition-colors" aria-hidden="true" />
                            <span>{{ item.label }}</span>
                        </Link>
                    </nav>

                    <div class="mt-auto pt-6">
                        <div class="border-border border-t pt-5">
                            <Link
                                v-if="isLoggedIn"
                                :href="route('frontend.account')"
                                class="group text-foreground hover:bg-primary/10 hover:text-primary focus-visible:ring-primary flex min-h-12 items-center gap-4 rounded-xl px-4 text-base font-semibold transition-colors focus-visible:ring-2 focus-visible:outline-none"
                                :on-start="close"
                            >
                                <User class="h-5 w-5 shrink-0" aria-hidden="true" />
                                <span>{{ t("labels.header.account") }}</span>
                            </Link>
                            <Link
                                :href="route('frontend.cart')"
                                class="group text-foreground hover:bg-primary/10 hover:text-primary focus-visible:ring-primary relative flex min-h-12 items-center gap-4 rounded-xl px-4 text-base font-semibold transition-colors focus-visible:ring-2 focus-visible:outline-none"
                                :class="isLoggedIn ? 'mt-2' : ''"
                                :on-start="close"
                            >
                                <ShoppingCart class="h-5 w-5 shrink-0" aria-hidden="true" />
                                <span>{{ t("labels.header.cart") }}</span>
                                <span
                                    v-if="cartItemCount > 0"
                                    class="bg-primary text-primary-foreground ml-auto flex h-6 min-w-6 items-center justify-center rounded-full px-1.5 text-xs font-bold"
                                    aria-hidden="true"
                                >
                                    {{ cartItemCount > 99 ? "99+" : cartItemCount }}
                                </span>
                            </Link>
                        </div>

                        <div v-if="!isLoggedIn" class="border-primary/15 bg-primary/10 mt-6 rounded-2xl border p-5">
                            <div class="flex gap-4">
                                <div class="bg-primary/15 text-primary flex h-12 w-12 shrink-0 items-center justify-center rounded-xl">
                                    <Gift class="h-7 w-7" aria-hidden="true" />
                                </div>
                                <div class="min-w-0">
                                    <h2 class="text-foreground text-base leading-tight font-bold">{{ t("labels.mobile_promo.title") }}</h2>
                                    <p class="text-muted-foreground mt-2 text-sm leading-5">{{ t("labels.mobile_promo.description") }}</p>
                                </div>
                            </div>
                            <Link
                                :href="route('frontend.account')"
                                class="bg-primary text-primary-foreground hover:bg-primary/90 focus-visible:ring-primary mt-5 flex min-h-12 items-center justify-center rounded-xl px-4 py-3 text-sm font-bold transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                                :on-start="close"
                            >
                                {{ t("labels.mobile_promo.action") }}
                            </Link>
                        </div>
                    </div>
                </div>
            </aside>
        </Transition>
    </Teleport>
</template>
