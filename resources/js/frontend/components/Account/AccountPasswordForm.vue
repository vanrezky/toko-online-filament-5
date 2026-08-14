<script setup>
import { useForm } from "@inertiajs/vue3";
import { KeyRound } from "lucide-vue-next";
import { useI18n } from "vue-i18n";
import Button from "../UI/Button.vue";
import FormInput from "../UI/FormInput.vue";

const { t } = useI18n();
const form = useForm({ current_password: "", password: "", password_confirmation: "" });
const submit = () => form.patch(route("frontend.account.password.update"), { preserveScroll: true, onSuccess: () => form.reset() });
</script>

<template>
    <section class="rounded-2xl border border-border bg-background p-6 shadow-sm md:p-8">
        <div class="mb-8">
            <h2 class="text-foreground text-xl font-bold">{{ t("labels.account.password_heading") }}</h2>
            <p class="text-muted-foreground mt-1 text-sm">{{ t("labels.account.password_description") }}</p>
        </div>
        <form class="max-w-xl space-y-6" @submit.prevent="submit">
            <label class="block space-y-2 text-sm font-semibold text-foreground">{{ t("labels.account.current_password") }}<FormInput v-model="form.current_password" type="password" autocomplete="current-password" required :invalid="Boolean(form.errors.current_password)" /><span v-if="form.errors.current_password" class="text-destructive text-xs" role="alert">{{ form.errors.current_password }}</span></label>
            <label class="block space-y-2 text-sm font-semibold text-foreground">{{ t("labels.form.new_password") }}<FormInput v-model="form.password" type="password" autocomplete="new-password" required :invalid="Boolean(form.errors.password)" :placeholder="t('placeholders.password_min')" /><span v-if="form.errors.password" class="text-destructive text-xs" role="alert">{{ form.errors.password }}</span></label>
            <label class="block space-y-2 text-sm font-semibold text-foreground">{{ t("labels.form.password_confirmation") }}<FormInput v-model="form.password_confirmation" type="password" autocomplete="new-password" required :invalid="Boolean(form.errors.password_confirmation)" :placeholder="t('placeholders.new_password_confirmation')" /><span v-if="form.errors.password_confirmation" class="text-destructive text-xs" role="alert">{{ form.errors.password_confirmation }}</span></label>
            <div class="border-border flex flex-col gap-3 border-t pt-6 sm:flex-row">
                <Button type="submit" variant="primary" :loading="form.processing"><KeyRound class="h-4 w-4" />{{ t("labels.account.menu.password") }}</Button>
                <Button type="button" variant="outline" @click="form.reset()">{{ t("labels.actions.cancel") }}</Button>
            </div>
        </form>
    </section>
</template>
