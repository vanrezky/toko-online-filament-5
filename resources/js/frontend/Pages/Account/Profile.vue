<script setup>
import Button from "@frontend/components/UI/Button.vue";
import { ref, computed, watch, getCurrentInstance } from "vue";
import { Link, useForm, router, usePage } from "@inertiajs/vue3";
import TemplateWrapper from "../../components/TemplateWrapper.vue";
import PageShell from "../../components/PageShell.vue";
import FormInput from "../../components/UI/FormInput.vue";
import AccountShell from "../../components/Account/AccountShell.vue";
import AccountProfileSettings from "../../components/Account/AccountProfileSettings.vue";
import AccountPasswordForm from "../../components/Account/AccountPasswordForm.vue";
import { formatCurrency, formatDate } from "../../lib/utils";
import { getOrderStatusColor, getOrderStatusLabel } from "../../lib/order-status";
import {
    Package,
    MapPin,
    Settings,
    ChevronRight,
    X,
    Plus,
    Trash2,
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
const isPrivateStore = computed(() => Boolean(page.props.settings?.is_private_store));
const canManageAddress = (address) => !isPrivateStore.value && address.can_customer_manage;

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

const editingAddress = ref(null);

const districts = ref([]);
const subDistricts = ref([]);
const villages = ref([]);

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
    const village = villages.value.find((v) => String(v.id) === String(addressForm.village_id));
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

                <AccountShell :user="user" :active-destination="activeSection">
                        <header class="space-y-1">
                            <h1 class="text-2xl font-bold text-foreground">{{ t("labels.account.heading") }}</h1>
                            <p class="text-sm text-muted-foreground">{{ t("labels.account.settings_description") }}</p>
                        </header>
                        <!-- OVERVIEW SECTION -->
                        <div v-if="activeSection === 'overview'" class="space-y-6">
                            <!-- Stats Grid -->
                            <div class="grid items-stretch grid-cols-1 gap-4 sm:grid-cols-2">
                                <div v-if="balanceEnabled" class="flex h-full flex-col rounded-2xl border border-border bg-background p-5 shadow-sm transition-shadow hover:shadow-md">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <div class="mb-2 flex items-center gap-2">
                                                <div class="bg-primary/10 flex h-9 w-9 items-center justify-center rounded-xl"><Wallet class="text-primary h-5 w-5" /></div>
                                                <span class="text-muted-foreground text-xs font-semibold tracking-wider uppercase">{{ t("labels.account.balance") }}</span>
                                            </div>
                                            <h3 class="text-foreground text-3xl font-bold">{{ formatCurrency(user.balance || 0, localeCode) }}</h3>
                                        </div>
                                    </div>
                                    <div class="border-border/60 mt-4 border-t pt-3"><p class="text-muted-foreground line-clamp-1 text-xs">{{ t("labels.account.balance_description") }}</p></div>
                                </div>
                                <div
                                    class="flex h-full flex-col rounded-2xl border border-border bg-background p-5 shadow-sm transition-shadow hover:shadow-md"
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
                                    </div>
                                    <div class="border-border/60 mt-auto flex items-center justify-between border-t pt-3">
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

                                <div class="flex h-full flex-col rounded-2xl border border-border bg-background p-5 shadow-sm transition-shadow hover:shadow-md">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <div class="mb-2 flex items-center gap-2">
                                                <div class="bg-primary/10 flex h-9 w-9 items-center justify-center rounded-xl"><MapPin class="text-primary h-5 w-5" /></div>
                                                <span class="text-muted-foreground text-xs font-semibold tracking-wider uppercase">{{ t("labels.account.default_address") }}</span>
                                            </div>
                                            <div v-if="featuredAddress" class="space-y-2">
                                                <div class="flex items-center gap-2"><h4 class="text-foreground truncate font-bold">{{ featuredAddress.name }}</h4><span class="bg-primary/10 text-primary shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold">{{ t("labels.address.default_badge") }}</span></div>
                                                <p class="text-muted-foreground line-clamp-2 text-sm">{{ featuredAddress.address }}, {{ featuredAddress.village_name }}, {{ featuredAddress.sub_district_name }}</p>
                                            </div>
                                            <p v-else class="text-muted-foreground py-2 text-sm">{{ t("labels.address.no_default") }}</p>
                                        </div>
                                    </div>
                                    <div class="border-border/60 mt-auto flex items-center justify-between border-t pt-3">
                                        <span class="text-muted-foreground text-xs">{{ featuredAddress ? t("labels.address.default_badge") : t("labels.address.no_default") }}</span>
                                        <Button @click="activeSection = 'addresses'" class="text-primary hover:text-primary/80 inline-flex items-center gap-1 text-xs font-semibold transition-colors"><Edit3 class="h-3 w-3" />{{ t("labels.actions.manage_addresses") }}</Button>
                                    </div>
                                </div>
                            </div>

                            <div v-if="balanceEnabled" class="rounded-2xl border border-border bg-background p-6 shadow-sm">
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
                            <div class="rounded-2xl border border-border bg-background p-6 shadow-sm">
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

                        <AccountProfileSettings v-if="activeSection === 'settings'" :user="user" @saved="activeSection = 'overview'" @cancel="activeSection = 'overview'" />
                        <AccountPasswordForm v-if="activeSection === 'password'" />

                        <div v-if="activeSection === 'addresses'" class="space-y-6">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h2 class="text-foreground text-xl font-bold">{{ t("labels.address.heading") }}</h2>
                                    <p class="text-muted-foreground mt-1 text-sm">{{ t("labels.address.description") }}</p>
                                </div>
                                <Button v-if="!isPrivateStore" variant="primary" :icon="Plus" @click="openAddressForm()">
                                    {{ t("labels.address.add") }}
                                </Button>
                            </div>

                            <p class="bg-secondary/60 text-muted-foreground rounded-xl px-4 py-3 text-xs font-semibold">
                                {{ t(isPrivateStore ? "labels.address.private_store_note" : "labels.address.public_store_note") }}
                            </p>

                            <div class="grid gap-4">
                                <div
                                    v-for="address in addresses || []"
                                    :key="address.id"
                                    class="rounded-2xl border border-border bg-background p-6 shadow-sm transition-shadow hover:shadow-md"
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

                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="bg-secondary/40 text-muted-foreground rounded-xl px-3 py-2 text-xs font-semibold">
                                                {{
                                                    address.source_type === "school_unit"
                                                        ? t("labels.address.synced_from_admin")
                                                        : address.can_customer_manage
                                                          ? t("labels.address.customer_created")
                                                          : t("labels.address.managed_by_admin")
                                                }}
                                            </span>
                                            <template v-if="canManageAddress(address)">
                                                <Button
                                                    v-if="!address.is_featured"
                                                    variant="outline"
                                                    size="sm"
                                                    @click="setFeaturedAddress(address)"
                                                >
                                                    {{ t("labels.address.set_default") }}
                                                </Button>
                                                <Button variant="outline" size="icon" :aria-label="t('labels.address.edit')" @click="openAddressForm(address)">
                                                    <Edit3 class="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="destructive"
                                                    size="icon"
                                                    :aria-label="t('labels.address.delete')"
                                                    @click="deleteAddress(address.id, address.name)"
                                                >
                                                    <Trash2 class="h-4 w-4" />
                                                </Button>
                                            </template>
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
                                        <p class="text-muted-foreground text-sm font-medium">
                                            {{ t(isPrivateStore ? "labels.address.awaiting_school_unit" : "labels.address.public_store_empty") }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="activeSection === 'address_form' && !isPrivateStore" class="space-y-6">
                            <div>
                                <h2 class="text-foreground text-xl font-bold">
                                    {{ editingAddress ? t("labels.address.edit_heading") : t("labels.address.new_heading") }}
                                </h2>
                                <p class="text-muted-foreground mt-1 text-sm">
                                    {{ editingAddress ? t("labels.address.edit_description") : t("labels.address.new_description") }}
                                </p>
                            </div>

                            <form class="space-y-5 rounded-2xl border border-border bg-background p-6 shadow-sm" @submit.prevent="submitAddress">
                                <div class="grid gap-4 md:grid-cols-2">
                                    <label class="space-y-2 text-sm font-semibold text-foreground">
                                        {{ t("labels.address.fields.name") }}
                                        <FormInput v-model="addressForm.name" :invalid="Boolean(addressForm.errors.name)" />
                                        <span v-if="addressForm.errors.name" class="text-xs font-medium text-destructive">{{ addressForm.errors.name }}</span>
                                    </label>
                                    <label class="space-y-2 text-sm font-semibold text-foreground">
                                        {{ t("labels.address.fields.phone") }}
                                        <FormInput v-model="addressForm.phone" :invalid="Boolean(addressForm.errors.phone)" />
                                        <span v-if="addressForm.errors.phone" class="text-xs font-medium text-destructive">{{ addressForm.errors.phone }}</span>
                                    </label>
                                </div>

                                <label class="block space-y-2 text-sm font-semibold text-foreground">
                                    {{ t("labels.address.fields.address") }}
                                    <textarea
                                        v-model="addressForm.address"
                                        rows="3"
                                        class="border-border focus:border-primary focus:ring-primary/25 w-full rounded-xl border bg-secondary px-4 py-3 text-sm outline-none focus:ring-2"
                                        :aria-invalid="Boolean(addressForm.errors.address)"
                                    />
                                    <span v-if="addressForm.errors.address" class="text-xs font-medium text-destructive">{{ addressForm.errors.address }}</span>
                                </label>

                                <div class="grid gap-4 md:grid-cols-2">
                                    <label class="space-y-2 text-sm font-semibold text-foreground">
                                        {{ t("labels.address.fields.province") }}
                                        <select v-model="addressForm.province_id" class="border-border w-full rounded-xl border bg-secondary px-4 py-3 text-sm" @change="fetchDistricts(addressForm.province_id)">
                                            <option value="">{{ t("placeholders.select_province") }}</option>
                                            <option v-for="province in provinces" :key="province.id" :value="province.id">{{ province.name }}</option>
                                        </select>
                                        <span v-if="addressForm.errors.province_id" class="text-xs font-medium text-destructive">{{ addressForm.errors.province_id }}</span>
                                    </label>
                                    <label class="space-y-2 text-sm font-semibold text-foreground">
                                        {{ t("labels.address.fields.district") }}
                                        <select v-model="addressForm.district_id" class="border-border w-full rounded-xl border bg-secondary px-4 py-3 text-sm" @change="fetchSubDistricts(addressForm.district_id)">
                                            <option value="">{{ t("placeholders.select_district") }}</option>
                                            <option v-for="district in districts" :key="district.id" :value="district.id">{{ district.name }}</option>
                                        </select>
                                        <span v-if="addressForm.errors.district_id" class="text-xs font-medium text-destructive">{{ addressForm.errors.district_id }}</span>
                                    </label>
                                    <label class="space-y-2 text-sm font-semibold text-foreground">
                                        {{ t("labels.address.fields.sub_district") }}
                                        <select v-model="addressForm.sub_district_id" class="border-border w-full rounded-xl border bg-secondary px-4 py-3 text-sm" @change="fetchVillages(addressForm.sub_district_id)">
                                            <option value="">{{ t("placeholders.select_sub_district") }}</option>
                                            <option v-for="subDistrict in subDistricts" :key="subDistrict.id" :value="subDistrict.id">{{ subDistrict.name }}</option>
                                        </select>
                                        <span v-if="addressForm.errors.sub_district_id" class="text-xs font-medium text-destructive">{{ addressForm.errors.sub_district_id }}</span>
                                    </label>
                                    <label class="space-y-2 text-sm font-semibold text-foreground">
                                        {{ t("labels.address.fields.village") }}
                                        <select v-model="addressForm.village_id" class="border-border w-full rounded-xl border bg-secondary px-4 py-3 text-sm" @change="onVillageChange">
                                            <option value="">{{ t("placeholders.select_village") }}</option>
                                            <option v-for="village in villages" :key="village.id" :value="village.id">{{ village.name }}</option>
                                        </select>
                                        <span v-if="addressForm.errors.village_id" class="text-xs font-medium text-destructive">{{ addressForm.errors.village_id }}</span>
                                    </label>
                                </div>

                                <label class="block space-y-2 text-sm font-semibold text-foreground">
                                    {{ t("labels.address.fields.postal_code") }}
                                    <FormInput v-model="addressForm.postal_code" :invalid="Boolean(addressForm.errors.postal_code)" />
                                    <span v-if="addressForm.errors.postal_code" class="text-xs font-medium text-destructive">{{ addressForm.errors.postal_code }}</span>
                                </label>

                                <label class="flex items-center gap-3 text-sm font-semibold text-foreground">
                                    <input v-model="addressForm.is_featured" type="checkbox" class="h-4 w-4 rounded border-border text-primary focus:ring-primary" />
                                    {{ t("labels.address.set_default") }}
                                </label>

                                <div class="flex flex-wrap justify-end gap-3">
                                    <Button variant="outline" @click="activeSection = 'addresses'">{{ t("labels.actions.cancel") }}</Button>
                                    <Button type="submit" variant="primary" :loading="addressForm.processing">{{ t("labels.actions.save") }}</Button>
                                </div>
                            </form>
                        </div>
                </AccountShell>
            </div>
        </PageShell>
    </TemplateWrapper>
</template>
