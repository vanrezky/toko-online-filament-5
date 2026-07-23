<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";

const { t } = useI18n();
</script>

<template>
    <Head :title="t('labels.auth.registration_closed_title')" />

    <div class="flex min-h-screen items-center justify-center bg-neutral-50 px-4 py-12">
        <div class="w-full max-w-lg rounded-3xl border border-neutral-200 bg-white p-8 text-center shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-primary">
                {{ t('labels.auth.private_store_badge') }}
            </p>
            <h1 class="mt-4 text-3xl font-bold text-neutral-900">
                {{ t('labels.auth.registration_closed_title') }}
            </h1>
            <p class="mt-4 text-sm leading-6 text-neutral-600">
                {{ t('labels.auth.registration_closed_description') }}
            </p>

            <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
                <Link
                    :href="route('frontend.login')"
                    class="inline-flex items-center justify-center rounded-full bg-primary px-6 py-3 text-sm font-semibold text-white transition hover:opacity-90"
                >
                    {{ t('labels.actions.login') }}
                </Link>
                <Link
                    :href="route('frontend.forgot-password')"
                    class="inline-flex items-center justify-center rounded-full border border-neutral-300 px-6 py-3 text-sm font-semibold text-neutral-700 transition hover:border-primary hover:text-primary"
                >
                    {{ t('labels.auth.forgot_password') }}
                </Link>
            </div>
        </div>
    </div>
</template>
