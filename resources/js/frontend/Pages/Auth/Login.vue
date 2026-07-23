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
const isPrivateStore = usePage().props.settings?.is_private_store ?? false;

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
                <p v-if="isPrivateStore" class="bg-secondary text-muted-foreground rounded-xl px-4 py-3 text-sm leading-relaxed">
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

                <label for="remember" class="text-muted-foreground flex min-h-11 cursor-pointer items-center gap-3 text-sm">
                    <FormCheckbox id="remember" v-model="form.remember" type="checkbox" class="h-5 w-5 shrink-0" />
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

            <div class="border-border mt-8 border-t pt-6 text-center">
                <template v-if="isPrivateStore">
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
