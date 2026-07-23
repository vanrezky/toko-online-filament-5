<script setup>
import Button from "@frontend/components/UI/Button.vue";
import { ref, computed } from 'vue';
import TemplateWrapper from '../../components/TemplateWrapper.vue';
import PageShell from '../../components/PageShell.vue';
import { ChevronDown, HelpCircle } from 'lucide-vue-next';
import Card from '../../components/UI/Card.vue';
import { useI18n } from "vue-i18n";

const { t } = useI18n();

const props = defineProps({
  faqs: [Object, Array]
});

const items = computed(() => {
  if (Array.isArray(props.faqs)) return props.faqs;
  return props.faqs?.data || [];
});

const openIndex = ref(null);

const toggle = (index) => {
  openIndex.value = openIndex.value === index ? null : index;
};
</script>

<template>
  <TemplateWrapper :shell="false"
    :title="t('meta.faq.title')"
    :description="t('meta.faq.description')"
  >
    <PageShell
      container
      :title="t('labels.faq.page_heading')"
      :description="t('labels.faq.page_subheading')"
    >
      <div class="mx-auto max-w-4xl space-y-3">
        <Card
          v-for="(faq, index) in items"
          :key="index"
          as="article"
          class="overflow-hidden"
        >
          <Button
            @click="toggle(index)"
            class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left text-sm font-semibold transition-colors hover:bg-muted/50 md:px-6"
            :class="{ 'bg-muted/50': openIndex === index }"
          >
            <span>{{ faq.question }}</span>
            <ChevronDown
              class="h-5 w-5 shrink-0 text-muted-foreground transition-transform duration-300"
              :class="{ 'rotate-180': openIndex === index }"
            />
          </Button>

          <div
            v-show="openIndex === index"
            class="prose prose-sm max-w-none border-t border-border px-5 py-4 leading-relaxed text-muted-foreground md:px-6"
            v-html="faq.answer"
          ></div>
        </Card>

        <Card variant="elevated" class="flex flex-col items-center gap-5 p-6 text-center md:p-8">
          <div class="bg-primary/10 text-primary flex h-12 w-12 items-center justify-center rounded-full">
            <HelpCircle class="h-6 w-6" />
          </div>
          <div class="space-y-1">
            <h2 class="text-xl font-bold text-foreground">{{ t('labels.faq.still_have_questions') }}</h2>
            <p class="text-sm text-muted-foreground">{{ t('labels.faq.contact_team') }}</p>
          </div>
          <a
            href="mailto:support@example.com"
            class="bg-primary text-primary-foreground hover:bg-primary/90 inline-flex rounded-lg px-5 py-2.5 text-sm font-semibold transition-colors"
          >
            {{ t('labels.actions.contact_support') }}
          </a>
        </Card>
      </div>
    </PageShell>
  </TemplateWrapper>
</template>

<style scoped>
.prose :first-child {
  margin-top: 0;
}
.prose :last-child {
  margin-bottom: 0;
}
</style>
