<script setup>
import Button from "@frontend/components/UI/Button.vue";
import { useForm } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import { ref } from "vue";
import TemplateWrapper from "../../components/TemplateWrapper.vue";
import PageShell from "../../components/PageShell.vue";
import FormInput from "../../components/UI/FormInput.vue";
import FormTextarea from "../../components/UI/FormTextarea.vue";
import Card from "../../components/UI/Card.vue";
import { useI18n } from "vue-i18n";

const { t } = useI18n();

const props = defineProps({
    settings: Object,
});

const form = useForm({
    name: "",
    email: "",
    subject: "",
    message: "",
});
const nameInput = ref(null);
const emailInput = ref(null);
const subjectInput = ref(null);
const messageInput = ref(null);

const submit = () => {
    form.post(route("frontend.contact.store"), {
        onSuccess: () => {
            toast.success(t("messages.success.contact_sent"));
            form.reset();
        },
        onError: (errors) => {
            if (errors.name) nameInput.value?.focus();
            else if (errors.email) emailInput.value?.focus();
            else if (errors.subject) subjectInput.value?.focus();
            else if (errors.message) messageInput.value?.$el?.focus();
            toast.error(t("messages.error.generic"));
        },
    });
};
</script>

<template>
    <TemplateWrapper
        :shell="false"
        :title="`${t('labels.contact.heading')} - ${settings?.site_name || 'Toko Online'}`"
        :description="t('labels.contact.subheading')"
        :keywords="'kontak, hubungi kami, customer service, bantuan'"
    >

        <PageShell container>
            <div class="mx-auto max-w-2xl">
                <!-- Header -->
                <div class="mb-8 text-center">
                    <h1 class="text-foreground text-2xl font-bold md:text-3xl">
                        {{ t("labels.contact.heading") }}
                    </h1>
                    <p class="text-muted-foreground mt-2 text-sm">
                        {{ t("labels.contact.subheading") }}
                    </p>
                </div>

                <!-- Form Card -->
                <Card variant="elevated" class="rounded-2xl border-0 p-6 shadow-lg sm:p-8">
                    <form class="space-y-6" @submit.prevent="submit">
                        <!-- Name -->
                        <div class="space-y-1.5">
                            <label for="contact-name" class="text-foreground text-sm font-semibold">
                                {{ t("labels.form.full_name") }} <span class="text-destructive">*</span>
                            </label>
                            <FormInput
                                ref="nameInput"
                                id="contact-name"
                                v-model="form.name"
                                type="text"
                                autocomplete="name"
                                required
                                :placeholder="t('placeholders.full_name')"
                                :invalid="Boolean(form.errors.name)"
                                :aria-describedby="form.errors.name ? 'contact-name-error' : undefined"
                                class="bg-background"
                            />
                            <p v-if="form.errors.name" id="contact-name-error" class="text-destructive text-xs" role="alert">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <!-- Email -->
                        <div class="space-y-1.5">
                            <label for="contact-email" class="text-foreground text-sm font-semibold">
                                {{ t("labels.form.email") }} <span class="text-destructive">*</span>
                            </label>
                            <FormInput
                                ref="emailInput"
                                id="contact-email"
                                v-model="form.email"
                                type="email"
                                autocomplete="email"
                                required
                                :placeholder="t('placeholders.email')"
                                :invalid="Boolean(form.errors.email)"
                                :aria-describedby="form.errors.email ? 'contact-email-error' : undefined"
                                class="bg-background"
                            />
                            <p v-if="form.errors.email" id="contact-email-error" class="text-destructive text-xs" role="alert">
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <!-- Subject -->
                        <div class="space-y-1.5">
                            <label for="contact-subject" class="text-foreground text-sm font-semibold">
                                {{ t("labels.form.subject") }} <span class="text-destructive">*</span>
                            </label>
                            <FormInput
                                ref="subjectInput"
                                id="contact-subject"
                                v-model="form.subject"
                                type="text"
                                autocomplete="off"
                                required
                                :placeholder="t('placeholders.subject')"
                                :invalid="Boolean(form.errors.subject)"
                                :aria-describedby="form.errors.subject ? 'contact-subject-error' : undefined"
                                class="bg-background"
                            />
                            <p v-if="form.errors.subject" id="contact-subject-error" class="text-destructive text-xs" role="alert">
                                {{ form.errors.subject }}
                            </p>
                        </div>

                        <!-- Message -->
                        <div class="space-y-1.5">
                            <label for="contact-message" class="text-foreground text-sm font-semibold">
                                {{ t("labels.form.message") }} <span class="text-destructive">*</span>
                            </label>
                            <FormTextarea
                                ref="messageInput"
                                id="contact-message"
                                v-model="form.message"
                                rows="5"
                                required
                                :placeholder="t('placeholders.message')"
                                :invalid="Boolean(form.errors.message)"
                                :aria-describedby="form.errors.message ? 'contact-message-error' : undefined"
                                class="bg-background"
                            />
                            <p v-if="form.errors.message" id="contact-message-error" class="text-destructive text-xs" role="alert">
                                {{ form.errors.message }}
                            </p>
                        </div>

                        <!-- Submit Button -->
                        <Button
                            type="submit"
                            :loading="form.processing"
                            class="bg-primary text-primary-foreground hover:bg-primary/90 w-full rounded-xl px-8 py-3.5 text-sm font-semibold shadow-sm transition-shadow hover:shadow-md"
                        >
                            {{ t("labels.actions.send_message") }}
                        </Button>
                    </form>
                </Card>
            </div>
        </PageShell>
    </TemplateWrapper>
</template>
