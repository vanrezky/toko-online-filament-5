<script setup>
import Button from "@frontend/components/UI/Button.vue";
import { Link } from "@inertiajs/vue3";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import TemplateWrapper from "../../components/TemplateWrapper.vue";
import { ArrowLeft, ArrowRight, Calendar, Clock3, User } from "lucide-vue-next";
import { useI18n } from "vue-i18n";

const { t } = useI18n();

const props = defineProps({
    post: Object,
    relatedPosts: [Array, Object],
});

const shareStatus = ref("");
const readProgress = ref(0);
let shareStatusTimeout;

const readingMinutes = computed(() => {
    const words = (props.post.content || "").replace(/<[^>]*>/g, " ").trim().split(/\s+/).filter(Boolean).length;

    return Math.max(1, Math.ceil(words / 200));
});

const allRelatedPosts = computed(() => {
    const posts = Array.isArray(props.relatedPosts) ? props.relatedPosts : (props.relatedPosts?.data ?? []);

    return posts.slice(0, 4);
});

const updateReadProgress = () => {
    const scrollableHeight = document.documentElement.scrollHeight - window.innerHeight;
    readProgress.value = scrollableHeight > 0 ? Math.min(100, Math.round((window.scrollY / scrollableHeight) * 100)) : 0;
};

const markCopied = () => {
    shareStatus.value = t("labels.blog.link_copied");
    window.clearTimeout(shareStatusTimeout);
    shareStatusTimeout = window.setTimeout(() => (shareStatus.value = ""), 2500);
};

const share = async (platform) => {
    const url = window.location.href;
    const title = props.post.title;

    if (platform === "facebook") {
        window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`, "_blank", "noopener,noreferrer");
        return;
    }

    if (platform === "twitter") {
        window.open(`https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`, "_blank", "noopener,noreferrer");
        return;
    }

    try {
        if (navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(url);
        } else {
            const textArea = document.createElement("textarea");
            textArea.value = url;
            textArea.style.position = "fixed";
            textArea.style.opacity = "0";
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand("copy");
            document.body.removeChild(textArea);
        }
        markCopied();
    } catch {
        shareStatus.value = "";
    }
};

onMounted(() => {
    updateReadProgress();
    window.addEventListener("scroll", updateReadProgress, { passive: true });
});

onBeforeUnmount(() => {
    window.removeEventListener("scroll", updateReadProgress);
    window.clearTimeout(shareStatusTimeout);
});
</script>

<template>
    <TemplateWrapper :title="post.meta?.title || post.title" :description="post.meta?.description || post.excerpt" :keywords="post.meta?.keyword">
        <article class="min-h-screen bg-background">
            <div class="fixed inset-x-0 top-0 z-50 h-1 bg-border" aria-hidden="true">
                <div class="h-full bg-primary transition-[width] duration-150 motion-reduce:transition-none" :style="{ width: `${readProgress}%` }"></div>
            </div>

            <header class="relative h-[46vh] min-h-[25rem] overflow-hidden bg-foreground md:h-[58vh]">
                <img v-if="post.image_url" :src="post.image_url" :alt="post.title" fetchpriority="high" decoding="async" class="h-full w-full object-cover opacity-60" />
                <div class="absolute inset-0 bg-gradient-to-t from-foreground/95 via-foreground/40 to-transparent"></div>

                <div class="absolute inset-0 flex items-end">
                    <div class="container mx-auto w-full px-4 pb-8 md:pb-12">
                        <div class="max-w-4xl space-y-4">
                            <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3">
                                <Link
                                    :href="route('frontend.blog.index')"
                                    class="inline-flex items-center gap-2 rounded-full bg-foreground/45 px-3 py-2 text-sm font-semibold text-white transition-colors hover:bg-foreground/70 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-foreground"
                                >
                                    <ArrowLeft class="h-4 w-4" />
                                    {{ t("labels.actions.back_to_blog") }}
                                </Link>
                                <Link
                                    v-if="post.category?.slug"
                                    :href="route('frontend.blog.index', { category: post.category?.slug })"
                                    class="rounded-full bg-primary px-4 py-2 text-xs font-bold text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-foreground"
                                >
                                    {{ post.category?.name }}
                                </Link>
                            </div>
                            <h1 class="text-3xl leading-[1.05] font-bold tracking-tight text-white md:text-5xl lg:text-6xl">{{ post.title }}</h1>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-white/75">
                                <span v-if="post.author?.name" class="flex items-center gap-2"><User class="h-4 w-4" />{{ post.author.name }}</span>
                                <span class="flex items-center gap-2"><Calendar class="h-4 w-4" />{{ post.published_at }}</span>
                                <span class="flex items-center gap-2"><Clock3 class="h-4 w-4" />{{ t("labels.blog.read_time", { minutes: readingMinutes }) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <div class="container mx-auto px-4 py-12 md:py-16">
                <div class="mx-auto max-w-[72ch]">
                    <div
                        class="prose prose-lg prose-headings:scroll-mt-24 prose-headings:font-bold prose-headings:tracking-tight prose-headings:text-foreground prose-p:leading-8 prose-p:text-muted-foreground prose-a:font-semibold prose-a:text-primary prose-a:underline prose-a:decoration-primary/40 prose-a:underline-offset-4 prose-strong:text-foreground prose-li:marker:text-primary prose-img:rounded-xl prose-img:border prose-img:border-primary/15 max-w-none"
                        v-html="post.content"
                    ></div>

                    <div class="mt-12 flex flex-col justify-between gap-6 border-y border-border py-7 md:flex-row md:items-center" :class="!post.tags?.length && 'md:justify-end'">
                        <div v-if="post.tags && post.tags.length > 0" class="flex flex-wrap items-center gap-2">
                            <span class="mr-2 text-sm font-semibold text-foreground">{{ t("labels.blog.tags") }}</span>
                            <Link
                                v-for="tag in post.tags"
                                :key="tag"
                                :href="route('frontend.blog.index', { tag })"
                                class="rounded-full bg-secondary px-4 py-1.5 text-sm font-medium text-foreground transition-colors hover:bg-primary hover:text-primary-foreground"
                            >
                                #{{ tag }}
                            </Link>
                        </div>

                        <div class="flex flex-wrap items-center gap-2" :aria-label="t('labels.blog.share')">
                            <span class="mr-1 text-sm font-semibold text-foreground">{{ t("labels.blog.share") }}</span>
                            <Button @click="share('facebook')" :aria-label="t('labels.blog.share_on_facebook')" class="h-9 w-9 rounded-full bg-secondary text-foreground hover:bg-primary hover:text-primary-foreground">
                                <svg viewBox="0 0 24 24" class="h-[18px] w-[18px] fill-current" aria-hidden="true">
                                    <path d="M13.6 21v-8h2.7l.4-3h-3.1V8.1c0-.9.3-1.6 1.7-1.6H17V3.8c-.3 0-1.3-.1-2.5-.1-2.5 0-4.2 1.5-4.2 4.3V10H7.5v3h2.8v8h3.3Z" />
                                </svg>
                            </Button>
                            <Button @click="share('twitter')" :aria-label="t('labels.blog.share_on_twitter')" class="h-9 w-9 rounded-full bg-secondary text-foreground hover:bg-primary hover:text-primary-foreground">
                                <svg viewBox="0 0 24 24" class="h-[18px] w-[18px] fill-current" aria-hidden="true">
                                    <path d="M18.9 2H22l-6.8 7.8L23.2 22h-6.3L12 15.6 6.4 22H3.3l7.3-8.3L2.9 2h6.3L13.6 8l5.3-6Zm-1.1 18h1.7L8.3 3.9H6.5L17.8 20Z" />
                                </svg>
                            </Button>
                            <Button @click="share('copy')" :aria-label="t('labels.blog.copy_link')" class="h-9 w-9 rounded-full bg-secondary text-foreground hover:bg-primary hover:text-primary-foreground">
                                <svg viewBox="0 0 24 24" class="h-[18px] w-[18px] fill-none stroke-current stroke-[2]" aria-hidden="true">
                                    <path d="M10.5 13.5a4.25 4.25 0 0 0 6 0l2-2a4.25 4.25 0 0 0-6-6l-1.15 1.15" />
                                    <path d="M13.5 10.5a4.25 4.25 0 0 0-6 0l-2 2a4.25 4.25 0 0 0 6 6l1.15-1.15" />
                                </svg>
                            </Button>
                            <span aria-live="polite" class="min-h-5 text-primary text-sm font-semibold">{{ shareStatus }}</span>
                        </div>
                    </div>

                    <section v-if="allRelatedPosts.length > 0" class="mt-14 border-t border-border pt-8 md:mt-16" aria-labelledby="related-articles">
                        <div class="mb-7 flex items-end justify-between gap-4">
                            <div>
                                <p class="text-primary text-xs font-semibold tracking-[0.08em] uppercase">{{ t("labels.blog.keep_exploring") }}</p>
                                <h2 id="related-articles" class="text-foreground mt-1 text-2xl font-bold tracking-tight md:text-3xl">{{ t("labels.blog.related_articles") }}</h2>
                            </div>
                                <Link
                                v-if="post.category"
                                :href="route('frontend.blog.index', { category: post.category?.slug })"
                                class="inline-flex shrink-0 items-center gap-2 text-sm font-semibold text-primary hover:underline"
                            >
                                {{ t("labels.actions.view_all") }}
                                <ArrowRight class="h-4 w-4" />
                            </Link>
                        </div>

                        <div class="grid gap-x-6 gap-y-8 sm:grid-cols-2">
                            <Link
                                v-for="item in allRelatedPosts"
                                :key="item.slug"
                                :href="route('frontend.blog.show', item.slug)"
                                    class="group grid grid-cols-[7rem_1fr] gap-4 border-b border-border pb-6 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-4 sm:grid-cols-[8rem_1fr]"
                            >
                                <div class="aspect-[4/3] overflow-hidden bg-secondary">
                                    <img
                                        v-if="item.image_url"
                                        :src="item.image_url"
                                        :alt="item.title"
                                        loading="lazy"
                                        decoding="async"
                                        class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03] motion-reduce:transition-none"
                                    />
                                    <div v-else class="h-full w-full bg-muted"></div>
                                </div>
                                <div class="min-w-0">
                                    <span class="text-primary text-xs font-semibold">{{ item.category?.name }}</span>
                                    <h3 class="text-foreground mt-1 line-clamp-2 text-base leading-snug font-bold transition-colors group-hover:text-primary">{{ item.title }}</h3>
                                    <p class="text-muted-foreground mt-2 text-xs">{{ item.published_at }}</p>
                                </div>
                            </Link>
                        </div>
                    </section>

                    <div class="mt-12 border-t border-border pt-7 text-center">
                        <Link :href="route('frontend.blog.index')" class="inline-flex items-center gap-2 text-sm font-semibold text-foreground transition-colors hover:text-primary">
                            <ArrowLeft class="h-4 w-4" />
                            {{ t("labels.actions.back_to_blog") }}
                        </Link>
                    </div>
                </div>
            </div>
        </article>
    </TemplateWrapper>
</template>

<style scoped>
.prose blockquote {
    margin-inline: 0;
    border-left: 1px solid hsl(var(--primary));
    padding-left: 1.25rem;
    font-style: italic;
}
</style>
