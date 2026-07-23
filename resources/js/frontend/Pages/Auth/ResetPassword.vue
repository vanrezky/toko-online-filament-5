<script setup>
import Button from "@frontend/components/UI/Button.vue";
import { ref } from "vue";
import { useForm, Link } from "@inertiajs/vue3";
import FormInput from "../../components/UI/FormInput.vue";
import Card from "../../components/UI/Card.vue";
import { useI18n } from "vue-i18n";
import { Lock, ArrowRight, ArrowLeft, Eye, EyeOff, Check, X } from "lucide-vue-next";
import PageShellAuth from "@frontend/components/PageShellAuth.vue";

const { t } = useI18n();

const props = defineProps({
    token: String,
    email: String,
    guard: {
        type: String,
        default: "customer",
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    guard: props.guard,
    password: "",
    password_confirmation: "",
});

const showPassword = ref(false);
const showConfirmPassword = ref(false);
const passwordInput = ref(null);
const passwordConfirmationInput = ref(null);

const passwordRules = {
    minLength: (p) => p.length >= 8,
    uppercase: (p) => /[A-Z]/.test(p),
    number: (p) => /\d/.test(p),
    symbol: (p) => /[!@#$%^&*(),.?":{}|<>]/.test(p),
};

const isPasswordValid = (rule) => {
    return rule(form.password);
};

const submit = () => {
    form.post(route("frontend.reset-password.update"), {
        onFinish: () => form.reset("password", "password_confirmation"),
        onError: (errors) => {
            if (errors.password) passwordInput.value?.focus();
            else if (errors.password_confirmation) passwordConfirmationInput.value?.focus();
        },
    });
};
</script>

<template>
    <PageShellAuth :title="t('meta.reset_password.title')">
        <Card class="w-full rounded-2xl border-0 p-6 shadow-lg sm:p-8">
            <div class="mb-7 space-y-2 text-center sm:mb-8">
                <h2 class="text-foreground text-2xl font-bold md:text-3xl">{{ t("labels.auth.reset_password_heading") }}</h2>
                <p class="text-muted-foreground text-sm">{{ t("labels.auth.reset_password_description") }}</p>
            </div>

            <form class="space-y-6" @submit.prevent="submit">
                <input type="hidden" v-model="form.token" />
                <input type="hidden" v-model="form.email" />
                <input type="hidden" v-model="form.guard" />

                <div class="space-y-2">
                    <label for="password" class="text-foreground text-sm font-semibold">{{ t("labels.form.new_password") }}</label>
                    <FormInput
                        ref="passwordInput"
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        autocomplete="new-password"
                        required
                        :invalid="Boolean(form.errors.password)"
                        :aria-describedby="form.errors.password ? 'password-error' : undefined"
                        class="py-3.5"
                        :placeholder="t('placeholders.password_min')"
                    >
                        <template #prefix><Lock class="text-muted-foreground absolute top-3.5 left-4 h-5 w-5" /></template>
                        <template #suffix
                            ><Button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="text-muted-foreground hover:text-foreground focus:ring-primary/20 absolute top-2.5 right-3 rounded-lg p-2 transition-colors focus:ring-2 focus:outline-none"
                                :aria-label="showPassword ? t('labels.auth.hide_password') : t('labels.auth.show_password')"
                            >
                                <component :is="showPassword ? EyeOff : Eye" class="h-5 w-5" />
                            </Button>
                        ></template>
                    </FormInput>
                    <p v-if="form.errors.password" id="password-error" class="text-destructive text-xs" role="alert">{{ form.errors.password }}</p>

                    <div v-if="form.password" class="bg-secondary/50 mt-3 space-y-1.5 rounded-xl p-3">
                        <p class="text-muted-foreground text-xs font-medium">{{ t("labels.auth.password_requirements_title") }}</p>
                        <div
                            class="flex items-center gap-2 text-xs"
                            :class="isPasswordValid(passwordRules.minLength) ? 'text-green-600' : 'text-red-500'"
                        >
                            <component :is="isPasswordValid(passwordRules.minLength) ? Check : X" class="h-3.5 w-3.5" />
                            {{ t("labels.auth.password_min_length") }}
                        </div>
                        <div
                            class="flex items-center gap-2 text-xs"
                            :class="isPasswordValid(passwordRules.uppercase) ? 'text-green-600' : 'text-red-500'"
                        >
                            <component :is="isPasswordValid(passwordRules.uppercase) ? Check : X" class="h-3.5 w-3.5" />
                            {{ t("labels.auth.password_uppercase") }}
                        </div>
                        <div
                            class="flex items-center gap-2 text-xs"
                            :class="isPasswordValid(passwordRules.number) ? 'text-green-600' : 'text-red-500'"
                        >
                            <component :is="isPasswordValid(passwordRules.number) ? Check : X" class="h-3.5 w-3.5" />
                            {{ t("labels.auth.password_number") }}
                        </div>
                        <div
                            class="flex items-center gap-2 text-xs"
                            :class="isPasswordValid(passwordRules.symbol) ? 'text-green-600' : 'text-red-500'"
                        >
                            <component :is="isPasswordValid(passwordRules.symbol) ? Check : X" class="h-3.5 w-3.5" />
                            {{ t("labels.auth.password_symbol") }}
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="password_confirmation" class="text-foreground text-sm font-semibold">{{
                        t("labels.form.password_confirmation")
                    }}</label>
                    <FormInput
                        ref="passwordConfirmationInput"
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        :type="showConfirmPassword ? 'text' : 'password'"
                        autocomplete="new-password"
                        required
                        :invalid="Boolean(form.errors.password_confirmation)"
                        :aria-describedby="form.errors.password_confirmation ? 'password-confirmation-error' : undefined"
                        class="py-3.5"
                        :placeholder="t('placeholders.new_password_confirmation')"
                    >
                        <template #prefix><Lock class="text-muted-foreground absolute top-3.5 left-4 h-5 w-5" /></template>
                        <template #suffix
                            ><Button
                                type="button"
                                @click="showConfirmPassword = !showConfirmPassword"
                                class="text-muted-foreground hover:text-foreground focus:ring-primary/20 absolute top-2.5 right-3 rounded-lg p-2 transition-colors focus:ring-2 focus:outline-none"
                                :aria-label="showConfirmPassword ? t('labels.auth.hide_password') : t('labels.auth.show_password')"
                            >
                                <component :is="showConfirmPassword ? EyeOff : Eye" class="h-5 w-5" />
                            </Button>
                        ></template>
                    </FormInput>
                    <p v-if="form.errors.password_confirmation" id="password-confirmation-error" class="text-destructive text-xs" role="alert">
                        {{ form.errors.password_confirmation }}
                    </p>
                </div>

                <Button
                    type="submit"
                    :loading="form.processing"
                    class="bg-primary text-primary-foreground hover:bg-primary/90 flex w-full items-center justify-center gap-2 rounded-full py-3.5 text-sm font-bold shadow-sm transition-all hover:shadow-md disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{ t("labels.actions.reset_password") }}
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
