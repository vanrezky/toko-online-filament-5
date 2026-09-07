<script setup>
import { computed } from "vue";
import { useForm } from "@inertiajs/vue3";
import { Bell, Leaf, LockKeyhole, Mail, Send, Sparkles } from "lucide-vue-next";
import { toast } from "vue-sonner";
import { useI18n } from "vue-i18n";
import { getSectionContent } from "../../lib/utils";
import Button from "./Button.vue";
import FormInput from "./FormInput.vue";

const props = defineProps({ template: { type: Object, default: null } });
const { t } = useI18n();
const form = useForm({ email: "" });
const getNewsletterContent = (key, fallback, legacyValues = []) => {
    const content = getSectionContent(props.template, "newsletter", key, "");
    return !content || legacyValues.includes(content) ? fallback : content;
};
const title = computed(() => getNewsletterContent("title", t("labels.home.newsletter_title"), ["Dapatkan Penawaran Spesial"]));
const subtitle = computed(() => getNewsletterContent("subtitle", t("labels.home.newsletter_description"), ["Daftar newsletter untuk mendapatkan informasi tentang produk baru dan promo menarik."]));
const buttonText = computed(() => getNewsletterContent("button_text", t("labels.home.newsletter_button"), ["Berlangganan"]));
const placeholder = computed(() => getNewsletterContent("placeholder", t("labels.home.newsletter_placeholder"), ["Masukkan email Anda"]));
const backgroundClasses = computed(() => {
    const style = getNewsletterContent("bg_style", "gradient");

    return {
        "bg-primary/5": style === "gradient",
        "bg-primary/10": style === "solid",
        "bg-background": style === "minimal",
    };
});

const submit = () => {
    form.post(route("frontend.newsletter.subscribe"), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(t("messages.success.subscribed"));
            form.reset();
        },
        onError: (errors) => toast.error(errors.email || t("messages.error.generic")),
    });
};
</script>

<template>
    <section class="bg-background py-6 md:py-10" aria-labelledby="newsletter-title">
        <div class="container mx-auto px-4 md:px-8">
            <div class="relative isolate overflow-hidden rounded-[2rem] border border-primary/10 px-6 py-8 sm:px-10 md:py-10 lg:px-12" :class="backgroundClasses">
                <div class="pointer-events-none absolute -right-16 -bottom-24 h-72 w-72 rounded-full bg-primary/10" aria-hidden="true"></div>
                <div class="relative grid items-center gap-8 lg:grid-cols-[1.05fr_1.25fr_.65fr] lg:gap-10">
                    <div class="max-w-xl">
                        <p class="text-primary mb-3 text-xs font-bold tracking-[0.14em] uppercase">{{ t("labels.home.newsletter_eyebrow") }}</p>
                        <h2 id="newsletter-title" class="text-foreground max-w-[19ch] text-3xl leading-[1.05] font-bold tracking-[-0.045em] md:text-4xl">{{ title }}</h2>
                        <p class="text-muted-foreground mt-4 max-w-lg text-sm leading-6 md:text-base">{{ subtitle }}</p>
                    </div>

                    <form class="relative" @submit.prevent="submit">
                        <div data-newsletter-control class="flex flex-col overflow-hidden rounded-xl border border-white/70 bg-white shadow-sm sm:flex-row">
                            <FormInput
                                v-model="form.email"
                                type="email"
                                :placeholder="placeholder"
                                :invalid="Boolean(form.errors.email)"
                                :aria-label="t('labels.footer.newsletter')"
                                wrapper-class="min-w-0 flex-1"
                                class="rounded-none border-0 bg-transparent py-3.5 pl-11 shadow-none focus-visible:border-0 focus-visible:ring-0 focus-visible:ring-offset-0"
                            >
                                <template #prefix>
                                    <Mail class="text-muted-foreground absolute top-1/2 left-4 h-5 w-5 -translate-y-1/2" aria-hidden="true" />
                                </template>
                            </FormInput>
                            <Button type="submit" :loading="form.processing" variant="primary" class="min-h-12 rounded-none border-0 px-3 text-sm font-bold whitespace-nowrap sm:w-44 sm:shrink-0 sm:border-l sm:border-primary-foreground/20">
                                {{ buttonText }}
                                <Send class="h-4 w-4" aria-hidden="true" />
                            </Button>
                        </div>
                        <p v-if="form.errors.email" class="text-destructive mt-2 text-xs">{{ form.errors.email }}</p>
                        <p v-else class="text-muted-foreground mt-3 flex items-center gap-1.5 text-xs">
                            <LockKeyhole class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                            {{ t("labels.home.newsletter_note") }}
                        </p>
                    </form>

                    <div class="relative hidden min-h-40 items-center justify-center lg:flex" aria-hidden="true">
                        <div class="absolute right-2 top-0 text-primary/30"><Sparkles class="h-5 w-5" /></div>
                        <div class="relative flex h-36 w-44 items-center justify-center rounded-[2rem] bg-primary/10">
                            <Leaf class="absolute -bottom-3 -left-5 h-16 w-16 -rotate-12 text-primary/70" stroke-width="1.4" />
                            <div class="relative flex h-24 w-32 items-center justify-center rounded-xl border-2 border-primary/60 bg-primary/15 shadow-sm">
                                <Mail class="h-12 w-12 text-primary" stroke-width="1.4" />
                            </div>
                            <div class="absolute -right-5 -top-5 flex h-12 w-12 items-center justify-center rounded-full bg-white text-primary shadow-md">
                                <Bell class="h-6 w-6" aria-hidden="true" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
