<script setup>
import Button from "@frontend/components/UI/Button.vue";
import { ref, watch } from "vue";
import { useForm, Link, usePage } from "@inertiajs/vue3";

import FormInput from "../../components/UI/FormInput.vue";
import Card from "../../components/UI/Card.vue";
import { Mail, ArrowRight, ArrowLeft } from "lucide-vue-next";
import { useI18n } from "vue-i18n";
import PageShellAuth from "@frontend/components/PageShellAuth.vue";

const { t } = useI18n();
const isPrivateStore = usePage().props.settings?.is_private_store ?? false;

const props = defineProps({
    status: String,
});

const form = useForm({
    email: "",
    guard: "customer",
});
const emailInput = ref(null);

watch(
    () => props.status,
    (newStatus) => {
        if (newStatus) {
            form.reset();
        }
    },
);

const submit = () => {
    form.post(route("frontend.forgot-password.send"), {
        onError: (errors) => {
            if (errors.email) emailInput.value?.focus();
        },
    });
};
</script>

<template>
    <PageShellAuth :title="t('meta.forgot_password.title')">
        <Card class="w-full rounded-2xl border-0 p-6 shadow-lg sm:p-8">
            <div class="mb-7 space-y-2 text-center sm:mb-8">
                <h2 class="text-foreground text-2xl font-bold md:text-3xl">{{ t("labels.auth.forgot_password_heading") }}</h2>
                <p class="text-muted-foreground text-sm">{{ t("labels.auth.forgot_password_description") }}</p>
                <p v-if="isPrivateStore" class="bg-secondary text-muted-foreground rounded-xl px-4 py-3 text-sm">
                    {{ t("labels.auth.registration_closed_description") }}
                </p>
            </div>

            <div v-if="status" class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700" role="status">
                {{ status }}
            </div>

            <form class="space-y-6" @submit.prevent="submit">
                <div class="space-y-2">
                    <label for="email" class="text-foreground text-sm font-semibold">{{ t("labels.form.email") }}</label>
                    <FormInput
                        ref="emailInput"
                        id="email"
                        v-model="form.email"
                        type="email"
                        autocomplete="email"
                        required
                        :invalid="Boolean(form.errors.email)"
                        :aria-describedby="form.errors.email ? 'email-error' : undefined"
                        class="py-3.5"
                        :placeholder="t('placeholders.email')"
                    >
                        <template #prefix><Mail class="text-muted-foreground absolute top-3.5 left-4 h-5 w-5" /></template>
                    </FormInput>
                    <p v-if="form.errors.email" id="email-error" class="text-destructive text-xs" role="alert">{{ form.errors.email }}</p>
                </div>

                <Button
                    type="submit"
                    :loading="form.processing"
                    class="bg-primary text-primary-foreground hover:bg-primary/90 flex w-full items-center justify-center gap-2 rounded-full py-3.5 text-sm font-bold shadow-sm transition-all hover:shadow-md disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{ t("labels.actions.send_reset_link") }}
                    <ArrowRight class="h-4 w-4" />
                </Button>
            </form>

            <div class="border-border mt-8 border-t pt-6 text-center">
                <Link
                    :href="route('frontend.login')"
                    class="text-foreground hover:text-primary inline-flex items-center gap-2 text-sm font-semibold transition-colors"
                >
                    <ArrowLeft class="h-4 w-4" />
                    {{ t("labels.actions.back_to_login") }}
                </Link>
            </div>
        </Card>
    </PageShellAuth>
</template>
