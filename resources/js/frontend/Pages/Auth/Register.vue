<script setup>
import Button from "@frontend/components/UI/Button.vue";
import { useForm, Link, usePage } from "@inertiajs/vue3";
import FormCheckbox from "../../components/UI/FormCheckbox.vue";
import FormInput from "../../components/UI/FormInput.vue";
import Card from "../../components/UI/Card.vue";
import { useI18n } from "vue-i18n";
import { ArrowRight, CheckCircle2, Circle, Eye, EyeOff, Lock, Mail, User } from "lucide-vue-next";
import { computed, ref } from "vue";
import PageShellAuth from "@frontend/components/PageShellAuth.vue";

const { t } = useI18n();
const showPassword = ref(false);
const showConfirmPassword = ref(false);
const firstNameInput = ref(null);
const lastNameInput = ref(null);
const emailInput = ref(null);
const passwordInput = ref(null);
const passwordConfirmationInput = ref(null);
const settings = usePage().props.settings ?? {};
const props = defineProps({
    secure_password: Boolean,
});

const form = useForm({
    first_name: "",
    last_name: "",
    email: "",
    password: "",
    password_confirmation: "",
    terms_accepted: false,
});

const passwordRequirements = computed(() => [
    { key: "minimum", passed: form.password.length >= 8 },
    { key: "letter", passed: /\p{L}/u.test(form.password) },
    { key: "number", passed: /\p{N}/u.test(form.password) },
    { key: "symbol", passed: /\p{Z}|\p{S}|\p{P}/u.test(form.password) },
]);

const submit = () => {
    form.post(route("frontend.signup.post"), {
        onFinish: () => form.reset("password", "password_confirmation"),
        onError: (errors) => {
            if (errors.first_name) firstNameInput.value?.focus();
            else if (errors.last_name) lastNameInput.value?.focus();
            else if (errors.email) emailInput.value?.focus();
            else if (errors.password) passwordInput.value?.focus();
            else if (errors.password_confirmation) passwordConfirmationInput.value?.focus();
        },
    });
};
</script>

<template>
    <PageShellAuth :title="t('meta.register.title')">
        <Card class="w-full rounded-2xl border-0 p-6 shadow-lg sm:p-8">
            <div class="mb-7 space-y-2 text-center sm:mb-8">
                <h2 class="text-foreground text-2xl font-bold md:text-3xl">{{ t("labels.auth.register_heading") }}</h2>
                <p class="text-muted-foreground text-sm">{{ t("labels.auth.register_subtitle") }}</p>
            </div>

            <form class="space-y-6" @submit.prevent="submit">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 sm:gap-4">
                    <div class="space-y-2">
                        <label for="first_name" class="text-foreground text-sm font-semibold">{{ t("labels.form.first_name") }}</label>
                        <FormInput
                            ref="firstNameInput"
                            id="first_name"
                            v-model="form.first_name"
                            type="text"
                            autocomplete="given-name"
                            required
                            autofocus
                            :invalid="Boolean(form.errors.first_name)"
                            :aria-describedby="form.errors.first_name ? 'first-name-error' : undefined"
                            class="py-3.5"
                            :placeholder="t('placeholders.first_name')"
                        >
                            <template #prefix><User class="text-muted-foreground absolute top-3.5 left-4 h-5 w-5" /></template>
                        </FormInput>
                        <p v-if="form.errors.first_name" id="first-name-error" class="text-destructive text-xs" role="alert">
                            {{ form.errors.first_name }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label for="last_name" class="text-foreground text-sm font-semibold">{{ t("labels.form.last_name") }}</label>
                        <FormInput
                            ref="lastNameInput"
                            id="last_name"
                            v-model="form.last_name"
                            type="text"
                            autocomplete="family-name"
                            required
                            :invalid="Boolean(form.errors.last_name)"
                            :aria-describedby="form.errors.last_name ? 'last-name-error' : undefined"
                            class="py-3.5"
                            :placeholder="t('placeholders.last_name')"
                        />
                        <p v-if="form.errors.last_name" id="last-name-error" class="text-destructive text-xs" role="alert">
                            {{ form.errors.last_name }}
                        </p>
                    </div>
                </div>

                <div class="space-y-5">
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

                    <div class="space-y-2">
                        <label for="password" class="text-foreground text-sm font-semibold">{{ t("labels.form.password") }}</label>
                        <div class="group relative">
                            <FormInput
                                ref="passwordInput"
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="new-password"
                                required
                                :invalid="Boolean(form.errors.password)"
                                :aria-describedby="
                                    form.errors.password ? 'password-error' : props.secure_password ? 'password-requirements' : undefined
                                "
                                class="py-3.5"
                                :placeholder="t('placeholders.password')"
                            >
                                <template #prefix><Lock class="text-muted-foreground absolute top-3.5 left-4 h-5 w-5" /></template>
                                <template #suffix>
                                    <Button
                                        type="button"
                                        class="text-muted-foreground hover:text-foreground focus:ring-primary/20 absolute top-1/2 right-3 -translate-y-1/2 rounded-lg p-2 transition-colors focus:ring-2 focus:outline-none"
                                        :aria-label="showPassword ? t('labels.auth.hide_password') : t('labels.auth.show_password')"
                                        @click="showPassword = !showPassword"
                                    >
                                        <EyeOff v-if="showPassword" class="h-5 w-5" />
                                        <Eye v-else class="h-5 w-5" />
                                    </Button>
                                </template>
                            </FormInput>
                            <p v-if="form.errors.password" id="password-error" class="text-destructive relative z-30 text-xs" role="alert">
                                {{ form.errors.password }}
                            </p>
                            <div
                                v-if="props.secure_password"
                                id="password-requirements"
                                class="border-border bg-background pointer-events-none invisible absolute top-full left-0 z-20 mt-2 w-full translate-y-1 space-y-2 rounded-lg border p-3 text-xs font-normal opacity-0 shadow-lg transition-all duration-150 group-focus-within:pointer-events-auto group-focus-within:visible group-focus-within:translate-y-0 group-focus-within:opacity-100 group-hover:pointer-events-auto group-hover:visible group-hover:translate-y-0 group-hover:opacity-100"
                                role="group"
                                :aria-label="t('labels.auth.secure_password_requirements')"
                            >
                                <p class="text-foreground font-semibold">{{ t("labels.auth.secure_password_requirements") }}</p>
                                <ul class="grid gap-1.5 sm:grid-cols-2">
                                    <li
                                        v-for="requirement in passwordRequirements"
                                        :key="requirement.key"
                                        class="flex items-center gap-2"
                                        :class="requirement.passed ? 'text-primary' : 'text-muted-foreground'"
                                    >
                                        <CheckCircle2 v-if="requirement.passed" class="h-4 w-4 shrink-0" aria-hidden="true" />
                                        <Circle v-else class="h-4 w-4 shrink-0" aria-hidden="true" />
                                        <span>{{ t(`labels.auth.secure_password_${requirement.key}`) }}</span>
                                        <span class="sr-only">
                                            {{
                                                requirement.passed
                                                    ? t("labels.auth.secure_password_requirement_met")
                                                    : t("labels.auth.secure_password_requirement_pending")
                                            }}
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="password_confirmation" class="text-foreground text-sm font-semibold">{{
                            t("labels.form.confirm_password")
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
                            :placeholder="t('placeholders.confirm_password')"
                        >
                            <template #prefix><Lock class="text-muted-foreground absolute top-3.5 left-4 h-5 w-5" /></template>
                            <template #suffix
                                ><Button
                                    type="button"
                                    class="text-muted-foreground hover:text-foreground focus:ring-primary/20 absolute top-1/2 right-3 -translate-y-1/2 rounded-lg p-2 transition-colors focus:ring-2 focus:outline-none"
                                    :aria-label="showConfirmPassword ? t('labels.auth.hide_password') : t('labels.auth.show_password')"
                                    @click="showConfirmPassword = !showConfirmPassword"
                                >
                                    <EyeOff v-if="showConfirmPassword" class="h-5 w-5" />
                                    <Eye v-else class="h-5 w-5"
                                /></Button>
                            </template>
                        </FormInput>
                        <p v-if="form.errors.password_confirmation" id="password-confirmation-error" class="text-destructive text-xs" role="alert">
                            {{ form.errors.password_confirmation }}
                        </p>
                    </div>
                </div>

                <label
                    v-if="settings.term_agreement"
                    for="terms_accepted"
                    class="text-muted-foreground flex min-h-11 cursor-pointer items-start gap-3 text-sm"
                >
                    <FormCheckbox id="terms_accepted" v-model="form.terms_accepted" type="checkbox" required class="mt-0.5" />
                    <span>
                        {{ t("labels.auth.terms_agreement_prefix") }}
                        <Link
                            :href="route('frontend.page.show', 'syarat-ketentuan')"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-primary font-bold hover:underline"
                        >
                            {{ t("labels.auth.terms_and_conditions") }}
                        </Link>
                        {{ t("labels.auth.terms_agreement_connector") }}
                        <Link
                            :href="route('frontend.page.show', 'kebijakan-privasi')"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-primary font-bold hover:underline"
                        >
                            {{ t("labels.auth.privacy_policy") }}
                        </Link>
                        {{ settings.site_name }}
                    </span>
                </label>

                <Button
                    type="submit"
                    :loading="form.processing"
                    :disabled="settings.term_agreement && !form.terms_accepted"
                    class="bg-primary text-primary-foreground hover:bg-primary/90 flex w-full items-center justify-center gap-2 rounded-full py-3.5 text-sm font-bold shadow-sm transition-all hover:shadow-md disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{ t("labels.actions.register") }}
                    <ArrowRight class="h-4 w-4" />
                </Button>
            </form>

            <div class="border-border mt-8 border-t pt-6 text-center">
                <p class="text-muted-foreground text-sm">
                    {{ t("labels.auth.has_account") }}
                    <Link :href="route('frontend.login')" class="text-primary font-bold hover:underline"> {{ t("labels.actions.login_now") }} </Link>
                </p>
            </div>
        </Card>
    </PageShellAuth>
</template>
