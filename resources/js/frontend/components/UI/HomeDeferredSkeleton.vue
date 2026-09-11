<script setup>
import ProductCardSkeleton from "./ProductCardSkeleton.vue";
import Skeleton from "./Skeleton.vue";

defineProps({
    type: { type: String, required: true },
});
</script>

<template>
    <section v-if="type === 'sliders'" class="bg-background py-3 md:py-5" aria-hidden="true">
        <div class="container mx-auto px-4 md:px-8">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="index in 3" :key="index" class="relative aspect-[1.8/1] overflow-hidden rounded-2xl sm:aspect-[3.3/1] lg:aspect-[2.7/1]">
                    <Skeleton custom-class="absolute inset-0 h-full w-full rounded-2xl" />
                    <div class="absolute inset-x-5 bottom-5 space-y-2 sm:inset-x-8 sm:bottom-7 lg:inset-x-9 lg:bottom-8">
                        <Skeleton custom-class="h-3 w-20 rounded" />
                        <Skeleton custom-class="h-6 w-3/4 rounded sm:h-7" />
                        <Skeleton custom-class="h-3 w-2/3 rounded" />
                        <Skeleton custom-class="mt-3 h-9 w-28 rounded-lg" />
                    </div>
                </div>
            </div>
            <div class="mt-3 flex items-center justify-center gap-2">
                <Skeleton custom-class="h-2 w-6 rounded-full" />
                <Skeleton custom-class="h-2 w-2 rounded-full" />
                <Skeleton custom-class="h-2 w-2 rounded-full" />
            </div>
        </div>
    </section>

    <section v-else-if="type === 'categories'" class="bg-background py-3 sm:py-4 md:py-5" aria-hidden="true">
        <div class="container mx-auto px-4 md:px-8">
            <div class="mb-3 flex items-center justify-between gap-3 md:mb-4 md:gap-4">
                <Skeleton custom-class="h-6 w-32 rounded sm:h-7 sm:w-40" />
                <Skeleton custom-class="h-5 w-24 rounded" />
            </div>

            <div class="-mx-4 flex gap-1.5 overflow-hidden px-4 sm:gap-2 md:mx-0 md:grid md:grid-cols-6 md:gap-3 md:px-0 lg:grid-cols-8 xl:grid-cols-12">
                <div v-for="index in 10" :key="index" class="flex h-[4.75rem] min-w-[4.75rem] flex-1 flex-col items-center justify-center gap-1.5 rounded-xl border border-border px-1.5 py-2 sm:h-24 sm:min-w-[5.25rem] sm:gap-2 sm:px-2 sm:py-2.5 md:min-w-0 md:rounded-2xl md:py-3">
                    <Skeleton custom-class="h-8 w-8 rounded-full sm:h-9 sm:w-9 md:h-10 md:w-10" />
                    <Skeleton custom-class="h-2.5 w-12 rounded sm:w-14" />
                </div>
            </div>
        </div>
    </section>

    <section v-else-if="type === 'flashsales'" class="border-destructive/15 from-secondary via-background to-background relative overflow-hidden border-y bg-gradient-to-br py-8 md:py-12" aria-hidden="true">
        <div class="container relative mx-auto px-8">
            <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row md:mb-8 md:items-end">
                <div class="space-y-2">
                    <Skeleton custom-class="h-3 w-24 rounded" />
                    <Skeleton custom-class="h-7 w-36 rounded md:h-8 md:w-44" />
                    <Skeleton custom-class="h-4 w-64 rounded" />
                </div>
                <div class="flex items-center gap-2.5">
                    <Skeleton custom-class="hidden h-7 w-36 rounded sm:block" />
                    <Skeleton custom-class="h-10 w-10 rounded-full" />
                    <Skeleton custom-class="h-10 w-10 rounded-full" />
                </div>
            </div>

            <div class="-mx-4 flex gap-3 overflow-hidden px-4 sm:gap-4 md:gap-5 lg:mx-0 lg:grid lg:grid-cols-6 lg:gap-5 lg:px-0">
                <div v-for="index in 6" :key="index" class="w-44 shrink-0 sm:w-64 lg:w-auto">
                    <ProductCardSkeleton />
                </div>
            </div>

            <Skeleton custom-class="mx-auto mt-8 h-11 w-36 rounded" />
        </div>
    </section>

    <section v-else class="bg-background py-10 md:py-14" aria-hidden="true">
        <div class="container mx-auto px-4 md:px-8">
            <div class="mb-6 flex items-end justify-between gap-4 md:mb-8">
                <div class="space-y-2">
                    <Skeleton custom-class="h-8 w-40 rounded" />
                    <Skeleton custom-class="h-4 w-56 rounded" />
                </div>
                <Skeleton custom-class="h-5 w-28 rounded" />
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 xl:grid-cols-6">
                <ProductCardSkeleton v-for="index in 6" :key="index" />
            </div>
        </div>
    </section>
</template>
