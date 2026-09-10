import { markRaw } from "vue";
import CategoryMenu from "../../components/UI/CategoryMenu.vue";
import FeaturedProducts from "../../components/UI/FeaturedProducts.vue";
import FlashSaleSection from "../../components/UI/FlashSaleSection.vue";
import HeroCarousel from "../../components/UI/HeroCarousel.vue";
import HeroSection from "../../components/UI/HeroSection.vue";
import HomeProductsSection from "../../components/UI/HomeProductsSection.vue";
import HomeTrustStrip from "../../components/UI/HomeTrustStrip.vue";
import NewsletterSection from "../../components/UI/NewsletterSection.vue";
import VoucherSection from "../../components/UI/VoucherSection.vue";

const LEGACY_SECTION_TYPES = [
    "hero",
    "flash_sale",
    "category_menu",
    "hero_carousel",
    "featured_products",
    "products_grid",
    "vouchers",
    "newsletter",
    "trust_strip",
];

const FALLBACK_SECTION_TYPES = ["hero_carousel", "vouchers", "trust_strip"];

const registry = {
    hero: {
        component: markRaw(HeroSection),
        props: ({ template }) => ({ template }),
    },
    flash_sale: {
        component: markRaw(FlashSaleSection),
        deferredProps: "flashsales",
        available: ({ flashsales }) => flashsales === undefined || Boolean(flashsales),
        props: ({ flashsales, template }) => ({ flashsales, template }),
    },
    category_menu: {
        component: markRaw(CategoryMenu),
        deferredProps: "categories",
        props: ({ categories, filters, template }) => ({
            categories,
            activeCategory: filters?.category,
            template,
        }),
    },
    hero_carousel: {
        component: markRaw(HeroCarousel),
        deferredProps: "sliders",
        props: ({ template, sliders }) => ({ template, slides: sliders }),
    },
    featured_products: {
        deferredProps: "products",
        component: markRaw(FeaturedProducts),
        available: ({ filters }) => !filters?.category,
        props: ({ products, template }) => ({ products, template }),
    },
    products_grid: {
        deferredProps: "products",
        component: markRaw(HomeProductsSection),
        props: ({ products, filters, template }) => ({ products, filters, template }),
    },
    vouchers: {
        component: markRaw(VoucherSection),
        props: ({ template }) => ({ template }),
    },
    newsletter: {
        component: markRaw(NewsletterSection),
        props: ({ template }) => ({ template }),
    },
    trust_strip: {
        component: markRaw(HomeTrustStrip),
        props: () => ({}),
    },
};

const configuredSections = (template) => (Array.isArray(template?.sections) ? template.sections : [])
    .filter((section) => section?.type && section.is_active !== false);

export function resolveHomeSections(props) {
    const configured = configuredSections(props.template);
    const hasTemplateSections = Array.isArray(props.template?.sections);
    const sourceSections = hasTemplateSections
        ? [
              ...configured,
              ...FALLBACK_SECTION_TYPES
                  .filter((type) => !configured.some((section) => section.type === type))
                  .map((type) => ({ type, fallback: true })),
          ]
        : LEGACY_SECTION_TYPES.map((type) => ({ type, fallback: true }));

    return sourceSections
        .map((section, index) => {
            const definition = registry[section.type];

            if (!definition || (definition.available && !definition.available(props))) {
                return null;
            }

            return {
                key: section.uuid || `${section.type}-${index}`,
                type: section.type,
                component: definition.component,
                deferredProps: definition.deferredProps,
                props: definition.props(props),
            };
        })
        .filter(Boolean);
}

export { LEGACY_SECTION_TYPES, registry };
