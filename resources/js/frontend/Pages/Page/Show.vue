<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import TemplateWrapper from "../../components/TemplateWrapper.vue";

const props = defineProps({
    page: {
        type: Object,
        required: true,
    },
});

const { t } = useI18n();

const pageTitle = computed(() => props.page?.title?.trim() ?? "");
const pageContent = computed(() => (typeof props.page?.content === "string" ? props.page.content.trim() : ""));
const imageUrl = computed(() => props.page?.image_url ?? null);
</script>

<template>
    <TemplateWrapper :title="page?.meta?.title || pageTitle" :description="page?.meta?.description" :keywords="page?.meta?.keyword">
        <main class="bg-secondary/30 py-10 md:py-14">
            <article class="container">
                <div class="mx-auto max-w-4xl space-y-8 md:space-y-10">
                    <header class="border-b border-primary/25 pb-8 text-center">
                        <h1 class="break-words text-3xl font-bold tracking-tight text-foreground sm:text-4xl md:text-5xl">
                            {{ pageTitle }}
                        </h1>
                    </header>

                    <figure v-if="imageUrl" class="aspect-video overflow-hidden rounded-xl border border-primary/20 bg-secondary">
                        <img :src="imageUrl" :alt="pageTitle" loading="lazy" decoding="async" class="h-full w-full object-cover" />
                    </figure>

                    <div
                        v-if="pageContent"
                        class="page-content prose prose-base max-w-none break-words prose-headings:scroll-mt-24 prose-headings:font-bold prose-headings:text-foreground prose-p:text-muted-foreground prose-a:font-semibold prose-a:text-primary prose-a:underline prose-a:decoration-primary/40 prose-a:underline-offset-4 prose-strong:text-foreground prose-li:marker:text-primary prose-hr:border-primary/20 prose-img:rounded-xl prose-img:border prose-img:border-primary/15 sm:prose-lg"
                        v-html="pageContent"
                    />
                    <p v-else class="border-y border-primary/15 bg-background/70 px-5 py-4 text-center text-sm text-secondary-foreground" role="status">
                        {{ t("labels.page.content_unavailable") }}
                    </p>
                </div>
            </article>
        </main>
    </TemplateWrapper>
</template>

<style scoped>
.page-content :deep(img),
.page-content :deep(video),
.page-content :deep(iframe),
.page-content :deep(embed) {
    max-width: 100%;
}

.page-content :deep(img),
.page-content :deep(video) {
    height: auto;
}

.page-content :deep(table),
.page-content :deep(pre) {
    display: block;
    max-width: 100%;
    overflow-x: auto;
}

.page-content :deep(blockquote) {
    margin-inline: 0;
    border-inline-start: 1px solid hsl(var(--primary));
    padding-inline-start: 1.25rem;
    font-style: italic;
}

.page-content :deep(> :first-child) {
    margin-top: 0;
}

.page-content :deep(> :last-child) {
    margin-bottom: 0;
}

.page-content :deep(a:focus-visible) {
    border-radius: 0.125rem;
    outline: 2px solid hsl(var(--ring));
    outline-offset: 3px;
}
</style>
