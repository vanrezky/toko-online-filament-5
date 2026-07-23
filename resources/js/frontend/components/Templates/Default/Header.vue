<script setup>
import Button from "@frontend/components/UI/Button.vue";
import { Link, usePage, router } from "@inertiajs/vue3";
import { computed, ref, nextTick } from "vue";
import { Search, ShoppingCart, User, Menu, X } from "lucide-vue-next";
import FormInput from "../../UI/FormInput.vue";
import { useI18n } from "vue-i18n";
import HeaderBrand from "./HeaderBrand.vue";
import HeaderMobileMenu from "./HeaderMobileMenu.vue";

const page = usePage();
const settings = computed(() => page.props.settings ?? {});
const isMobileMenuOpen = ref(false);
const searchQuery = ref("");
const isDesktopSearchOpen = ref(false);
const desktopSearchInput = ref(null);
const currentUrl = computed(() => page.url);
const { t } = useI18n();

const toggleDesktopSearch = () => {
    isDesktopSearchOpen.value = !isDesktopSearchOpen.value;
    if (isDesktopSearchOpen.value) {
        nextTick(() => {
            desktopSearchInput.value?.focus();
        });
    }
};

const cartItemCount = computed(() => usePage().props.cart_total || 0);
const auth = computed(() => usePage().props.auth);
const isLoggedIn = computed(() => !!auth.value?.user);

const searchPlaceholder = computed(() => {
    const name = settings.value?.site_name?.trim() || "UMKM";
    return t("labels.header.search_placeholder", { site: name });
});

const toggleMobileMenu = () => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
};

const isCurrentRoute = (path) => {
    const currentPath = currentUrl.value.split("?")[0];

    return path === "/" ? currentPath === "/" : currentPath.startsWith(path);
};

const handleSearch = () => {
    if (searchQuery.value.trim()) {
        router.get(
            route("frontend.products"),
            { search: searchQuery.value },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    }
};

const clearSearch = () => {
    searchQuery.value = "";
    router.get(
        route("frontend.products"),
        {},
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};
</script>

<template>
    <header class="border-border sticky top-0 z-50 border-b bg-white shadow-sm">
        <div class="container mx-auto px-4">
            <div class="flex h-14 items-center justify-between gap-2 md:h-16">
                <div class="flex shrink-0 items-center">
                    <HeaderBrand :logo="settings.logo" :site-name="settings.site_name" />
                </div>

                <!-- Mobile Search Bar -->
                <div class="min-w-0 flex-1 md:hidden">
                    <FormInput
                            v-model="searchQuery"
                            type="text"
                            :placeholder="searchPlaceholder"
                            class="py-2"
                            @keyup.enter="handleSearch"
                        >
                        <template #prefix><Search class="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" /></template>
                    </FormInput>
                </div>

                <!-- Desktop Nav Links -->
                <nav class="mx-4 hidden h-full items-center gap-7 md:flex">
                    <Link
                        :href="route('frontend.home')"
                        class="relative flex h-full items-center px-1 text-sm font-semibold tracking-[0.08em] transition-colors duration-200 after:absolute after:bottom-3 after:left-0 after:h-0.5 after:w-full after:origin-left after:bg-primary after:transition-transform after:duration-300 after:ease-out focus-visible:ring-primary/30 focus-visible:ring-2 focus-visible:outline-none"
                        :class="isCurrentRoute('/') ? 'text-primary after:scale-x-100' : 'text-muted-foreground after:scale-x-0 hover:text-primary hover:after:scale-x-100'"
                    >
                        {{ t("labels.header.home") }}
                    </Link>
                    <Link
                        :href="route('frontend.products')"
                        class="relative flex h-full items-center px-1 text-sm font-semibold tracking-[0.08em] transition-colors duration-200 after:absolute after:bottom-3 after:left-0 after:h-0.5 after:w-full after:origin-left after:bg-primary after:transition-transform after:duration-300 after:ease-out focus-visible:ring-primary/30 focus-visible:ring-2 focus-visible:outline-none"
                        :class="isCurrentRoute('/products') ? 'text-primary after:scale-x-100' : 'text-muted-foreground after:scale-x-0 hover:text-primary hover:after:scale-x-100'"
                    >
                        {{ t("labels.header.products") }}
                    </Link>
                    <Link
                        :href="route('frontend.blog.index')"
                        class="relative flex h-full items-center px-1 text-sm font-semibold tracking-[0.08em] transition-colors duration-200 after:absolute after:bottom-3 after:left-0 after:h-0.5 after:w-full after:origin-left after:bg-primary after:transition-transform after:duration-300 after:ease-out focus-visible:ring-primary/30 focus-visible:ring-2 focus-visible:outline-none"
                        :class="isCurrentRoute('/blog') ? 'text-primary after:scale-x-100' : 'text-muted-foreground after:scale-x-0 hover:text-primary hover:after:scale-x-100'"
                    >
                        {{ t("labels.header.blog") }}
                    </Link>
                    <Link
                        :href="route('frontend.contact')"
                        class="relative flex h-full items-center px-1 text-sm font-semibold tracking-[0.08em] transition-colors duration-200 after:absolute after:bottom-3 after:left-0 after:h-0.5 after:w-full after:origin-left after:bg-primary after:transition-transform after:duration-300 after:ease-out focus-visible:ring-primary/30 focus-visible:ring-2 focus-visible:outline-none"
                        :class="isCurrentRoute('/contact') ? 'text-primary after:scale-x-100' : 'text-muted-foreground after:scale-x-0 hover:text-primary hover:after:scale-x-100'"
                    >
                        {{ t("labels.header.contact") }}
                    </Link>
                </nav>

                <!-- Action Icons -->
                <div class="flex items-center space-x-2 md:space-x-4">
                    <!-- Desktop Search Icon -->
                    <div class="hidden md:block">
                        <Button
                            type="button"
                            @click="toggleDesktopSearch"
                            class="text-muted-foreground hover:text-primary p-2 transition-colors"
                            :class="{ 'text-primary': isDesktopSearchOpen }"
                            :aria-label="t('labels.header.search_products')"
                            :aria-expanded="isDesktopSearchOpen"
                            aria-controls="desktop-search"
                        >
                            <Search class="h-5 w-5" />
                        </Button>
                    </div>

                    <!-- User Icon -->
                    <Link
                        :href="route('frontend.account')"
                        class="text-foreground/70 hover:text-primary hidden min-h-11 min-w-11 items-center justify-center p-2 transition-colors md:flex"
                        :aria-label="isLoggedIn ? t('labels.header.account') : t('labels.header.login')"
                    >
                        <User class="h-5 w-5" />
                    </Link>

                    <!-- Cart Icon -->
                    <Link
                        :href="route('frontend.cart')"
                        class="text-muted-foreground hover:text-primary relative flex min-h-11 min-w-11 items-center justify-center p-2 transition-colors"
                        :aria-label="t('labels.header.cart')"
                    >
                        <ShoppingCart class="h-5 w-5" />
                        <span
                            v-if="cartItemCount > 0"
                            class="bg-destructive absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full text-[10px] font-bold text-white"
                        >
                            {{ cartItemCount > 99 ? "99+" : cartItemCount }}
                        </span>
                    </Link>

                    <!-- Mobile Menu Button -->
                    <Button
                        type="button"
                        class="text-muted-foreground hover:text-primary -mr-2 p-2 transition-colors md:hidden"
                        :aria-expanded="isMobileMenuOpen"
                        aria-controls="mobile-navigation"
                        @click="toggleMobileMenu"
                    >
                        <Menu class="h-6 w-6" />
                    </Button>
                </div>
            </div>
        </div>

        <!-- Desktop Search Bar (Expandable) -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="-translate-y-2 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="-translate-y-2 opacity-0"
        >
            <div
                v-if="isDesktopSearchOpen"
                id="desktop-search"
                class="border-border hidden border-b bg-background shadow-sm md:block"
            >
            <div class="mx-auto mb-4 w-2/3 max-w-4xl px-4 py-6">
                <div class="group relative flex items-center gap-3 border-b-2 border-border pb-1 transition-colors duration-200 focus-within:border-primary">
                    <Search class="text-muted-foreground h-5 w-5 shrink-0 transition-colors duration-200 group-focus-within:text-primary" />
                    <FormInput
                        ref="desktopSearchInput"
                        v-model="searchQuery"
                        type="text"
                        :placeholder="t('labels.header.search_products')"
                        borderless
                        wrapper-class="flex-1"
                        class="bg-transparent py-3 text-lg placeholder:text-muted-foreground/70"
                        @keyup.enter="handleSearch"
                        @keyup.escape="isDesktopSearchOpen = false"
                    />
                    <Button
                        v-if="searchQuery"
                        @click="clearSearch"
                        class="text-muted-foreground hover:text-foreground shrink-0"
                        :aria-label="t('labels.header.search_products')"
                    >
                        <X class="h-5 w-5" />
                    </Button>
                </div>
            </div>
        </div>
        </Transition>

        <HeaderMobileMenu
            :is-open="isMobileMenuOpen"
            :settings="settings"
            :cart-item-count="cartItemCount"
            :is-logged-in="isLoggedIn"
            @close="isMobileMenuOpen = false"
        />
    </header>
</template>
