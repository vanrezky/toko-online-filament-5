<script setup>
import Button from "@frontend/components/UI/Button.vue";
import FormInput from "../../components/UI/FormInput.vue";
import TemplateWrapper from "../../components/TemplateWrapper.vue";
import { Link, router } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import { ArrowRight, Calendar, Search } from "lucide-vue-next";
import debounce from "lodash/debounce";
import { useI18n } from "vue-i18n";
import PageShell from "@frontend/components/PageShell.vue";

const { t } = useI18n();
const props = defineProps({ posts: Object, categories: Array, filters: Object });
const search = ref(props.filters.search || "");
const selectedCategory = ref(props.filters.category || "");
const postItems = computed(() => props.posts?.data ?? []);
const featuredPost = computed(() => postItems.value[0] ?? null);
const readingQueue = computed(() => postItems.value.slice(1));
const paginationLinks = computed(() => {
    const links = props.posts?.meta?.links ?? props.posts?.links;

    return Array.isArray(links) ? links : [];
});

const applyFilters = debounce(() => {
    router.get(
        route("frontend.blog.index"),
        { search: search.value, category: selectedCategory.value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}, 500);

watch([search, selectedCategory], applyFilters);
</script>

<template>
    <TemplateWrapper :title="t('meta.blog.title')" :description="t('meta.blog.description')">
        <PageShell container>
            <header class="border-primary/20 grid gap-8 border-b pb-8 md:grid-cols-[1fr_20rem] md:items-end md:pb-10">
                <div class="max-w-2xl space-y-3">
                    <h1 class="text-foreground text-4xl font-bold tracking-tight md:text-6xl">{{ t("meta.blog.title") }}</h1>
                    <p class="text-muted-foreground max-w-xl text-base leading-relaxed md:text-lg">{{ t("meta.blog.description") }}</p>
                </div>
                <FormInput v-model="search" type="search" :placeholder="t('placeholders.search_articles')" class="bg-background py-3.5">
                    <template #prefix><Search class="text-muted-foreground absolute top-1/2 left-4 h-4 w-4 -translate-y-1/2" /></template>
                </FormInput>
            </header>

            <nav class="no-scrollbar -mx-4 flex gap-2 overflow-x-auto px-4 py-6 md:mx-0 md:px-0" :aria-label="t('labels.filters.all')">
                <Button
                    @click="selectedCategory = ''"
                    :aria-pressed="selectedCategory === ''"
                    class="shrink-0 rounded-full px-4 py-2 text-sm"
                    :class="selectedCategory === '' ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground hover:bg-primary/10'"
                    >{{ t("labels.filters.all") }}</Button
                >
                <Button
                    v-for="cat in categories"
                    :key="cat.slug"
                    @click="selectedCategory = cat.slug"
                    :aria-pressed="selectedCategory === cat.slug"
                    class="shrink-0 rounded-full px-4 py-2 text-sm"
                    :class="
                        selectedCategory === cat.slug ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground hover:bg-primary/10'
                    "
                    >{{ cat.name }}</Button
                >
            </nav>

            <section v-if="featuredPost" class="space-y-10" :aria-label="t('labels.blog.articles')">
                <article class="border-primary/20 bg-background grid overflow-hidden border-y md:grid-cols-[1.15fr_0.85fr]">
                    <Link :href="route('frontend.blog.show', featuredPost.slug)" class="bg-secondary aspect-[16/10] overflow-hidden md:aspect-auto">
                        <img
                            v-if="featuredPost.image_url"
                            :src="featuredPost.image_url"
                            :alt="featuredPost.title"
                            fetchpriority="high"
                            class="h-full w-full object-cover transition-transform duration-500 hover:scale-[1.02] motion-reduce:transition-none"
                        />
                        <div v-else class="text-muted-foreground flex h-full min-h-64 items-center justify-center"><Search class="h-8 w-8" /></div>
                    </Link>
                    <div class="bg-primary text-primary-foreground flex min-w-0 flex-col justify-between p-6 md:p-9">
                        <div class="space-y-5">
                            <p class="text-primary-foreground/75 text-xs font-semibold tracking-[0.08em] uppercase">{{ t("labels.blog.featured") }}</p>
                            <div class="text-primary-foreground/75 flex flex-wrap items-center gap-x-3 gap-y-2 text-xs">
                                <span class="text-primary-foreground font-semibold">{{ featuredPost.category?.name }}</span
                                ><span class="flex items-center gap-1"><Calendar class="h-3.5 w-3.5" />{{ featuredPost.published_at }}</span>
                            </div>
                            <Link
                                :href="route('frontend.blog.show', featuredPost.slug)"
                                class="focus-visible:ring-primary-foreground block focus-visible:ring-2 focus-visible:outline-none"
                                ><h2 class="text-primary-foreground text-2xl leading-tight font-bold tracking-tight md:text-4xl">
                                    {{ featuredPost.title }}
                                </h2></Link
                            >
                            <p class="text-primary-foreground/80 line-clamp-4 text-sm leading-relaxed md:text-base">{{ featuredPost.excerpt }}</p>
                        </div>
                        <Link
                            :href="route('frontend.blog.show', featuredPost.slug)"
                            class="text-primary-foreground mt-8 inline-flex w-fit items-center gap-2 text-sm font-semibold underline underline-offset-4"
                            >{{ t("labels.actions.read_more") }}<ArrowRight class="h-4 w-4"
                        /></Link>
                    </div>
                </article>

                <div v-if="readingQueue.length" class="space-y-6">
                    <div class="flex items-end justify-between gap-4 border-b border-border pb-4">
                        <div>
                            <p class="text-primary text-xs font-semibold tracking-[0.08em] uppercase">{{ t("labels.blog.keep_exploring") }}</p>
                            <h2 class="text-foreground mt-1 text-2xl font-bold tracking-tight md:text-3xl">{{ t("labels.blog.latest_articles") }}</h2>
                        </div>
                    </div>
                    <div class="grid gap-x-8 gap-y-10 md:grid-cols-2">
                    <article
                        v-for="post in readingQueue"
                        :key="post.id"
                        class="group border-border grid min-w-0 grid-cols-[8rem_1fr] gap-4 border-b pb-6 sm:grid-cols-[10rem_1fr]"
                    >
                        <Link :href="route('frontend.blog.show', post.slug)" class="bg-secondary aspect-[4/3] overflow-hidden"
                            ><img
                                v-if="post.image_url"
                                :src="post.image_url"
                                :alt="post.title"
                                loading="lazy"
                                decoding="async"
                                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03] motion-reduce:transition-none"
                        /></Link>
                        <div class="flex min-w-0 flex-col">
                            <div class="text-primary mb-2 text-xs font-semibold">{{ post.category?.name }}</div>
                            <Link
                                :href="route('frontend.blog.show', post.slug)"
                                class="focus-visible:ring-primary focus-visible:ring-2 focus-visible:outline-none"
                                ><h2 class="text-foreground group-hover:text-primary line-clamp-2 text-lg leading-snug font-bold">
                                    {{ post.title }}
                                </h2></Link
                            >
                            <p class="text-muted-foreground mt-2 line-clamp-2 text-sm leading-relaxed">{{ post.excerpt }}</p>
                            <div class="mt-auto flex items-center justify-between gap-3 pt-3">
                                <span class="text-muted-foreground text-xs">{{ post.published_at }}</span>
                                <Link :href="route('frontend.blog.show', post.slug)" class="text-primary text-xs font-semibold hover:underline">
                                    {{ t("labels.blog.continue_reading") }}
                                </Link>
                            </div>
                        </div>
                    </article>
                    </div>
                </div>
            </section>

            <section v-else class="border-primary/15 bg-background border-y px-5 py-16 text-center">
                <Search class="text-primary mx-auto h-8 w-8" />
                <h2 class="text-foreground mt-5 text-xl font-bold">{{ t("labels.blog.empty_title") }}</h2>
                <p class="text-muted-foreground mt-2 text-sm">{{ t("labels.blog.empty_description") }}</p>
                <Button
                    @click="
                        search = '';
                        selectedCategory = '';
                    "
                    class="bg-primary text-primary-foreground mt-6"
                    >{{ t("labels.actions.reset_filters") }}</Button
                >
            </section>

            <nav v-if="paginationLinks.length > 3" class="mt-10 flex justify-center" :aria-label="t('labels.blog.pagination')">
                <div class="flex flex-wrap justify-center gap-2">
                    <Link
                        v-for="(link, index) in paginationLinks"
                        :key="index"
                        :href="link.url || '#'"
                        v-html="link.label"
                        class="min-h-10 min-w-10 rounded-full px-3 py-2 text-center text-sm font-semibold"
                        :class="
                            link.active
                                ? 'bg-primary text-primary-foreground'
                                : link.url
                                  ? 'bg-background text-foreground hover:bg-primary/10'
                                  : 'pointer-events-none opacity-40'
                        "
                    />
                </div>
            </nav>
        </PageShell>

    </TemplateWrapper>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
