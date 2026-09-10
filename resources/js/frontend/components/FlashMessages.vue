<script setup>
import { toast } from "vue-sonner";
import { usePage } from "@inertiajs/vue3";
import { watch } from "vue";

const page = usePage();

watch(
    [
        () => page.props.flash?.success,
        () => page.props.flash?.error,
        () => page.props.flash?.warning,
        () => page.props.flash?.info,
    ],
    ([success, error, warning, info]) => {
        if (success) {
            toast.success(success);
        }
        if (error) {
            toast.error(error);
        }
        if (warning) {
            toast.warning(warning);
        }
        if (info) {
            toast.info(info);
        }
    },
    { immediate: true },
);
</script>

<template>
    <!--  This component doesn't render anything visually, but it triggers toast notifications. -->
</template>
