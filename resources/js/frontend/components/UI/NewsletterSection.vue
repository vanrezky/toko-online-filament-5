<script setup>
import { computed } from "vue";
import { useForm } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import { useI18n } from "vue-i18n";
import { getSectionContent } from "../../lib/utils";
import Button from "./Button.vue";
import FormInput from "./FormInput.vue";

const props = defineProps({
    template: { type: Object, default: null },
});

const { t } = useI18n();
const form = useForm({ email: "" });
const title = computed(() => getSectionContent(props.template, "newsletter", "title", "Dapatkan Penawaran Spesial"));
const subtitle = computed(() => getSectionContent(props.template, "newsletter", "subtitle", "Daftar newsletter untuk mendapatkan informasi tentang produk baru dan promo menarik."));
const buttonText = computed(() => getSectionContent(props.template, "newsletter", "button_text", "Berlangganan"));

const submit = () => {
    form.post(route("frontend.newsletter.subscribe"), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(t("messages.success.subscribed"));
            form.reset();
        },
        onError: (errors) => {
            toast.error(errors.email || t("messages.error.generic"));
        },
    });
};
</script>

<template>
    <section class="from-primary/5 via-primary/10 to-primary/5 relative overflow-hidden bg-gradient-to-r py-10 md:py-14">
        <div class="bg-primary/10 absolute -top-20 -left-20 h-64 w-64 rounded-full blur-3xl"></div>
        <div class="bg-primary/10 absolute -right-20 -bottom-20 h-64 w-64 rounded-full blur-3xl"></div>

        <div class="container mx-auto px-4">
            <div class="relative z-10 mx-auto max-w-lg text-center">
                <div class="from-primary to-primary/80 shadow-primary/30 mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="text-primary-foreground h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                </div>

                <h2 class="text-foreground mb-3 text-2xl font-bold md:text-3xl">{{ title }}</h2>
                <p class="text-muted-foreground mb-6 text-sm">{{ subtitle }}</p>

                <form class="flex flex-col gap-3 sm:flex-row" @submit.prevent="submit">
                    <FormInput v-model="form.email" type="email" :placeholder="t('placeholders.email')" :invalid="Boolean(form.errors.email)" wrapper-class="flex-grow" class="border-white/50 bg-white px-5 py-3.5 shadow-lg" />
                    <Button type="submit" :loading="form.processing" class="from-primary to-primary/90 text-primary-foreground shadow-primary/30 hover:shadow-primary/40 rounded-xl bg-gradient-to-r px-8 py-3.5 text-sm font-semibold shadow-lg transition-all duration-300 hover:shadow-xl">
                        {{ buttonText }}
                    </Button>
                </form>
                <p v-if="form.errors.email" class="text-destructive mt-2 text-xs">{{ form.errors.email }}</p>
            </div>
        </div>
    </section>
</template>
