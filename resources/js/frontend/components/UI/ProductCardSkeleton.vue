<script setup>
import Skeleton from "./Skeleton.vue";
</script>

<template>
    <article class="border-border bg-background flex h-full flex-col overflow-hidden rounded-xl border" aria-hidden="true">
        <div class="relative aspect-square overflow-hidden bg-secondary/35 p-1.5 sm:p-2">
            <Skeleton custom-class="h-full w-full rounded-lg" />
            <Skeleton custom-class="absolute top-3 left-3 h-5 w-11 rounded-full" />
            <Skeleton custom-class="absolute top-3 right-3 h-8 w-8 rounded-full sm:h-9 sm:w-9" />
        </div>

        <div class="flex grow flex-col p-3 sm:p-3.5">
            <div class="flex min-h-10 flex-col justify-center gap-1.5">
                <Skeleton custom-class="h-3.5 w-full rounded" />
                <Skeleton custom-class="h-3.5 w-4/5 rounded" />
            </div>

            <div class="mt-2 flex items-center gap-1">
                <Skeleton custom-class="h-3.5 w-3.5 rounded-full" />
                <Skeleton custom-class="h-3 w-8 rounded" />
                <Skeleton custom-class="h-3 w-7 rounded" />
            </div>

            <div class="mt-auto flex items-end pt-3">
                <div class="flex w-full flex-col gap-1">
                    <Skeleton custom-class="h-4 w-24 rounded" />
                    <Skeleton custom-class="h-3 w-16 rounded" />
                </div>
            </div>
        </div>
    </article>
</template>
