<script setup>
import { computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import { Heart, KeyRound, LogOut, MapPin, Package, Settings, User, CheckCircle2, Wallet } from "lucide-vue-next";
import { useI18n } from "vue-i18n";
import Card from "../UI/Card.vue";

const props = defineProps({
    activeDestination: { type: String, default: "overview" },
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
            <nav class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 lg:hidden" :aria-label="t('labels.account.heading')">
                <template v-for="group in navigationGroups" :key="group.label">
                    <Link
                        v-for="item in group.items"
                        :key="item.id"
                        :href="item.href"
                        class="inline-flex shrink-0 items-center gap-2 rounded-full px-4 py-2.5 text-sm font-semibold"
                        :class="isActive(item) ? 'bg-primary text-primary-foreground' : 'bg-secondary text-foreground'"
                    >
                        <component :is="item.icon" class="h-4 w-4" />
                        {{ item.name }}
                    </Link>
                </template>
            </nav>
            <slot />
        </main>
    </div>
</template>
