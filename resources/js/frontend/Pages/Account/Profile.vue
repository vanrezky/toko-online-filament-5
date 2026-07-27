<script setup>
import Button from "@frontend/components/UI/Button.vue";
import { ref, computed, watch, getCurrentInstance } from "vue";
import { Link, useForm, router, usePage } from "@inertiajs/vue3";
import TemplateWrapper from "../../components/TemplateWrapper.vue";
import PageShell from "../../components/PageShell.vue";
import FormFile from "../../components/UI/FormFile.vue";
import FormInput from "../../components/UI/FormInput.vue";
import Card from "../../components/UI/Card.vue";
import { formatCurrency, formatDate } from "../../lib/utils";
import { getOrderStatusColor, getOrderStatusLabel } from "../../lib/order-status";
import {
    User,
    Package,
    MapPin,
    Settings,
    LogOut,
    ChevronRight,
    Camera,
    Save,
    X,
    Plus,
    Trash2,
    CheckCircle2,
    Edit3,
    Heart,
    Clock,
    Wallet,
} from "lucide-vue-next";
import axios from "axios";
import { useI18n } from "vue-i18n";

const { t, locale } = useI18n();

const localeCode = computed(() => (locale.value === "id" ? "id-ID" : "en-US"));

const props = defineProps({
    user: Object,
    addresses: Object,
    provinces: Array,
    totalOrders: Number,
    recentOrders: Array,
    balanceEnabled: Boolean,
    balanceHistory: Array,
});

const page = usePage();
const { proxy } = getCurrentInstance();

const getSectionFromUrl = () => {
    const params = new URLSearchParams(window.location.search);
    return params.get("section") || "overview";
};

const activeSection = ref(getSectionFromUrl());

watch(
    () => page.url,
    () => {
        activeSection.value = getSectionFromUrl();
    },
);

const imagePreview = ref(null);
const fileInput = ref(null);
const editingAddress = ref(null);

const districts = ref([]);
const subDistricts = ref([]);
const villages = ref([]);

const profileForm = useForm({
    first_name: props.user.first_name,
    last_name: props.user.last_name,
    email: props.user.email,
    phone: props.user.phone || "",
    image: null,
});

const addressForm = useForm({
    id: null,
    name: "",
    phone: "",
    province_id: "",
    district_id: "",
    sub_district_id: "",
    village_id: "",
    address: "",
    postal_code: "",
    is_featured: false,
});

const menuItems = [
    { id: "overview", name: t("labels.account.menu.overview"), icon: User },
    { id: "orders", name: t("labels.account.menu.orders"), icon: Package, href: route("frontend.orders") },
    { id: "addresses", name: t("labels.account.menu.addresses"), icon: MapPin },
    { id: "settings", name: t("labels.account.menu.settings"), icon: Settings },
];

const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        profileForm.image = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const submitProfile = () => {
    profileForm.post(route("frontend.account.update"), {
        preserveScroll: true,
        onSuccess: () => {
            imagePreview.value = null;
            profileForm.image = null;
            activeSection.value = "overview";
        },
    });
};

const openAddressForm = (address = null) => {
    if (address) {
        editingAddress.value = address;
        addressForm.id = address.id;
        addressForm.name = address.name;
        addressForm.phone = address.phone;
        addressForm.province_id = address.province_id;
        addressForm.district_id = address.district_id;
        addressForm.sub_district_id = address.sub_district_id;
        addressForm.village_id = address.village_id;
        addressForm.address = address.address;
        addressForm.postal_code = address.postal_code;
        addressForm.is_featured = !!address.is_featured;

        fetchDistricts(address.province_id, address.district_id);
        fetchSubDistricts(address.district_id, address.sub_district_id);
        fetchVillages(address.sub_district_id, address.village_id);
    } else {
        editingAddress.value = null;
        addressForm.reset();
        districts.value = [];
        subDistricts.value = [];
        villages.value = [];
    }
    activeSection.value = "address_form";
};

const fetchDistricts = async (provinceId, selectId = null) => {
    if (!provinceId) return;
    const res = await axios.get(route("frontend.regions.districts", provinceId));
    districts.value = res.data;
    if (!selectId) {
        addressForm.district_id = "";
        addressForm.sub_district_id = "";
        addressForm.village_id = "";
        subDistricts.value = [];
        villages.value = [];
    }
};

const fetchSubDistricts = async (districtId, selectId = null) => {
    if (!districtId) return;
    const res = await axios.get(route("frontend.regions.sub-districts", districtId));
    subDistricts.value = res.data;
    if (!selectId) {
        addressForm.sub_district_id = "";
        addressForm.village_id = "";
        villages.value = [];
    }
};

const fetchVillages = async (subDistrictId, selectId = null) => {
    if (!subDistrictId) return;
    const res = await axios.get(route("frontend.regions.villages", subDistrictId));
    villages.value = res.data;
    if (!selectId) addressForm.village_id = "";
};

const onVillageChange = () => {
    const village = villages.value.find((v) => v.id === addressForm.village_id);
    if (village && village.postal_code) {
        addressForm.postal_code = village.postal_code;
    }
};

const submitAddress = () => {
    if (addressForm.id) {
        addressForm.patch(route("frontend.account.address.update", addressForm.id), {
            onSuccess: () => {
                activeSection.value = "addresses";
                addressForm.reset();
                districts.value = [];
                subDistricts.value = [];
            },
        });
    } else {
        addressForm.post(route("frontend.account.address.store"), {
            onSuccess: () => {
                activeSection.value = "addresses";
                addressForm.reset();
                districts.value = [];
                subDistricts.value = [];
            },
        });
    }
};

const deleteAddress = (id, addressName) => {
    proxy.$confirm({
        title: t("labels.dialogs.delete_address_title"),
        message: t("labels.dialogs.delete_address_message", { name: addressName }),
        button: {
            no: t("labels.dialogs.cancel"),
            yes: t("labels.dialogs.confirm_delete"),
        },
        callback: (confirm) => {
            if (confirm) {
                router.delete(route("frontend.account.address.delete", id), {
                    preserveScroll: true,
                });
            }
        },
    });
};

const setFeaturedAddress = (address) => {
    router.patch(
        route("frontend.account.address.update", address.id),
        {
            ...address,
            is_featured: true,
        },
        {
            preserveScroll: true,
        },
    );
};

const featuredAddress = computed(() => props.addresses?.find((a) => a.is_featured));

const dateFormat = { year: "numeric", month: "short", day: "numeric" };
</script>

<template>
    <TemplateWrapper :shell="false" :title="t('labels.account.heading')">
        <PageShell class="relative overflow-hidden">
            <!-- Decorative -->
            <div class="bg-primary/5 absolute -top-20 -left-20 h-80 w-80 rounded-full blur-3xl"></div>
            <div class="bg-primary/5 absolute -right-20 -bottom-40 h-96 w-96 rounded-full blur-3xl"></div>

            <div class="container mx-auto px-4">
                <!-- Breadcrumb -->
                <div class="hidden">
                    <span class="hover:text-foreground cursor-pointer transition-colors" @click="$inertia.get(route('frontend.home'))">{{
                        t("labels.breadcrumb.home")
                    }}</span>
                    <ChevronRight class="h-4 w-4" />
                    <span class="text-foreground font-medium">{{ t("labels.account.heading") }}</span>
                </div>

                <div class="relative z-10 mx-auto grid max-w-7xl grid-cols-1 gap-8 lg:grid-cols-[300px_1fr]">
                    <!-- Sidebar Navigation -->
                    <aside>
                        <div class="space-y-4">
                            <!-- Profile Card -->
                            <Card variant="elevated" class="overflow-hidden rounded-2xl border-0">
                                <!-- Header with Gradient -->
                                <div class="from-primary/10 via-primary/5 bg-gradient-to-r to-transparent p-6">
                                    <div class="flex flex-col items-center text-center">
                                        <div class="relative">
                                            <div
                                                class="bg-secondary flex h-20 w-20 items-center justify-center overflow-hidden rounded-full border-4 border-white shadow-md"
                                            >
                                                <img
                                                    v-if="user.image || user.profile_photo_url"
                                                    :src="user.image || user.profile_photo_url"
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
                                        <p class="text-foreground mt-3 font-bold">{{ user.full_name }}</p>
                                        <p class="text-muted-foreground text-sm">{{ user.email }}</p>
                                    </div>
                                </div>

                                <!-- Menu Items -->
                                <div class="p-4">
                                    <nav class="space-y-1">
                                        <template v-for="item in menuItems" :key="item.id">
                                            <Link
                                                v-if="item.href"
                                                :href="item.href"
                                                class="text-muted-foreground hover:bg-secondary hover:text-foreground flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition-all"
                                            >
                                                <component :is="item.icon" class="h-5 w-5" />
                                                <span>{{ item.name }}</span>
                                            </Link>
                                            <Button
                                                v-else
                                                @click="activeSection = item.id"
                                                
                                                class="flex w-full items-center justify-start gap-3 rounded-xl px-4 py-3 text-sm font-medium transition-all"
                                                :class="
                                                    activeSection === item.id || (activeSection === 'address_form' && item.id === 'addresses')
                                                        ? 'from-primary to-primary/80 text-primary-foreground shadow-primary/20 bg-gradient-to-r shadow-lg'
                                                        : 'text-muted-foreground hover:bg-secondary hover:text-foreground'
                                                "
                                            >
                                                <component :is="item.icon" class="h-5 w-5" />
                                                <span>{{ item.name }}</span>
                                            </Button>
                                        </template>

                                        <Link
                                            :href="route('frontend.logout')"
                                            method="post"
                                            as="button"
                                            class="flex w-full items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-red-500 transition-all hover:bg-red-50"
                                        >
                                            <LogOut class="h-5 w-5" />
                                            <span>{{ t("labels.actions.logout") }}</span>
                                        </Link>
                                    </nav>
                                </div>
                            </Card>

                            <!-- Quick Links -->
                            <div class="overflow-hidden rounded-2xl bg-white p-4 shadow-lg">
                                <h4 class="text-foreground mb-3 text-sm font-semibold">{{ t("labels.account.quick_links") }}</h4>
                                <div class="space-y-2">
                                    <Link
                                        :href="route('frontend.wishlist')"
                                        class="text-muted-foreground hover:bg-secondary hover:text-foreground flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm transition-all"
                                    >
                                        <Heart class="h-4 w-4" />
                                        <span>{{ t("labels.account.wishlist") }}</span>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </aside>

                    <!-- Main Content Area -->
                    <div class="space-y-6">
                        <!-- OVERVIEW SECTION -->
                        <div v-if="activeSection === 'overview'" class="space-y-6">
                            <!-- Stats Grid -->
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div v-if="balanceEnabled" class="group overflow-hidden rounded-2xl bg-white p-5 shadow-lg ring-1 ring-black/5">
                                    <div class="mb-2 flex items-center gap-2">
                                        <div class="bg-primary/10 flex h-9 w-9 items-center justify-center rounded-xl">
                                            <Wallet class="text-primary h-5 w-5" />
                                        </div>
                                        <span class="text-muted-foreground text-xs font-semibold tracking-wider uppercase">{{ t("labels.account.balance") }}</span>
                                    </div>
                                    <h3 class="text-foreground text-3xl font-bold">{{ formatCurrency(user.balance || 0, localeCode) }}</h3>
                                    <p class="text-muted-foreground mt-2 text-xs">{{ t("labels.account.balance_description") }}</p>
                                </div>
                                <div
                                    class="group overflow-hidden rounded-2xl bg-white p-5 shadow-lg ring-1 ring-black/5 transition-all hover:shadow-xl"
                                >
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="mb-2 flex items-center gap-2">
                                                <div class="bg-primary/10 flex h-9 w-9 items-center justify-center rounded-xl">
                                                    <Package class="text-primary h-5 w-5" />
                                                </div>
                                                <span class="text-muted-foreground text-xs font-semibold tracking-wider uppercase">{{
                                                    t("labels.account.total_orders")
                                                }}</span>
                                            </div>
                                            <h3 class="text-foreground text-3xl font-bold">{{ totalOrders || 0 }}</h3>
                                        </div>
                                        <div class="bg-secondary rounded-2xl p-2.5 transition-transform group-hover:scale-105">
                                            <Package class="text-muted-foreground h-7 w-7" />
                                        </div>
                                    </div>
                                    <div class="border-border/60 mt-4 flex items-center justify-between border-t pt-3">
                                        <span class="text-muted-foreground text-xs">{{ t("labels.account.all_time") }}</span>
                                        <Link
                                            :href="route('frontend.orders')"
                                            class="text-primary hover:text-primary/80 inline-flex items-center gap-1 text-xs font-semibold transition-colors outline-none focus:outline-none focus-visible:ring-0 focus-visible:outline-none"
                                        >
                                            {{ t("labels.actions.view_all") }}
                                            <ChevronRight class="h-3 w-3" />
                                        </Link>
                                    </div>
                                </div>

                                <div class="group overflow-hidden rounded-2xl bg-white p-5 shadow-lg transition-all hover:shadow-xl">
                                    <div class="mb-2 flex items-center gap-2">
                                        <div class="bg-primary/10 flex h-9 w-9 items-center justify-center rounded-xl">
                                            <MapPin class="text-primary h-5 w-5" />
                                        </div>
                                        <span class="text-muted-foreground text-xs font-semibold tracking-wider uppercase">{{
                                            t("labels.account.default_address")
                                        }}</span>
                                    </div>
                                    <div v-if="featuredAddress" class="space-y-2">
                                        <div class="flex items-center gap-2">
                                            <h4 class="text-foreground font-bold">{{ featuredAddress.name }}</h4>
                                            <span class="bg-primary/10 text-primary rounded-full px-2 py-0.5 text-[10px] font-bold">{{
                                                t("labels.address.default_badge")
                                            }}</span>
                                        </div>
                                        <p class="text-muted-foreground line-clamp-2 text-sm">
                                            {{ featuredAddress.address }}, {{ featuredAddress.village_name }}, {{ featuredAddress.sub_district_name }}
                                        </p>
                                    </div>
                                    <div v-else class="py-2">
                                        <p class="text-muted-foreground mb-3 text-sm">{{ t("labels.address.no_default") }}</p>
                                    </div>
                                    <Button
                                        @click="activeSection = 'addresses'"
                                        class="text-primary hover:text-primary/80 mt-4 inline-flex items-center gap-1 text-xs font-semibold transition-colors"
                                    >
                                        <Edit3 class="h-3 w-3" />
                                        {{ t("labels.actions.manage_addresses") }}
                                    </Button>
                                </div>
                            </div>

                            <div v-if="balanceEnabled" class="overflow-hidden rounded-2xl bg-white p-6 shadow-lg">
                                <h3 class="text-foreground mb-4 flex items-center gap-2 text-lg font-bold">
                                    <Wallet class="text-primary h-5 w-5" />
                                    {{ t("labels.account.balance_history") }}
                                </h3>
                                <div v-if="balanceHistory?.length" class="space-y-3">
                                    <div v-for="entry in balanceHistory" :key="entry.id" class="border-border flex items-center justify-between border-b pb-3 last:border-0 last:pb-0">
                                        <div>
                                            <p class="text-foreground text-sm font-medium">{{ entry.notes }}</p>
                                            <p class="text-muted-foreground text-xs">{{ formatDate(entry.created_at, dateFormat, localeCode) }}</p>
                                        </div>
                                        <span :class="entry.trx_type === '+' ? 'text-emerald-600' : 'text-red-600'" class="text-sm font-semibold">
                                            {{ entry.trx_type === '+' ? '+' : '-' }}{{ formatCurrency(entry.amount, localeCode) }}
                                        </span>
                                    </div>
                                </div>
                                <p v-else class="text-muted-foreground text-sm">{{ t("labels.account.balance_history_empty") }}</p>
                            </div>

                            <!-- Recent Activity -->
                            <div class="overflow-hidden rounded-2xl bg-white p-6 shadow-lg">
                                <h3 class="text-foreground mb-6 flex items-center gap-2 text-lg font-bold">
                                    <Clock class="text-primary h-5 w-5" />
                                    {{ t("labels.account.recent_activity") }}
                                </h3>
                                <div v-if="recentOrders && recentOrders.length > 0" class="space-y-3">
                                    <Link
                                        v-for="order in recentOrders"
                                        :key="order.id"
                                        :href="route('frontend.orders.show', order.id)"
                                        class="group border-border hover:bg-secondary/40 flex items-center justify-between gap-4 rounded-xl border bg-white px-4 py-3 transition-all"
                                    >
                                        <div class="min-w-0">
                                            <p class="text-foreground truncate text-sm font-semibold">
                                                {{ t("labels.order.order_number", { id: order.code }) }}
                                            </p>
                                            <p class="text-muted-foreground mt-0.5 text-xs">
                                                {{ formatDate(order.created_at, dateFormat, localeCode) }}
                                            </p>
                                        </div>
                                        <div class="flex flex-shrink-0 items-center gap-3">
                                            <span
                                                class="rounded-full px-2.5 py-0.5 text-[10px] leading-none font-medium"
                                                :class="getOrderStatusColor(order.status)"
                                            >
                                                {{ getOrderStatusLabel(order.status, t) }}
                                            </span>
                                            <p class="text-primary text-sm font-bold">{{ formatCurrency(order.total, localeCode) }}</p>
                                            <ChevronRight class="text-muted-foreground group-hover:text-foreground h-4 w-4 transition-colors" />
                                        </div>
                                    </Link>
                                    <Link
                                        :href="route('frontend.orders')"
                                        class="text-primary hover:text-primary/80 inline-flex items-center gap-2 text-sm font-semibold transition-colors"
                                    >
                                        {{ t("labels.account.menu.orders") }}
                                        <ChevronRight class="h-4 w-4" />
                                    </Link>
                                </div>
                                <div v-else class="flex flex-col items-center justify-center py-12 text-center">
                                    <div
                                        class="from-secondary to-secondary/50 mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br shadow-inner"
                                    >
                                        <Package class="text-muted-foreground h-10 w-10" />
                                    </div>
                                    <p class="text-foreground mb-2 font-semibold">{{ t("labels.account.no_activity") }}</p>
                                    <p class="text-muted-foreground text-sm">{{ t("labels.account.no_activity_description") }}</p>
                                    <Link
                                        :href="route('frontend.products')"
                                        class="from-primary to-primary/90 text-primary-foreground shadow-primary/30 hover:shadow-primary/40 mt-6 inline-flex items-center gap-2 rounded-xl bg-gradient-to-r px-6 py-3 text-sm font-semibold shadow-lg transition-all hover:shadow-xl"
                                    >
                                        {{ t("labels.actions.start_shopping") }}
                                        <ChevronRight class="h-4 w-4" />
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- SETTINGS SECTION -->
                        <div v-if="activeSection === 'settings'" class="overflow-hidden rounded-2xl bg-white p-6 shadow-xl md:p-8">
                            <div class="mb-8">
                                <h2 class="text-foreground text-xl font-bold">{{ t("labels.account.settings_heading") }}</h2>
                                <p class="text-muted-foreground mt-1 text-sm">{{ t("labels.account.settings_description") }}</p>
                            </div>

                            <form @submit.prevent="submitProfile" class="space-y-8">
                                <!-- Avatar Section -->
                                <div class="flex flex-col items-center gap-4 md:items-start">
                                    <div class="group relative">
                                        <div
                                            class="from-secondary to-secondary/50 flex h-28 w-28 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br shadow-inner"
                                        >
                                            <img
                                                v-if="imagePreview || user.image || user.profile_photo_url"
                                                :src="imagePreview || user.image || user.profile_photo_url"
                                                class="h-full w-full object-cover"
                                            />
                                            <User v-else class="text-muted-foreground h-12 w-12" />
                                        </div>
                                        <Button
                                            type="button"
                                            @click="$refs.fileInput.click()"
                                            class="absolute inset-0 flex items-center justify-center rounded-full bg-gradient-to-t from-black/60 to-transparent opacity-0 transition-opacity group-hover:opacity-100"
                                        >
                                            <Camera class="h-8 w-8 text-white" />
                                        </Button>
                                        <FormFile ref="fileInput" class="hidden" accept="image/*" @change="handleImageChange" />
                                    </div>
                                    <div class="text-center md:text-left">
                                        <p class="text-foreground text-sm font-semibold">{{ t("labels.account.profile_photo") }}</p>
                                        <p class="text-muted-foreground text-xs">{{ t("labels.account.photo_requirements") }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div class="space-y-2">
                                        <label class="text-foreground text-sm font-semibold">{{ t("labels.form.first_name") }}</label>
                                        <FormInput v-model="profileForm.first_name" type="text" />
                                        <p v-if="profileForm.errors.first_name" class="text-xs text-red-500">{{ profileForm.errors.first_name }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-foreground text-sm font-semibold">{{ t("labels.form.last_name") }}</label>
                                        <FormInput v-model="profileForm.last_name" type="text" />
                                        <p v-if="profileForm.errors.last_name" class="text-xs text-red-500">{{ profileForm.errors.last_name }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-foreground text-sm font-semibold">{{ t("labels.form.email") }}</label>
                                        <FormInput v-model="profileForm.email" type="email" />
                                        <p v-if="profileForm.errors.email" class="text-xs text-red-500">{{ profileForm.errors.email }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-foreground text-sm font-semibold">{{ t("labels.form.phone") }}</label>
                                        <FormInput v-model="profileForm.phone" type="text" :placeholder="t('placeholders.phone')" />
                                        <p v-if="profileForm.errors.phone" class="text-xs text-red-500">{{ profileForm.errors.phone }}</p>
                                    </div>
                                </div>

                                <div class="border-border flex flex-col gap-3 border-t pt-6 sm:flex-row">
                                    <Button
                                        type="submit"
                                        :disabled="profileForm.processing"
                                        class="from-primary to-primary/90 text-primary-foreground shadow-primary/30 hover:shadow-primary/40 inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r px-8 py-3 text-sm font-bold shadow-lg transition-all hover:shadow-xl active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:shadow-lg"
                                    >
                                        <Save class="h-4 w-4" />
                                        <span>{{ profileForm.processing ? t("labels.actions.saving") : t("labels.actions.save") }}</span>
                                    </Button>
                                    <Button
                                        type="button"
                                        @click="activeSection = 'overview'"
                                        class="border-border text-foreground hover:bg-secondary inline-flex items-center justify-center gap-2 rounded-xl border bg-white px-8 py-3 text-sm font-semibold shadow-sm transition-all"
                                    >
                                        {{ t("labels.actions.cancel") }}
                                    </Button>
                                </div>
                            </form>
                        </div>

                        <!-- ADDRESSES LIST SECTION -->
                        <div v-if="activeSection === 'addresses'" class="space-y-6">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h2 class="text-foreground text-xl font-bold">{{ t("labels.address.heading") }}</h2>
                                    <p class="text-muted-foreground mt-1 text-sm">{{ t("labels.address.description") }}</p>
                                </div>
                                <p class="bg-secondary/60 text-muted-foreground rounded-xl px-4 py-2 text-xs font-semibold">
                                    {{ t("labels.address.managed_by_admin") }}
                                </p>
                            </div>

                            <div class="grid gap-4">
                                <div
                                    v-for="address in addresses || []"
                                    :key="address.id"
                                    class="group overflow-hidden rounded-2xl bg-white p-6 shadow-lg transition-all hover:shadow-xl"
                                    :class="address.is_featured ? 'ring-primary ring-2' : ''"
                                >
                                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                        <div class="space-y-2">
                                            <div class="flex items-center gap-2">
                                                <h3 class="text-foreground font-bold">{{ address.name }}</h3>
                                                <span
                                                    v-if="address.is_featured"
                                                    class="from-primary to-primary/80 text-primary-foreground rounded-full bg-gradient-to-r px-3 py-1 text-xs font-bold shadow-sm"
                                                    >{{ t("labels.address.default_badge") }}</span
                                                >
                                            </div>
                                            <p class="text-primary text-sm font-medium">{{ address.phone }}</p>
                                            <p class="text-muted-foreground text-sm">
                                                {{ address.address }}<br />
                                                {{ address.village_name }}, {{ address.sub_district_name }}, {{ address.district_name }}<br />
                                                {{ address.province_name }} {{ address.postal_code }}
                                            </p>
                                        </div>

                                        <div class="bg-secondary/40 text-muted-foreground rounded-xl px-3 py-2 text-xs font-semibold">
                                            {{ t("labels.address.synced_from_school_unit") }}
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-if="!addresses || addresses.length === 0"
                                    class="border-border relative overflow-hidden rounded-3xl border-2 border-dashed bg-gradient-to-br from-slate-50 to-slate-100 py-16 text-center"
                                >
                                    <div class="bg-primary/5 absolute -top-10 -right-10 h-40 w-40 rounded-full"></div>
                                    <div class="bg-primary/5 absolute -bottom-10 -left-10 h-32 w-32 rounded-full"></div>

                                    <div class="relative z-10">
                                        <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-white shadow-lg">
                                            <MapPin class="text-muted-foreground h-10 w-10" />
                                        </div>
                                        <p class="text-muted-foreground mb-6 text-sm font-medium">{{ t("labels.address.empty") }}</p>
                                        <p class="text-muted-foreground text-sm font-medium">{{ t("labels.address.awaiting_school_unit") }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </PageShell>
    </TemplateWrapper>
</template>
