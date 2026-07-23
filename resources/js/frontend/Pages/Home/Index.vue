<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import TemplateWrapper from "../../components/TemplateWrapper.vue";
import HeroSection from "../../components/UI/HeroSection.vue";
import HeroCarousel from "../../components/UI/HeroCarousel.vue";
import StoreStorySection from "../../components/UI/StoreStorySection.vue";
import FlashSaleSection from "../../components/UI/FlashSaleSection.vue";
import FeaturedProducts from "../../components/UI/FeaturedProducts.vue";
import CategoryMenu from "../../components/UI/CategoryMenu.vue";
import VoucherSection from "../../components/UI/VoucherSection.vue";
import HomeProductsSection from "../../components/UI/HomeProductsSection.vue";
import NewsletterSection from "../../components/UI/NewsletterSection.vue";

const props = defineProps({
    products: { type: Object, default: () => ({ data: [] }) },
    categories: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    template: { type: Object, default: null },
    sliders: { type: Object, default: () => ({ data: [] }) },
    flashsales: { type: Object, default: null },
});

const page = usePage();
const settings = computed(() => page.props.settings ?? {});
const colorScheme = computed(() => page.props.colorScheme);
</script>

<template>
    <TemplateWrapper
        :shell="false"
        :title="settings.social_title || settings.site_name"
        :description="settings.site_description"
        :keywords="settings.site_keywords"
        :social-image="settings.social_image"
        :color-scheme="colorScheme"
    >
        <HeroSection :template="template" />
        <CategoryMenu :categories="categories" :active-category="filters?.category" />
        <HomeProductsSection :products="products" :filters="filters" :template="template" />
        <StoreStorySection :template="template" :categories="categories" />
        <HeroCarousel :template="template" :slides="sliders" />
        <FeaturedProducts v-if="!filters?.category" :products="products" :template="template" />
        <FlashSaleSection v-if="flashsales" :flashsales="flashsales" :template="template" />
        <VoucherSection :template="template" />
        <NewsletterSection :template="template" />
    </TemplateWrapper>
</template>
