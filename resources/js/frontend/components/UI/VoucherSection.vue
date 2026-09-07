<script setup>
import { computed, ref, onMounted } from "vue";
import { Link } from "@inertiajs/vue3";
import VoucherCard from "./VoucherCard.vue";
import voucherService from "../../services/voucherService";
import { ChevronRight } from "lucide-vue-next";
import { useI18n } from "vue-i18n";
import { getSectionContent } from "../../lib/utils";

const props = defineProps({
    template: { type: Object, default: null },
});

const vouchers = ref([]);
const isLoading = ref(true);
const { t } = useI18n();
const title = computed(() => getSectionContent(props.template, "vouchers", "title", t("labels.voucher.available_title")));
const subtitle = computed(() => getSectionContent(props.template, "vouchers", "subtitle", t("labels.voucher.available_subtitle")));
const limit = computed(() => Number(getSectionContent(props.template, "vouchers", "limit", 4)) || 4);

onMounted(async () => {
    try {
        const response = await voucherService.getVouchers();
        vouchers.value = (response.data || []).slice(0, limit.value);
    } catch (error) {
        console.error("Failed to load vouchers:", error);
    } finally {
        isLoading.value = false;
    }
});
</script>

<template>
    <section v-if="vouchers.length > 0" class="py-6 sm:py-8 md:py-10">
        <div class="container mx-auto px-4">
            <!-- Header -->
            <div class="mb-4 flex items-center justify-between gap-3 sm:mb-5">
                <div>
                    <h2 class="text-lg font-bold text-foreground sm:text-xl md:text-2xl">{{ title }}</h2>
                    <p v-if="subtitle" class="mt-1 text-sm text-muted-foreground">{{ subtitle }}</p>
                </div>
                <Link
                    :href="route('frontend.vouchers')"
                    class="inline-flex min-h-10 shrink-0 items-center gap-1 rounded-lg px-1 text-xs font-semibold text-primary transition-colors hover:text-primary/80 sm:px-2 sm:text-sm"
                >
                    <span>Lihat Semua</span>
                    <ChevronRight class="h-4 w-4" />
                </Link>
            </div>

            <!-- Voucher Grid -->
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-4">
                <VoucherCard
                    v-for="voucher in vouchers"
                    :key="voucher.id"
                    :voucher="voucher"
                    variant="compact"
                    :show-apply-button="true"
                    :show-copy-button="true"
                />
            </div>
        </div>
    </section>
</template>
