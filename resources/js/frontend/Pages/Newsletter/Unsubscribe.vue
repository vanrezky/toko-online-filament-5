<script setup>
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    status: {
        type: String,
        required: true,
    },
    email: {
        type: String,
        default: "",
    },
});

const isUnsubscribed = props.status === "unsubscribed";
const isAlreadyUnsubscribed = props.status === "already_unsubscribed";
</script>

<template>
    <TemplateWrapper
        title="Newsletter - Kelola Langganan"
        description="Kelola langganan newsletter Anda"
    >
        <section class="py-12 md:py-20">
            <div class="container mx-auto px-4">
                <div class="mx-auto max-w-lg">
                    <div class="rounded-2xl bg-white p-8 shadow-lg md:p-10">
                        <!-- Success Unsubscribe -->
                        <div v-if="isUnsubscribed" class="text-center">
                            <div
                                class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-green-100"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-8 w-8 text-green-600"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <h1 class="mb-3 text-2xl font-bold text-foreground">Berhasil Unsubscribe</h1>
                            <p class="mb-2 text-sm text-muted-foreground">
                                Email <strong>{{ email }}</strong> telah berhasil dihapus dari daftar newsletter kami.
                            </p>
                            <p class="text-sm text-muted-foreground">
                                Anda tidak akan lagi menerima email newsletter dari kami.
                            </p>
                        </div>

                        <!-- Already Unsubscribed -->
                        <div v-else-if="isAlreadyUnsubscribed" class="text-center">
                            <div
                                class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-yellow-100"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-8 w-8 text-yellow-600"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                    />
                                </svg>
                            </div>
                            <h1 class="mb-3 text-2xl font-bold text-foreground">Sudah Unsubscribe</h1>
                            <p class="mb-2 text-sm text-muted-foreground">
                                Email <strong>{{ email }}</strong> sudah tidak berlangganan newsletter kami.
                            </p>
                            <p class="text-sm text-muted-foreground">
                                Jika Anda ingin berlangganan kembali, silakan daftar di halaman utama.
                            </p>
                        </div>

                        <!-- Back to Home -->
                        <div class="mt-8 text-center">
                            <Link
                                :href="route('frontend.home')"
                                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-primary to-primary/90 px-6 py-3 text-sm font-semibold text-primary-foreground shadow-lg shadow-primary/30 transition-all duration-300 hover:shadow-xl hover:shadow-primary/40"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-4 w-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                                    />
                                </svg>
                                Kembali ke Beranda
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </TemplateWrapper>
</template>