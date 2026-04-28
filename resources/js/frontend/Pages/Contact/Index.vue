<script setup>
import { useForm } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import TemplateWrapper from "../../components/TemplateWrapper.vue";

const props = defineProps({
    settings: Object,
});

const form = useForm({
    name: "",
    email: "",
    subject: "",
    message: "",
});

const submit = () => {
    form.post(route("frontend.contact.store"), {
        onSuccess: () => {
            toast.success("Pesan berhasil dikirim! Kami akan menghubungi Anda segera.");
            form.reset();
        },
        onError: () => {
            toast.error("Gagal mengirim pesan. Silakan periksa kembali isian Anda.");
        },
    });
};
</script>

<template>
    <TemplateWrapper
        :title="`Hubungi Kami - ${settings?.site_name || 'Toko Online'}`"
        :description="`Hubungi kami untuk pertanyaan, saran, atau bantuan.`"
        :keywords="'kontak, hubungi kami, customer service, bantuan'"
    >
        <!-- Breadcrumb -->
        <section class="py-4">
            <div class="container mx-auto px-4">
                <div class="text-sm text-muted-foreground">
                    <span class="text-foreground">Hubungi Kami</span>
                </div>
            </div>
        </section>

        <!-- Contact Section -->
        <section class="py-8 md:py-16">
            <div class="container mx-auto px-4">
                <div class="mx-auto max-w-2xl">
                    <!-- Header -->
                    <div class="mb-8 text-center">
                        <h1 class="text-2xl font-bold text-foreground md:text-3xl">
                            Hubungi Kami
                        </h1>
                        <p class="mt-2 text-sm text-muted-foreground">
                            Punya pertanyaan atau butuh bantuan? Kirim pesan kepada kami.
                        </p>
                    </div>

                    <!-- Form Card -->
                    <div class="rounded-2xl bg-white p-6 shadow-lg md:p-8">
                        <form @submit.prevent="submit" class="space-y-5">
                            <!-- Name -->
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">
                                    Nama Lengkap <span class="text-destructive">*</span>
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Masukkan nama Anda"
                                    class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm text-foreground shadow-sm transition-all duration-200 placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                    :class="form.errors.name && 'border-destructive focus:border-destructive focus:ring-destructive/20'"
                                />
                                <p v-if="form.errors.name" class="text-xs text-destructive">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <!-- Email -->
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">
                                    Email <span class="text-destructive">*</span>
                                </label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    placeholder="email@example.com"
                                    class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm text-foreground shadow-sm transition-all duration-200 placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                    :class="form.errors.email && 'border-destructive focus:border-destructive focus:ring-destructive/20'"
                                />
                                <p v-if="form.errors.email" class="text-xs text-destructive">
                                    {{ form.errors.email }}
                                </p>
                            </div>

                            <!-- Subject -->
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">
                                    Subjek <span class="text-destructive">*</span>
                                </label>
                                <input
                                    v-model="form.subject"
                                    type="text"
                                    placeholder="Apa yang ingin Anda tanyakan?"
                                    class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm text-foreground shadow-sm transition-all duration-200 placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                    :class="form.errors.subject && 'border-destructive focus:border-destructive focus:ring-destructive/20'"
                                />
                                <p v-if="form.errors.subject" class="text-xs text-destructive">
                                    {{ form.errors.subject }}
                                </p>
                            </div>

                            <!-- Message -->
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-foreground">
                                    Pesan <span class="text-destructive">*</span>
                                </label>
                                <textarea
                                    v-model="form.message"
                                    rows="5"
                                    placeholder="Tulis pesan Anda di sini..."
                                    class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm text-foreground shadow-sm transition-all duration-200 placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                    :class="form.errors.message && 'border-destructive focus:border-destructive focus:ring-destructive/20'"
                                ></textarea>
                                <p v-if="form.errors.message" class="text-xs text-destructive">
                                    {{ form.errors.message }}
                                </p>
                            </div>

                            <!-- Submit Button -->
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full rounded-xl bg-gradient-to-r from-primary to-primary/90 px-8 py-3.5 text-sm font-semibold text-primary-foreground shadow-lg shadow-primary/30 transition-all duration-300 hover:shadow-xl hover:shadow-primary/40 active:scale-95 disabled:opacity-70"
                            >
                                <span v-if="form.processing">Mengirim...</span>
                                <span v-else>Kirim Pesan</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </TemplateWrapper>
</template>
