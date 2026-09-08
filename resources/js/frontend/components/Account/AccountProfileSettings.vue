<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import { Camera, Save, User } from "lucide-vue-next";
import { useI18n } from "vue-i18n";
import Button from "../UI/Button.vue";
import FormFile from "../UI/FormFile.vue";
import FormInput from "../UI/FormInput.vue";

const props = defineProps({ user: { type: Object, required: true } });
const emit = defineEmits(["saved", "cancel"]);
const { t } = useI18n();
const imagePreview = ref(null);
const fileInput = ref(null);
const form = useForm({ first_name: props.user.first_name, last_name: props.user.last_name, email: props.user.email, phone: props.user.phone || "", image: null });
const handleImageChange = (event) => {
    const file = event.target.files[0];
    if (!file) return;
    form.image = file;
    const reader = new FileReader();
    reader.onload = (loadEvent) => (imagePreview.value = loadEvent.target.result);
    reader.readAsDataURL(file);
};
const submit = () => form.post(route("frontend.account.update"), { preserveScroll: true, onSuccess: () => { imagePreview.value = null; form.image = null; emit("saved"); } });
</script>

<template>
    <section class="rounded-2xl border border-border bg-background p-6 shadow-sm md:p-8">
        <form class="space-y-8" @submit.prevent="submit">
            <div class="flex flex-col items-center gap-4 md:items-start">
                <div class="relative"><div class="from-secondary to-secondary/50 flex h-28 w-28 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br shadow-inner"><img v-if="imagePreview || user.image || user.profile_photo_url" :src="imagePreview || user.image || user.profile_photo_url" class="h-full w-full object-cover" /><User v-else class="text-muted-foreground h-12 w-12" /></div><Button type="button" class="absolute -right-1 -bottom-1 flex h-10 w-10 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-sm transition-transform hover:scale-105" :aria-label="t('labels.account.profile_photo')" @click="fileInput?.element?.click()"><Camera class="h-4 w-4" /></Button><FormFile ref="fileInput" class="hidden" accept="image/*" @change="handleImageChange" /></div>
                <div class="text-center md:text-left"><p class="text-foreground text-sm font-semibold">{{ t("labels.account.profile_photo") }}</p><p class="text-muted-foreground text-xs">{{ t("labels.account.photo_requirements") }}</p></div>
            </div>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <label class="space-y-2 text-sm font-semibold text-foreground">{{ t("labels.form.first_name") }}<FormInput v-model="form.first_name" required :invalid="Boolean(form.errors.first_name)" /><span v-if="form.errors.first_name" class="text-destructive text-xs">{{ form.errors.first_name }}</span></label>
                <label class="space-y-2 text-sm font-semibold text-foreground">{{ t("labels.form.last_name") }}<FormInput v-model="form.last_name" required :invalid="Boolean(form.errors.last_name)" /><span v-if="form.errors.last_name" class="text-destructive text-xs">{{ form.errors.last_name }}</span></label>
                <label class="space-y-2 text-sm font-semibold text-foreground">{{ t("labels.form.email") }}<FormInput v-model="form.email" type="email" required :invalid="Boolean(form.errors.email)" /><span v-if="form.errors.email" class="text-destructive text-xs">{{ form.errors.email }}</span></label>
                <label class="space-y-2 text-sm font-semibold text-foreground">{{ t("labels.form.phone") }}<FormInput v-model="form.phone" type="tel" :placeholder="t('placeholders.phone')" :invalid="Boolean(form.errors.phone)" /><span v-if="form.errors.phone" class="text-destructive text-xs">{{ form.errors.phone }}</span></label>
            </div>
            <div class="border-border flex flex-col gap-3 border-t pt-6 sm:flex-row"><Button type="submit" variant="primary" :loading="form.processing"><Save class="h-4 w-4" />{{ t("labels.actions.save") }}</Button><Button type="button" variant="outline" @click="emit('cancel')">{{ t("labels.actions.cancel") }}</Button></div>
        </form>
    </section>
</template>
