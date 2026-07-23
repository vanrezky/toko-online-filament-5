---
target: resources/js/frontend/pages/Home/Index.vue
total_score: 16
p0_count: 0
p1_count: 3
timestamp: 2026-07-22T06-51-02Z
slug: resources-js-frontend-pages-home-index-vue
---
Method: dual-agent (A: /root/design_review · B: /root/evidence_review)

## Design Health Score

| # | Heuristic | Score | Key Issue |
|---|---|---:|---|
| 1 | Visibility of System Status | 2 | Voucher application has no visible outcome on the home page. |
| 2 | Match System / Real World | 2 | Commerce vocabulary is clear, but English carousel labels and emoji reduce coherence. |
| 3 | User Control and Freedom | 2 | Horizontal rails have controls, but discovery is weak and voucher intent is not fulfilled. |
| 4 | Consistency and Standards | 1 | Gradients, emoji, cards, and stark footer styling form an inconsistent vocabulary. |
| 5 | Error Prevention | 1 | “Gunakan” implies voucher application without a clear resulting state. |
| 6 | Recognition Rather Than Recall | 2 | Product facts are visible, but multiple promotion modules compete for attention. |
| 7 | Flexibility and Efficiency | 2 | Search and routes help, but the hierarchy slows direct product/category browsing. |
| 8 | Aesthetic and Minimalist Design | 1 | Decorative urgency, gradients, and repeated CTAs conflict with a premium position. |
| 9 | Error Recovery | 2 | Newsletter feedback exists; voucher loading failure has no customer-facing recovery. |
| 10 | Help and Documentation | 1 | Payment, delivery, return, and support reassurance are absent near the first purchase decision. |
| **Total** | | **16/40** | **Critical—requires a focused hierarchy and trust pass** |

## Anti-Patterns Verdict

**LLM assessment:** Medium-to-high AI-template risk. The page combines gradient washes, blurred blobs, floating emoji, bouncing and pulsing decor, generic promo metrics, rounded cards, and repeated “Lihat Semua” prompts. It reads more like a busy marketplace campaign than a distinctive premium independent store.

**Deterministic scan:** 0 findings in `resources/js/frontend/pages/Home/Index.vue`; no detector rules or file locations were reported. This does not contradict the design review: the detector did not flag structural anti-patterns, while the review identified composition and hierarchy problems.

**Visual overlays:** No reliable user-visible overlay is available. Browser inspection could not start because the local Google Chrome executable is unavailable; therefore no tab, injection, screenshot, or console evidence was produced.

## Overall Impression

The page has solid shopping primitives and configurable merchandising, but it asks customers to process too many promotions before establishing why this store is trustworthy and premium. The single biggest opportunity is to replace promotion-first density with a deliberate journey: reassurance, curation, then purchase momentum.

## What's Working

- Product cards expose price, discount savings, ratings, review counts, sold counts, and categories before the detail page.
- Hero and section content are configurable through template data, so merchandising can become specific to the store rather than hardcoded.
- Category routes, wishlist, cart support, and responsive product browsing support real shopping tasks.

## Priority Issues

### [P1] Promotion density overwhelms premium curation

**Why it matters:** Hero statistics, voucher offers, flash-sale urgency, featured products, and a full product grid compete for the same attention. Customers see pressure before trust, making the store feel interchangeable with a marketplace.

**Fix:** Give the first viewport one value proposition and one focused collection. Move flash sales and vouchers into quieter lower-page modules with a clear purpose.

**Suggested command:** `/impeccable layout resources/js/frontend/pages/Home/Index.vue`

### [P1] Voucher CTA promises an outcome the page does not show

**Why it matters:** “Gunakan” suggests immediate application, but the home surface does not communicate an applied voucher, a cart change, or where the benefit will appear. This erodes checkout trust.

**Fix:** Either make the action truthful—such as “Salin kode” and “Lihat syarat”—or wire it to a visible cart/checkout state that explains when the discount applies.

**Suggested command:** `/impeccable harden resources/js/frontend/components/UI/VoucherSection.vue`

### [P1] Trust proof arrives too late

**Why it matters:** A new customer is asked to browse and buy without concise evidence about payment safety, delivery, returns, product quality, or support.

**Fix:** Add a restrained, factual three-proof strip near the hero or first collection. Only use operational claims the business can substantiate.

**Suggested command:** `/impeccable shape storefront trust proof`

### [P2] Selected category is not visibly recognized

**Why it matters:** `Index.vue` does not pass `filters?.category` to `CategoryMenu`, so customers browsing a filtered category lose contextual confirmation.

**Fix:** Pass `:active-category="filters?.category"` and ensure the selected rail item and product heading agree.

**Suggested command:** `/impeccable polish resources/js/frontend/pages/Home/Index.vue`

### [P2] Decorative motion and emoji weaken polish

**Why it matters:** Bouncing/pulsing emoji, glow blobs, hover scaling, and urgency styling produce a campaign-template feel and lack reduced-motion handling.

**Fix:** Remove decorative animation, reserve motion for feedback, add reduced-motion alternatives, and use branded imagery or a purposeful illustration instead.

**Suggested command:** `/impeccable quieter resources/js/frontend/components/UI/HeroSection.vue`

## Persona Red Flags

- **Jordan, goal-oriented repeat buyer:** must pass through several promotion modules before reaching the complete product grid; horizontal rails slow direct comparison.
- **Casey, trust-first mobile buyer:** sees generic “70%” and countdown urgency before delivery, payment-safety, return, or service proof; dense two-column cards and horizontal rails increase mobile scanning effort.
- **Riley, edge-case shopper:** voucher use has no clear success state, and voucher-fetch failure only logs to the console, leaving customers without recovery guidance.

## Minor Observations

- Hero receives secondary CTA props but does not render a secondary CTA.
- Carousel `aria-label` values are English in an Indonesian storefront.
- The featured rail contains a “Lihat Semua” card while the page later provides another product-browsing CTA.
- Fallback hero claims—“50+ Produk Promo”, “70% Diskon Terbesar”, and “24/7 Pelayanan”—need verification or removal.
- Newsletter capture appears both on the home page and footer, creating redundant interruption.

## Questions to Consider

- If flash sale, vouchers, and generic discount statistics disappeared, what proves this store is a better choice than a marketplace?
- Which claim can the business substantiate most strongly: curation, delivery reliability, authenticity, service, or payment simplicity?
- Should the homepage prioritize quick product discovery or a curated brand point of view? The current page tries to do both without giving either enough room.
