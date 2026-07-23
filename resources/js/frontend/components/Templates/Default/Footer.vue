<script setup>
import Button from "@frontend/components/UI/Button.vue";
import { Link, useForm, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import { Instagram, Facebook, Twitter, Mail } from "lucide-vue-next";
import { toast } from "vue-sonner";
import PromotionBanner from "../../UI/PromotionBanner.vue";
import { useI18n } from "vue-i18n";
import FormInput from "../../UI/FormInput.vue";

const { t } = useI18n();

const { props } = usePage();
const settings = computed(() => props.settings);
const currentYear = new Date().getFullYear();

const newsletterForm = useForm({
    email: "",
});

const submitNewsletter = () => {
    newsletterForm.post(route("frontend.newsletter.subscribe"), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(t('messages.success.subscribed'));
            newsletterForm.reset();
        },
        onError: (errors) => {
            const message = errors.email || t('messages.error.generic');
            toast.error(message);
        },
    });
};

const customerServiceLinks = [
  { name: t('labels.footer.contact_us'), href: route('frontend.contact') },
  { name: t('labels.footer.faq'), href: route('frontend.faq') },
  { name: t('labels.footer.shipping_info'), href: '#' },
  { name: t('labels.footer.returns_exchanges'), href: '#' },
  { name: t('labels.footer.track_order'), href: '#' },
];

const companyLinks = computed(() => [...(usePage().props.menu?.footer || [])]);
const footerPromos = computed(() => {
  const promos = usePage().props.promotions;
  return (Array.isArray(promos) ? promos : promos?.data || []).filter(p => p.position === 'footer');
});
</script>

<template>
    <footer class="border-t border-border bg-background pb-8 pt-16">
        <!-- Promotions: Footer Position (New Section) -->
        <div v-if="footerPromos.length > 0" class="container mx-auto px-4 md:px-6 mb-12">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <PromotionBanner v-for="promo in footerPromos" :key="promo.id" :promotion="promo" class="aspect-[16/7] md:aspect-[21/9]" />
          </div>
        </div>
        <div class="container mx-auto px-4 md:px-6">
            <div class="grid grid-cols-1 gap-12 md:grid-cols-2 md:gap-8 lg:grid-cols-4">
                <!-- Brand Section -->
                <div class="space-y-6">
                    <Link :href="route('frontend.home')" class="block">
                        <img v-if="settings.logo" :src="settings.logo" alt="Logo" class="h-10 w-auto" />
                        <span v-else class="font-display text-3xl leading-none text-foreground">{{ settings.site_name }}</span>
                    </Link>
                    <p class="text-muted-foreground max-w-xs text-sm leading-relaxed">
                        {{ settings.site_description }}
                    </p>
                    <div class="flex space-x-5">
                        <a href="#" class="text-muted-foreground transition-colors hover:text-foreground">
                            <Instagram class="h-5 w-5" />
                        </a>
                        <a href="#" class="text-muted-foreground transition-colors hover:text-foreground">
                            <Facebook class="h-5 w-5" />
                        </a>
                        <a href="#" class="text-muted-foreground transition-colors hover:text-foreground">
                            <Twitter class="h-5 w-5" />
                        </a>
                    </div>
                </div>

                <!-- Customer Service -->
                <div>
                    <h3 class="mb-6 text-xs font-semibold tracking-[0.12em] text-foreground uppercase">{{ t('labels.footer.customer_service') }}</h3>
                    <ul class="space-y-4">
                        <li v-for="link in customerServiceLinks" :key="link.name">
                            <Link :href="link.href" class="text-muted-foreground text-sm transition-colors hover:text-foreground">{{ link.name }}</Link>
                        </li>
                    </ul>
                </div>

                <!-- Pages -->
                <div>
                    <h3 class="mb-6 text-xs font-semibold tracking-[0.12em] text-foreground uppercase">{{ t('labels.footer.pages') }}</h3>
                    <ul class="space-y-4">
                        <li v-for="link in companyLinks" :key="link.name">
                            <Link :href="link.href" class="text-muted-foreground text-sm transition-colors hover:text-foreground">{{ link.name }}</Link>
                        </li>
                    </ul>
                </div>

                <!-- Newsletter -->
                <div>
                    <h3 class="mb-6 text-xs font-semibold tracking-[0.12em] text-foreground uppercase">{{ t('labels.footer.newsletter') }}</h3>
                    <p class="text-muted-foreground mb-6 text-sm">{{ t('labels.footer.newsletter_description') }}</p>
                    <form class="space-y-3" @submit.prevent="submitNewsletter">
                        <FormInput
                                v-model="newsletterForm.email"
                                type="email"
                                :placeholder="t('placeholders.email')"
                                :invalid="Boolean(newsletterForm.errors.email)"
                                class="rounded-md border-border bg-secondary/40"
                            >
                            <template #suffix><Button
                                type="submit"
                                :disabled="newsletterForm.processing"
                                class="absolute right-0 top-0 h-full px-4 text-foreground hover:text-primary disabled:opacity-50"
                            >
                                <Mail class="h-5 w-5" />
                            </Button></template>
                        </FormInput>
                        <p v-if="newsletterForm.errors.email" class="text-destructive text-xs">
                            {{ newsletterForm.errors.email }}
                        </p>
                    </form>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div
                class="text-muted-foreground mt-16 flex flex-col items-center justify-between space-y-4 border-t border-border pt-8 text-xs md:flex-row md:space-y-0"
            >
                <p>&copy; {{ currentYear }} {{ settings.site_name }}. {{ t('labels.footer.all_rights_reserved') }}</p>
                <div class="flex space-x-6">
                    <span>{{ t('labels.footer.secure_payment') }}</span>
                    <div class="flex space-x-3 opacity-50 grayscale">
                        <img src="https://cdn-icons-png.flaticon.com/512/196/196578.png" alt="Visa" class="h-4 w-auto" />
                        <img src="https://cdn-icons-png.flaticon.com/512/196/196561.png" alt="Mastercard" class="h-4 w-auto" />
                        <img src="https://cdn-icons-png.flaticon.com/512/196/196566.png" alt="PayPal" class="h-4 w-auto" />
                    </div>
                </div>
            </div>
        </div>
    </footer>
</template>
