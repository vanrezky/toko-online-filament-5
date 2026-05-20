<script setup>
import { watch } from "vue";
import { useForm, Link } from "@inertiajs/vue3";
import TemplateWrapper from "../../components/TemplateWrapper.vue";
import { Mail, ArrowRight, ArrowLeft } from "lucide-vue-next";
import { useI18n } from "vue-i18n";

const { t } = useI18n();

const props = defineProps({
    status: String,
});

const form = useForm({
    email: "",
    guard: "customer",
});

watch(
    () => props.status,
    (newStatus) => {
        if (newStatus) {
            form.reset();
        }
    },
);

const submit = () => {
    form.post(route("frontend.forgot-password.send"));
};
</script>

<template>
    <TemplateWrapper :title="t('meta.forgot_password.title')">
        <div class="flex min-h-[70vh] items-center justify-center bg-secondary/30 px-4 py-12 sm:px-6 lg:px-8">
            <div class="w-full max-w-md space-y-8">
                <div class="rounded-2xl bg-white p-8 shadow-sm">
                    <div class="mb-8 space-y-2 text-center">
                        <h2 class="text-2xl font-bold text-foreground md:text-3xl">{{ t('labels.auth.forgot_password_heading') }}</h2>
                        <p class="text-sm text-muted-foreground">{{ t('labels.auth.forgot_password_description') }}</p>
                    </div>

                    <div v-if="status" class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700">
                        {{ status }}
                    </div>

                    <form class="space-y-5" @submit.prevent="submit">
                        <div class="space-y-2">
                            <label for="email" class="text-sm font-semibold text-foreground">{{ t('labels.form.email') }}</label>
                            <div class="relative">
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    required
                                    class="w-full rounded-xl border border-border bg-secondary px-4 py-3.5 pl-11 text-sm transition-all focus:outline-none focus:ring-2 focus:ring-primary/20"
                                    :placeholder="t('placeholders.email')"
                                />
                                <Mail class="absolute left-4 top-3.5 h-5 w-5 text-muted-foreground" />
                            </div>
                            <p v-if="form.errors.email" class="text-xs text-red-500">{{ form.errors.email }}</p>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex w-full items-center justify-center gap-2 rounded-full bg-primary py-3.5 text-sm font-bold text-primary-foreground shadow-md transition-all hover:bg-primary/90 hover:shadow-lg disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {{ t('labels.actions.send_reset_link') }}
                            <ArrowRight class="h-4 w-4" />
                        </button>
                    </form>

                    <div class="mt-8 border-t border-border pt-6 text-center">
                        <Link
                            :href="route('frontend.login')"
                            class="inline-flex items-center gap-2 text-sm font-semibold text-foreground transition-colors hover:text-primary"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            {{ t('labels.actions.back_to_login') }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </TemplateWrapper>
</template>
