<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import { Facebook, Instagram, Mail, MapPin, MessageCircle, Phone, Twitter } from "lucide-vue-next";
import { useI18n } from "vue-i18n";
import PromotionBanner from "../../UI/PromotionBanner.vue";

const { t } = useI18n();
const page = usePage();
const settings = computed(() => page.props.settings ?? {});
const currentYear = new Date().getFullYear();
const logoError = ref(false);

watch(() => settings.value.logo, () => {
    logoError.value = false;
});

const footerPromos = computed(() => {
    const promos = page.props.promotions;
    return (Array.isArray(promos) ? promos : promos?.data || []).filter((promo) => promo.position === "footer");
});

const socialLinks = computed(() => [
    { key: "instagram", label: "Instagram", icon: Instagram, href: settings.value.instagram },
    { key: "facebook", label: "Facebook", icon: Facebook, href: settings.value.facebook },
    { key: "twitter", label: "X", icon: Twitter, href: settings.value.twitter },
].filter((link) => link.href));

const shoppingLinks = computed(() => [
    { name: t("labels.footer.all_products"), href: route("frontend.products") },
    { name: t("labels.footer.categories"), href: route("frontend.products") },
    { name: t("labels.footer.promotions"), href: route("frontend.flashsales") },
    { name: t("labels.footer.best_sellers"), href: route("frontend.products") },
    { name: t("labels.footer.new_products"), href: route("frontend.products") },
]);

const helpLinks = computed(() => [
    { name: t("labels.footer.help_center"), href: route("frontend.contact") },
    { name: t("labels.footer.how_to_shop"), href: route("frontend.faq") },
    { name: t("labels.footer.shipping_info"), href: route("frontend.products") },
    { name: t("labels.footer.returns_exchanges"), href: route("frontend.faq") },
    { name: t("labels.footer.privacy_policy"), href: route("frontend.page.show", "privacy-policy") },
    { name: t("labels.footer.terms_conditions"), href: route("frontend.page.show", "terms-and-conditions") },
]);

const aboutLinks = computed(() => {
    const links = [...(page.props.menu?.footer || [])].map((link) => ({ name: link.name, href: link.href }));
    const additions = [
        { name: t("labels.footer.blog"), href: route("frontend.blog.index") },
        { name: t("labels.footer.contact_us"), href: route("frontend.contact") },
    ];

    return [...links, ...additions].filter((link, index, collection) => collection.findIndex((item) => item.name === link.name) === index);
});

const whatsappHref = computed(() => {
    const digits = String(settings.value.wa_phone || "").replace(/\D/g, "");
    return digits ? `https://wa.me/${digits}` : "";
});

const paymentMethods = [
    { key: "bca", name: "BCA", src: "/assets/images/payment/bca.svg" },
    { key: "bri", name: "BRI", src: "/assets/images/payment/bri.svg" },
    { key: "mandiri", name: "Mandiri", src: "/assets/images/payment/mandiri.svg" },
    { key: "bni", name: "BNI", src: "/assets/images/payment/bni.svg" },
    { key: "dana", name: "DANA", src: "/assets/images/payment/dana.svg" },
    { key: "ovo", name: "OVO", src: "/assets/images/payment/ovo.svg" },
    { key: "gopay", name: "GoPay", src: "/assets/images/payment/gopay.svg" },
    { key: "visa", name: "Visa", src: "/assets/images/payment/visa.svg" },
    { key: "mastercard", name: "Mastercard", src: "/assets/images/payment/mastercard.svg" },
];
</script>

<template>
    <footer class="border-border bg-secondary/20 border-t pb-8 pt-10 md:pt-14">
        <div v-if="footerPromos.length > 0" class="container mx-auto mb-10 px-4 md:px-8">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <PromotionBanner v-for="promo in footerPromos" :key="promo.id" :promotion="promo" class="aspect-[16/7] md:aspect-[21/9]" />
            </div>
        </div>

        <div class="container mx-auto px-4 md:px-8">
            <div class="grid grid-cols-1 gap-10 md:grid-cols-2 md:gap-x-8 md:gap-y-12 lg:grid-cols-[1.35fr_.8fr_.8fr_.8fr_1.25fr]">
                <div class="space-y-5">
                    <Link :href="route('frontend.home')" class="inline-flex items-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30">
                        <img v-if="settings.logo && !logoError" :src="settings.logo" :alt="settings.site_name" class="h-11 w-auto max-w-[12rem] object-contain" @error="logoError = true" />
                        <span v-else class="font-display text-primary text-3xl leading-none">{{ settings.site_name }}</span>
                    </Link>
                    <p class="text-muted-foreground max-w-xs text-sm leading-6">{{ settings.site_description }}</p>
                    <div v-if="socialLinks.length" class="flex items-center gap-3 pt-1">
                        <a
                            v-for="social in socialLinks"
                            :key="social.key"
                            :href="social.href"
                            :aria-label="social.label"
                            target="_blank"
                            rel="noreferrer"
                            class="text-muted-foreground focus-visible:ring-primary rounded-full p-1 transition-colors hover:text-primary focus-visible:outline-none focus-visible:ring-2"
                        >
                            <component :is="social.icon" class="h-[18px] w-[18px]" aria-hidden="true" />
                        </a>
                    </div>
                </div>

                <div>
                    <h2 class="text-foreground mb-5 text-sm font-bold">{{ t("labels.footer.shopping") }}</h2>
                    <ul class="space-y-3">
                        <li v-for="link in shoppingLinks" :key="link.name">
                            <Link :href="link.href" class="text-muted-foreground focus-visible:ring-primary rounded-sm text-sm transition-colors hover:text-primary focus-visible:outline-none focus-visible:ring-2">{{ link.name }}</Link>
                        </li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-foreground mb-5 text-sm font-bold">{{ t("labels.footer.help") }}</h2>
                    <ul class="space-y-3">
                        <li v-for="link in helpLinks" :key="link.name">
                            <Link :href="link.href" class="text-muted-foreground focus-visible:ring-primary rounded-sm text-sm transition-colors hover:text-primary focus-visible:outline-none focus-visible:ring-2">{{ link.name }}</Link>
                        </li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-foreground mb-5 text-sm font-bold">{{ t("labels.footer.about_us") }}</h2>
                    <ul class="space-y-3">
                        <li v-for="link in aboutLinks" :key="link.name">
                            <Link :href="link.href" class="text-muted-foreground focus-visible:ring-primary rounded-sm text-sm transition-colors hover:text-primary focus-visible:outline-none focus-visible:ring-2">{{ link.name }}</Link>
                        </li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-foreground mb-5 text-sm font-bold">{{ t("labels.footer.contact_us") }}</h2>
                    <div class="space-y-3.5">
                        <a v-if="whatsappHref" :href="whatsappHref" target="_blank" rel="noreferrer" class="flex items-start gap-3 text-sm transition-colors hover:text-primary">
                            <MessageCircle class="text-primary mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
                            <span><span class="text-foreground block font-medium">{{ t("labels.footer.live_chat") }}</span><span class="text-muted-foreground block text-xs">{{ t("labels.footer.live_chat_hours") }}</span></span>
                        </a>
                        <a v-if="settings.email" :href="`mailto:${settings.email}`" class="flex items-start gap-3 text-sm transition-colors hover:text-primary">
                            <Mail class="text-primary mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
                            <span><span class="text-foreground block font-medium">{{ settings.email }}</span><span class="text-muted-foreground block text-xs">{{ t("labels.footer.email_response") }}</span></span>
                        </a>
                        <a v-if="settings.phone" :href="`tel:${settings.phone}`" class="flex items-start gap-3 text-sm transition-colors hover:text-primary">
                            <Phone class="text-primary mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
                            <span><span class="text-foreground block font-medium">{{ settings.phone }}</span><span class="text-muted-foreground block text-xs">{{ t("labels.footer.phone_hours") }}</span></span>
                        </a>
                        <div v-if="settings.address" class="flex items-start gap-3 text-sm">
                            <MapPin class="text-primary mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
                            <span class="text-muted-foreground leading-5">{{ settings.address }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-muted-foreground mt-12 flex flex-col gap-5 border-t border-border pt-6 text-xs md:flex-row md:items-center md:justify-between">
                <p>&copy; {{ currentYear }} {{ settings.site_name }}. {{ t("labels.footer.all_rights_reserved") }}</p>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-2 md:justify-end">
                    <span class="mr-1">{{ t("labels.footer.secure_payment") }}</span>
                    <span
                        v-for="method in paymentMethods"
                        :key="method.key"
                        class="inline-flex h-7 w-14 items-center justify-center rounded-sm bg-white/80 px-1.5 py-1"
                    >
                        <img
                            :src="method.src"
                            :alt="method.name"
                            class="max-h-5 w-auto max-w-full object-contain"
                            loading="lazy"
                        />
                    </span>
                </div>
            </div>
        </div>
    </footer>
</template>
