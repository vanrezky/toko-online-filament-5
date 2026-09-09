<script setup>
import { Link, router, usePage } from "@inertiajs/vue3";
import { computed, nextTick, ref } from "vue";
import { Menu, Search, ShoppingCart, User, X } from "lucide-vue-next";
import { useI18n } from "vue-i18n";
import Button from "@frontend/components/UI/Button.vue";
import FormInput from "../../UI/FormInput.vue";
import HeaderBrand from "./HeaderBrand.vue";
import HeaderMobileMenu from "./HeaderMobileMenu.vue";

const page = usePage();
const { t } = useI18n();
const settings = computed(() => page.props.settings ?? {});
const currentUrl = computed(() => page.url);
const searchQuery = ref("");
const searchInput = ref(null);
const isSearchOpen = ref(false);
const isMobileMenuOpen = ref(false);

const cartItemCount = computed(() => page.props.cart_total || 0);
const isLoggedIn = computed(() => Boolean(page.props.auth?.user));
const searchPlaceholder = computed(() => {
    const siteName = settings.value.site_name?.trim() || "UMKM";

    return t("labels.header.search_placeholder", { site: siteName });
});

const isCurrentRoute = (path) => {
    const currentPath = currentUrl.value.split("?")[0];

    return path === "/" ? currentPath === "/" : currentPath.startsWith(path);
};

const handleSearch = () => {
    const search = searchQuery.value.trim();

    if (!search) return;

    router.get(route("frontend.products"), { search }, { preserveState: true, preserveScroll: true });
};

const toggleSearch = async () => {
    isSearchOpen.value = !isSearchOpen.value;

    if (isSearchOpen.value) {
        await nextTick();
        searchInput.value?.focus();
    }
};
</script>

<template>
    <header class="border-border sticky top-0 z-50 border-b bg-white/95 shadow-[0_8px_24px_-24px_hsl(var(--foreground)/0.45)] backdrop-blur">
        <div class="mx-auto max-w-[1408px] px-4 md:px-8 xl:px-0">
            <div class="flex h-14 items-center gap-4 md:gap-6">
                <HeaderBrand class="shrink-0" :logo="settings.logo" :site-name="settings.site_name" />

                <nav class="hidden min-w-0 flex-1 items-center justify-center gap-6 lg:flex xl:gap-8" :aria-label="t('labels.header.home')">
                    <Link
                        v-for="item in [
                            { href: route('frontend.home'), label: t('labels.header.home'), path: '/' },
                            { href: route('frontend.products'), label: t('labels.header.products'), path: '/products' },
                            { href: route('frontend.flashsales'), label: t('labels.header.promo'), path: '/flash-sale' },
                            { href: route('frontend.blog.index'), label: t('labels.header.blog'), path: '/blog' },
                            { href: route('frontend.contact'), label: t('labels.header.contact'), path: '/contact' },
                        ]"
                        :key="item.path"
                        :href="item.href"
                        class="relative flex min-h-11 items-center px-1 text-sm font-medium transition-colors after:absolute after:right-0 after:bottom-1 after:left-0 after:h-0.5 after:origin-left after:bg-primary after:transition-transform focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30"
                        :class="isCurrentRoute(item.path) ? 'text-primary after:scale-x-100' : 'text-muted-foreground after:scale-x-0 hover:text-primary hover:after:scale-x-100'"
                    >
                        {{ item.label }}
                    </Link>
                </nav>

                <form class="hidden w-[28rem] max-w-[32vw] shrink-0 lg:block" @submit.prevent="handleSearch">
                    <FormInput
                        v-model="searchQuery"
                        type="search"
                        :placeholder="searchPlaceholder"
                        :aria-label="t('labels.header.search_products')"
                        class="rounded-full border-border bg-secondary/45 py-2.5 pl-10 pr-4 text-sm shadow-none focus-within:bg-white"
                    >
                        <template #prefix>
                            <Search class="text-muted-foreground absolute top-1/2 left-4 h-4 w-4 -translate-y-1/2" aria-hidden="true" />
                        </template>
                    </FormInput>
                </form>

                <div class="ml-auto flex shrink-0 items-center gap-1 md:gap-2">
                    <Button
                        type="button"
                        class="text-foreground hover:text-primary rounded-full p-2 lg:hidden"
                        :aria-expanded="isSearchOpen"
                        aria-controls="mobile-product-search"
                        :aria-label="isSearchOpen ? t('labels.actions.close') : t('labels.header.open_search')"
                        @click="toggleSearch"
                    >
                        <X v-if="isSearchOpen" class="h-5 w-5" aria-hidden="true" />
                        <Search v-else class="h-5 w-5" aria-hidden="true" />
                    </Button>
                    <Link
                        :href="route('frontend.account')"
                        class="text-foreground hover:text-primary focus-visible:ring-primary/30 flex min-h-11 min-w-11 items-center justify-center rounded-full p-2 transition-colors focus-visible:ring-2 focus-visible:outline-none"
                        :aria-label="isLoggedIn ? t('labels.header.account') : t('labels.header.login')"
                    >
                        <User class="h-5 w-5" aria-hidden="true" />
                    </Link>
                    <Link
                        :href="route('frontend.cart')"
                        class="text-foreground hover:text-primary focus-visible:ring-primary/30 relative flex min-h-11 min-w-11 items-center justify-center rounded-full p-2 transition-colors focus-visible:ring-2 focus-visible:outline-none"
                        :aria-label="t('labels.header.cart')"
                    >
                        <ShoppingCart class="h-5 w-5" aria-hidden="true" />
                        <span
                            v-if="cartItemCount > 0"
                            class="bg-primary text-primary-foreground absolute top-0 right-0 flex h-4 min-w-4 items-center justify-center rounded-full px-1 text-[10px] font-bold"
                        >
                            {{ cartItemCount > 99 ? "99+" : cartItemCount }}
                        </span>
                    </Link>
                    <Button
                        type="button"
                        class="text-foreground hover:text-primary -mr-2 rounded-full p-2 lg:hidden"
                        :aria-expanded="isMobileMenuOpen"
                        aria-controls="mobile-navigation"
                        :aria-label="isMobileMenuOpen ? t('labels.order.review.close') : t('labels.header.home')"
                        @click="isMobileMenuOpen = !isMobileMenuOpen"
                    >
                        <X v-if="isMobileMenuOpen" class="h-6 w-6" aria-hidden="true" />
                        <Menu v-else class="h-6 w-6" aria-hidden="true" />
                    </Button>
                </div>
            </div>

            <form v-if="isSearchOpen" id="mobile-product-search" class="pb-3 lg:hidden" @submit.prevent="handleSearch">
                <FormInput
                    ref="searchInput"
                    v-model="searchQuery"
                    type="search"
                    :placeholder="searchPlaceholder"
                    :aria-label="t('labels.header.search_products')"
                    class="rounded-full border-border bg-secondary/45 py-2.5 pl-10 pr-4 text-sm shadow-none"
                >
                    <template #prefix>
                        <Search class="text-muted-foreground absolute top-1/2 left-4 h-4 w-4 -translate-y-1/2" aria-hidden="true" />
                    </template>
                </FormInput>
            </form>
        </div>

        <HeaderMobileMenu
            :is-open="isMobileMenuOpen"
            :settings="settings"
            :cart-item-count="cartItemCount"
            :is-logged-in="isLoggedIn"
            @close="isMobileMenuOpen = false"
        />
    </header>
</template>
