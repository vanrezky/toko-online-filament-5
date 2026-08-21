<script setup>
import { useForm } from "@inertiajs/vue3";
import { CheckCircle2, Circle, Eye, EyeOff, KeyRound } from "lucide-vue-next";
import { useI18n } from "vue-i18n";
import { computed, ref } from "vue";
import Button from "../UI/Button.vue";
import FormInput from "../UI/FormInput.vue";

const { t } = useI18n();
const props = defineProps({
    passwordRequirementsEnabled: Boolean,
});
const showPassword = ref(false);
const showConfirmPassword = ref(false);
const form = useForm({ current_password: "", password: "", password_confirmation: "" });
const passwordRequirements = computed(() => [
    { key: "minimum", passed: form.password.length >= 8 },
    { key: "letter", passed: /\p{L}/u.test(form.password) },
    { key: "number", passed: /\p{N}/u.test(form.password) },
    { key: "symbol", passed: /\p{Z}|\p{S}|\p{P}/u.test(form.password) },
]);
const submit = () => form.patch(route("frontend.account.password.update"), { preserveScroll: true, onSuccess: () => form.reset() });
</script>

<template>
    <section class="border-border bg-background rounded-2xl border p-6 shadow-sm md:p-8">
        <div class="mb-8">
            <h2 class="text-foreground text-xl font-bold">{{ t("labels.account.password_heading") }}</h2>
            <p class="text-muted-foreground mt-1 text-sm">{{ t("labels.account.password_description") }}</p>
        </div>
        <form class="max-w-xl space-y-6" @submit.prevent="submit">
            <label class="text-foreground block space-y-2 text-sm font-semibold"
                >{{ t("labels.account.current_password")
                }}<FormInput
                    v-model="form.current_password"
                    type="password"
                    autocomplete="current-password"
                    required
                    :invalid="Boolean(form.errors.current_password)"
                /><span v-if="form.errors.current_password" class="text-destructive text-xs" role="alert">{{
                    form.errors.current_password
                }}</span></label
            >
            <label class="text-foreground block space-y-2 text-sm font-semibold">
                {{ t("labels.form.new_password") }}
                <div class="group relative">
                    <FormInput
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        autocomplete="new-password"
                        required
                        :invalid="Boolean(form.errors.password)"
                        :placeholder="t('placeholders.password_min')"
                        :aria-describedby="
                            form.errors.password
                                ? 'account-password-error'
                                : props.passwordRequirementsEnabled
                                  ? 'account-password-requirements'
                                  : undefined
                        "
                    />
                    <Button
                        type="button"
                        variant="ghost"
                        class="text-muted-foreground hover:text-foreground absolute top-1/2 right-2 -translate-y-1/2 p-2"
                        :aria-label="showPassword ? t('labels.auth.hide_password') : t('labels.auth.show_password')"
                        @click="showPassword = !showPassword"
                    >
                        <EyeOff v-if="showPassword" class="h-5 w-5" />
                        <Eye v-else class="h-5 w-5" />
                    </Button>
                    <span v-if="form.errors.password" id="account-password-error" class="text-destructive relative z-30 text-xs" role="alert">{{
                        form.errors.password
                    }}</span>
                    <div
                        v-if="props.passwordRequirementsEnabled"
                        id="account-password-requirements"
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
            </label>
            <label class="text-foreground block space-y-2 text-sm font-semibold">
                {{ t("labels.form.password_confirmation") }}
                <div class="relative">
                    <FormInput
                        v-model="form.password_confirmation"
                        :type="showConfirmPassword ? 'text' : 'password'"
                        autocomplete="new-password"
                        required
                        :invalid="Boolean(form.errors.password_confirmation)"
                        :placeholder="t('placeholders.new_password_confirmation')"
                    />
                    <Button
                        type="button"
                        variant="ghost"
                        class="text-muted-foreground hover:text-foreground absolute top-1/2 right-2 -translate-y-1/2 p-2"
                        :aria-label="showConfirmPassword ? t('labels.auth.hide_password') : t('labels.auth.show_password')"
                        @click="showConfirmPassword = !showConfirmPassword"
                    >
                        <EyeOff v-if="showConfirmPassword" class="h-5 w-5" />
                        <Eye v-else class="h-5 w-5" />
                    </Button>
                </div>
                <span v-if="form.errors.password_confirmation" class="text-destructive text-xs" role="alert">{{
                    form.errors.password_confirmation
                }}</span>
            </label>
            <div class="border-border flex flex-col gap-3 border-t pt-6 sm:flex-row">
                <Button type="submit" variant="primary" :loading="form.processing"
                    ><KeyRound class="h-4 w-4" />{{ t("labels.account.menu.password") }}</Button
                >
                <Button type="button" variant="outline" @click="form.reset()">{{ t("labels.actions.cancel") }}</Button>
            </div>
        </form>
    </section>
</template>
