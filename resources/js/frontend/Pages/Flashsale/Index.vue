<script setup>
import { Head, Link } from "@inertiajs/vue3";
import TemplateWrapper from "../../components/TemplateWrapper.vue";
import FlashSaleCard from "../../components/UI/FlashSaleCard.vue";

const props = defineProps({
    flashsale: Object,
});
</script>

<template>
    <Head :title="flashsale?.name || 'Flash Sale'" />

    <TemplateWrapper>
        <section class="relative overflow-hidden px-4 py-10">
            <div class="mx-auto max-w-7xl">
                <div class="mb-8 rounded-3xl bg-gradient-to-r from-red-500 via-orange-500 to-amber-400 p-6 text-white shadow-xl">
                    <p class="mb-2 text-sm font-semibold uppercase tracking-[0.3em]">Flash Sale</p>
                    <h1 class="text-3xl font-black md:text-4xl">{{ flashsale?.name }}</h1>
                    <p v-if="flashsale?.description" class="mt-3 max-w-2xl text-sm text-white/90 md:text-base">
                        {{ flashsale.description }}
                    </p>
                    <div class="mt-5">
                        <Link
                            :href="route('frontend.home')"
                            class="inline-flex items-center rounded-xl bg-white/15 px-4 py-2 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/25"
                        >
                            Kembali ke beranda
                        </Link>
                    </div>
                </div>

                <div v-if="flashsale?.products?.length" class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
                    <FlashSaleCard
                        v-for="item in flashsale.products"
                        :key="item.id"
                        :product-data="item"
                    />
                </div>

                <div
                    v-else
                    class="rounded-3xl border border-dashed border-red-200 bg-red-50 px-6 py-12 text-center text-sm text-red-700"
                >
                    Belum ada produk flash sale yang sedang berjalan.
                </div>
            </div>
        </section>
    </TemplateWrapper>
</template>
