<script setup>
import Button from "@frontend/components/UI/Button.vue";
import { useForm, Link, usePage } from "@inertiajs/vue3";
import FormCheckbox from "../../components/UI/FormCheckbox.vue";
import FormInput from "../../components/UI/FormInput.vue";
import Card from "../../components/UI/Card.vue";
import { useI18n } from "vue-i18n";
import { ArrowRight, Eye, EyeOff, Lock, Mail } from "lucide-vue-next";
import { ref } from "vue";
import PageShellAuth from "@frontend/components/PageShellAuth.vue";

const { t } = useI18n();
const page = usePage();
const isPrivateStore = page.props.settings?.is_private_store ?? false;
const registrationEnabled = page.props.settings?.registration ?? true;
const isRegistrationClosed = isPrivateStore || !registrationEnabled;
const socialLoginEnabled = page.props.settings?.social_login_enabled ?? true;

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const showPassword = ref(false);
const emailInput = ref(null);
const passwordInput = ref(null);

const submit = () => {
    form.post(route("frontend.login.post"), {
        onFinish: () => form.reset("password"),
        onError: (errors) => {
            if (errors.email) {
                emailInput.value?.focus();
            } else if (errors.password) {
                passwordInput.value?.focus();
            }
        },
    });
};
</script>

<template>
    <PageShellAuth :title="t('meta.login.title')">
        <Card class="w-full rounded-2xl border-0 p-6 shadow-lg sm:p-8">
            <div class="mb-7 space-y-2 text-center sm:mb-8">
                <h2 class="text-foreground text-2xl font-bold md:text-3xl">{{ t("labels.auth.welcome") }}</h2>
                <p class="text-muted-foreground text-sm">{{ t("labels.auth.login_subtitle") }}</p>
                <p v-if="isRegistrationClosed" class="bg-secondary text-muted-foreground rounded-xl px-4 py-3 text-sm leading-relaxed">
                    {{ t("labels.auth.registration_closed_description") }}
                </p>
            </div>

            <form class="space-y-6" @submit.prevent="submit">
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
                        <FormInput
                            ref="passwordInput"
                            id="password"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            autocomplete="current-password"
                            required
                            :invalid="Boolean(form.errors.password)"
                            :aria-describedby="form.errors.password ? 'password-error' : undefined"
                            class="py-3.5"
                            :placeholder="t('placeholders.password')"
                        >
                            <template #prefix><Lock class="text-muted-foreground absolute top-3.5 left-4 h-5 w-5" /></template>
                            <template #suffix
                                ><Button
                                    type="button"
                                    class="text-muted-foreground hover:text-foreground focus:ring-primary/20 absolute top-2.5 right-3 rounded-lg p-2 transition-colors focus:ring-2 focus:outline-none"
                                    :aria-label="showPassword ? t('labels.auth.hide_password') : t('labels.auth.show_password')"
                                    @click="showPassword = !showPassword"
                                >
                                    <EyeOff v-if="showPassword" class="h-5 w-5" />
                                    <Eye v-else class="h-5 w-5" /></button
                            ></template>
                        </FormInput>
                        <p v-if="form.errors.password" id="password-error" class="text-destructive text-xs" role="alert">{{ form.errors.password }}</p>
                    </div>
                </div>

                <label for="remember" class="text-muted-foreground flex min-h-11 cursor-pointer items-start gap-3 text-sm">
                    <FormCheckbox id="remember" v-model="form.remember" type="checkbox" class="mt-0.5" />
                    <span>{{ t("labels.auth.remember_me") }}</span>
                </label>

                <Button
                    type="submit"
                    :loading="form.processing"
                    class="bg-primary text-primary-foreground hover:bg-primary/90 flex w-full items-center justify-center gap-2 rounded-full py-3.5 text-sm font-bold shadow-sm transition-all hover:shadow-md disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{ t("labels.actions.login") }}
                    <ArrowRight class="h-4 w-4" />
                </Button>
                <div class="flex items-center justify-end">
                    <Link
                        :href="route('frontend.forgot-password')"
                        class="text-primary hover:text-primary/80 text-sm font-semibold transition-colors"
                    >
                        {{ t("labels.auth.forgot_password") }}
                    </Link>
                </div>
            </form>

            <div v-if="socialLoginEnabled && !isPrivateStore" class="my-8 flex items-center gap-3" aria-hidden="true">
                <div class="bg-border h-px flex-1" />
                <span class="text-muted-foreground text-xs">{{ t("labels.auth.or_continue_with") }}</span>
                <div class="bg-border h-px flex-1" />
            </div>

            <div v-if="socialLoginEnabled && !isPrivateStore" class="flex gap-3">
                <Button
                    as="a"
                    :href="route('frontend.auth.social.redirect', { provider: 'google' })"
                    variant="outline"
                    :aria-label="t('labels.auth.continue_with_google')"
                    class="min-w-0 flex-1 py-3"
                >
                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill="#4285F4" d="M21.35 12.23c0-.71-.06-1.39-.18-2.05H12v3.88h5.24a4.48 4.48 0 0 1-1.94 2.94v2.51h3.15c1.84-1.7 2.9-4.2 2.9-7.28Z" />
                        <path fill="#34A853" d="M12 21.75c2.62 0 4.82-.87 6.45-2.35l-3.15-2.51c-.87.58-1.99.93-3.3.93-2.53 0-4.67-1.71-5.44-4.01H3.31v2.59A9.75 9.75 0 0 0 2.25 12c0 1.57.38 3.05 1.06 4.4l3.25-2.59Z" />
                        <path fill="#FBBC05" d="M6.56 13.81A5.86 5.86 0 0 1 6.26 12c0-.63.11-1.24.3-1.81V7.6H3.31A9.75 9.75 0 0 0 2.25 12c0 1.57.38 3.05 1.06 4.4l3.25-2.59Z" />
                        <path fill="#EA4335" d="M12 6.18c1.43 0 2.71.49 3.72 1.45l2.79-2.79C16.81 3.26 14.62 2.25 12 2.25A9.75 9.75 0 0 0 3.31 7.6l3.25 2.59c.77-2.3 2.91-4.01 5.44-4.01Z" />
                    </svg>
                    <span>Google</span>
                </Button>
                <Button
                    as="a"
                    :href="route('frontend.auth.social.redirect', { provider: 'github' })"
                    variant="outline"
                    :aria-label="t('labels.auth.continue_with_github')"
                    class="min-w-0 flex-1 py-3"
                >
                    <svg class="h-5 w-5 shrink-0 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 2.25a9.75 9.75 0 0 0-3.08 19c.49.09.67-.21.67-.47v-1.72c-2.74.6-3.32-1.17-3.32-1.17-.45-1.14-1.1-1.45-1.1-1.45-.9-.61.07-.6.07-.6 1 .07 1.52 1.02 1.52 1.02.89 1.51 2.32 1.08 2.89.83.09-.64.35-1.08.63-1.33-2.19-.25-4.49-1.09-4.49-4.86 0-1.07.38-1.95 1.01-2.64-.1-.25-.44-1.25.1-2.6 0 0 .83-.26 2.69 1.01A9.4 9.4 0 0 1 12 6.4c.85 0 1.71.12 2.51.34 1.86-1.27 2.69-1.01 2.69-1.01.54 1.35.2 2.35.1 2.6.63.69 1.01 1.57 1.01 2.64 0 3.78-2.3 4.6-4.5 4.85.35.3.67.9.67 1.82v2.7c0 .26.18.57.68.47A9.75 9.75 0 0 0 12 2.25Z" />
                    </svg>
                    <span>GitHub</span>
                </Button>
            </div>

            <div class="border-border mt-8 border-t pt-6 text-center">
                <template v-if="isRegistrationClosed">
                    <p class="text-muted-foreground text-sm">
                        {{ t("labels.auth.registration_closed_short") }}
                    </p>
                    <Link
                        :href="route('frontend.registration-closed')"
                        class="text-primary hover:text-primary/80 mt-2 inline-flex font-semibold transition-colors"
                    >
                        {{ t("labels.auth.registration_closed_cta") }}
                    </Link>
                </template>
                <p v-else class="text-muted-foreground text-sm leading-relaxed">
                    {{ t("labels.auth.no_account") }}
                    <Link :href="route('frontend.signup')" class="text-primary hover:text-primary/80 font-semibold transition-colors">
                        {{ t("labels.auth.create_account") }}
                    </Link>
                </p>
            </div>
        </Card>
    </PageShellAuth>
</template>
