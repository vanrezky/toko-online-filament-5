<script setup>
import { computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import { ArrowLeft, CheckCircle2, ChevronRight, Heart, KeyRound, LogOut, MapPin, Package, Settings, User, Wallet } from "lucide-vue-next";
import { useI18n } from "vue-i18n";
import Card from "../UI/Card.vue";

const props = defineProps({
    activeDestination: { type: String, default: "overview" },
    activeTitle: { type: String, default: "" },
    activeDescription: { type: String, default: "" },
    user: { type: Object, default: null },
    balanceEnabled: { type: Boolean, default: false },
});

const { t } = useI18n();
const page = usePage();
const customer = computed(() => props.user || page.props.auth?.user);
const accountSectionHref = (section) => route("frontend.account", { section });
const navigationGroups = computed(() => {
    const accountItems = [
        { id: "overview", name: t("labels.account.menu.overview"), icon: User, href: accountSectionHref("overview") },
        { id: "settings", name: t("labels.account.menu.settings"), icon: Settings, href: accountSectionHref("settings") },
        { id: "password", name: t("labels.account.menu.password"), icon: KeyRound, href: accountSectionHref("password") },
        { id: "addresses", name: t("labels.account.menu.addresses"), icon: MapPin, href: accountSectionHref("addresses") },
    ];

    if (props.balanceEnabled) {
        accountItems.splice(1, 0, {
            id: "balance",
            name: t("labels.account.menu.balance"),
            icon: Wallet,
            href: accountSectionHref("balance"),
        });
    }

    return [
        {
            label: t("labels.account.heading"),
            items: accountItems,
        },
        {
            label: t("labels.account.quick_links"),
            items: [
                { id: "orders", name: t("labels.account.menu.orders"), icon: Package, href: route("frontend.orders") },
                { id: "wishlist", name: t("labels.account.wishlist"), icon: Heart, href: route("frontend.wishlist") },
            ],
        },
    ];
});
const isActive = (item) => item.id === props.activeDestination || (props.activeDestination === "address_form" && item.id === "addresses");
const isOverview = computed(() => props.activeDestination === "overview");
const activeDestinationName = computed(() => {
    for (const group of navigationGroups.value) {
        const item = group.items.find(
            (item) => item.id === props.activeDestination || (props.activeDestination === "address_form" && item.id === "addresses"),
        );
        if (item) return item.name;
    }

    return t("labels.account.heading");
});
const mobileContextTitle = computed(() => props.activeTitle || activeDestinationName.value);
</script>

<template>
    <div class="relative z-10 mx-auto grid max-w-7xl grid-cols-1 gap-8 lg:grid-cols-[300px_1fr]">
        <aside class="hidden lg:block">
            <Card variant="elevated" class="overflow-hidden rounded-2xl border-0">
                <div class="from-primary/10 via-primary/5 bg-gradient-to-r to-transparent p-6">
                    <div class="flex flex-col items-center text-center">
                        <div class="relative">
                            <div
                                class="bg-secondary flex h-20 w-20 items-center justify-center overflow-hidden rounded-full border-4 border-white shadow-md"
                            >
                                <img
                                    v-if="customer?.image || customer?.profile_photo_url"
                                    :src="customer.image || customer.profile_photo_url"
                                    class="h-full w-full object-cover"
                                />
                                <User v-else class="text-muted-foreground h-10 w-10" />
                            </div>
                            <div
                                class="bg-primary text-primary-foreground absolute -right-1 -bottom-1 flex h-7 w-7 items-center justify-center rounded-full border-2 border-white shadow"
                            >
                                <CheckCircle2 class="h-4 w-4" />
                            </div>
                        </div>
                        <p class="text-foreground mt-3 font-bold">{{ customer?.full_name }}</p>
                        <p class="text-muted-foreground text-sm">{{ customer?.email }}</p>
                    </div>
                </div>

                <div class="space-y-4 p-4">
                    <nav v-for="group in navigationGroups" :key="group.label" class="space-y-1">
                        <p class="text-muted-foreground px-4 text-xs font-semibold tracking-wide uppercase">{{ group.label }}</p>
                        <Link
                            v-for="item in group.items"
                            :key="item.id"
                            :href="item.href"
                            class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition-all"
                            :class="
                                isActive(item)
                                    ? 'bg-primary text-primary-foreground'
                                    : 'text-muted-foreground hover:bg-secondary hover:text-foreground'
                            "
                        >
                            <component :is="item.icon" class="h-5 w-5" />
                            <span>{{ item.name }}</span>
                        </Link>
                    </nav>
                    <div class="border-border border-t pt-4">
                        <Link
                            :href="route('frontend.logout')"
                            method="post"
                            as="button"
                            class="flex w-full items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-red-500 transition-all hover:bg-red-50"
                        >
                            <LogOut class="h-5 w-5" />
                            <span>{{ t("labels.actions.logout") }}</span>
                        </Link>
                    </div>
                </div>
            </Card>
        </aside>

        <main class="min-w-0 space-y-6">
            <div v-if="isOverview" data-test="mobile-account-overview-header" class="space-y-4 lg:hidden">
                <header class="space-y-1">
                    <h1 class="text-foreground text-2xl font-bold">{{ t("labels.account.heading") }}</h1>
                    <p class="text-muted-foreground text-sm">{{ t("labels.account.settings_description") }}</p>
                </header>

                <section
                    data-test="mobile-account-identity"
                    class="border-border bg-background flex items-center gap-4 rounded-2xl border p-4 shadow-sm"
                >
                    <div
                        class="bg-secondary flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-full border-2 border-white shadow-sm"
                    >
                        <img
                            v-if="customer?.image || customer?.profile_photo_url"
                            :src="customer.image || customer.profile_photo_url"
                            :alt="customer?.full_name || t('labels.account.heading')"
                            class="h-full w-full object-cover"
                        />
                        <User v-else class="text-muted-foreground h-7 w-7" aria-hidden="true" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-foreground truncate font-bold">{{ customer?.full_name }}</p>
                        <p class="text-muted-foreground truncate text-sm">{{ customer?.email }}</p>
                    </div>
                    <CheckCircle2 class="text-primary ml-auto h-5 w-5 shrink-0" aria-hidden="true" />
                </section>
            </div>

            <header v-else data-test="mobile-account-context" class="border-border flex items-center gap-3 border-b pb-4 lg:hidden">
                <Link
                    :href="accountSectionHref('overview')"
                    class="text-foreground hover:bg-secondary focus-visible:ring-primary flex min-h-11 min-w-11 items-center justify-center rounded-full transition-colors focus-visible:ring-2 focus-visible:outline-none"
                    :aria-label="t('labels.account.back_to_overview')"
                >
                    <ArrowLeft class="h-5 w-5" aria-hidden="true" />
                </Link>
                <div class="min-w-0">
                    <p class="text-muted-foreground text-xs font-semibold tracking-wide uppercase">{{ t("labels.account.heading") }}</p>
                    <p class="text-foreground truncate text-lg font-bold">{{ mobileContextTitle }}</p>
                    <p v-if="activeDescription" class="text-muted-foreground text-sm leading-5">{{ activeDescription }}</p>
                </div>
            </header>

            <nav
                v-if="isOverview"
                data-test="account-mobile-navigation"
                class="border-border bg-background overflow-hidden rounded-2xl border shadow-sm lg:hidden"
                :aria-label="t('labels.account.mobile_menu')"
            >
                <div class="border-border bg-secondary/40 border-b px-4 py-3">
                    <p class="text-foreground text-sm font-bold">{{ t("labels.account.mobile_menu") }}</p>
                </div>
                <div class="divide-border divide-y">
                    <template v-for="group in navigationGroups" :key="group.label">
                        <p class="text-muted-foreground bg-secondary/20 px-4 py-2 text-xs font-semibold tracking-wide uppercase">
                            {{ group.label }}
                        </p>
                        <Link
                            v-for="item in group.items"
                            :key="item.id"
                            :href="item.href"
                            class="group flex min-h-12 items-center gap-3 px-4 py-3 text-sm font-semibold transition-colors focus-visible:ring-2 focus-visible:outline-none focus-visible:ring-inset"
                            :class="
                                isActive(item)
                                    ? 'bg-primary text-primary-foreground focus-visible:ring-primary-foreground'
                                    : 'text-foreground hover:bg-secondary focus-visible:ring-primary'
                            "
                        >
                            <span
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                                :class="isActive(item) ? 'bg-primary-foreground/15' : 'bg-primary/10 text-primary'"
                            >
                                <component :is="item.icon" class="h-5 w-5" aria-hidden="true" />
                            </span>
                            <span class="min-w-0 flex-1 truncate">{{ item.name }}</span>
                            <ChevronRight class="h-4 w-4 shrink-0 opacity-70" aria-hidden="true" />
                        </Link>
                    </template>
                </div>
                <div class="border-border border-t p-2">
                    <Link
                        data-test="mobile-account-logout"
                        :href="route('frontend.logout')"
                        method="post"
                        as="button"
                        class="text-destructive hover:bg-destructive/10 focus-visible:ring-destructive flex min-h-12 w-full items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition-colors focus-visible:ring-2 focus-visible:outline-none"
                    >
                        <LogOut class="h-5 w-5" aria-hidden="true" />
                        <span>{{ t("labels.actions.logout") }}</span>
                    </Link>
                </div>
            </nav>

            <slot />
        </main>
    </div>
</template>
