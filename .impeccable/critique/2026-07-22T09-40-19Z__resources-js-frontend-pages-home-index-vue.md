---
target: resources/js/frontend/pages/Home/Index.vue
total_score: 21
p0_count: 0
p1_count: 3
timestamp: 2026-07-22T09-40-19Z
slug: resources-js-frontend-pages-home-index-vue
---
Method: dual-agent (A: /root/design_review · B: /root/detector_evidence)

## Design Health Score

| # | Heuristic | Score | Key issue |
|---|---|---:|---|
| 1 | Visibility of system status | 2 | Carousel has no current-slide state. |
| 2 | Match between system and real world | 3 | Category and product browsing are familiar. |
| 3 | User control and freedom | 2 | Autoplay has no pause and discovery cannot be shortened. |
| 4 | Consistency and standards | 3 | Square editorial treatment conflicts with rounded category chips. |
| 5 | Error prevention | 2 | Image-copy contrast depends on optional or fixed overlays. |
| 6 | Recognition rather than recall | 3 | Choices are visible, but Story and Categories duplicate the decision. |
| 7 | Flexibility and efficiency | 2 | Repeat shoppers reach products only after several campaign surfaces. |
| 8 | Aesthetic and minimalist design | 2 | Four high-attention modules precede the product grid. |
| 9 | Error recovery | 1 | No resilient image or discovery fallback. |
| 10 | Help and documentation | 1 | No orientation between Story, Categories, and campaigns. |
| **Total** | | **21/40** | **Hierarchy refinement needed** |

## Anti-Patterns Verdict

LLM assessment: The page is borderline template-composed rather than fully art-directed. The hero and Store Story are strong individually, but their sequence is followed by a utility-like category chip strip and a second full-bleed hero carousel before products. Repeated uppercase eyebrows, universal dark image overlays, and mixed rounded versus square component language reduce the premium read.

Deterministic scan: 0 findings in `resources/js/frontend/pages/Home/Index.vue`. No ignore file exists. Browser inspection and live overlay injection were unavailable in this harness.

## Overall Impression

The current `hero -> story -> category -> carousel -> catalogue` flow is not proportionate for premium commerce. It takes shoppers through two visual campaigns and two category-selection mechanisms before giving them products. The biggest opportunity is to let product discovery arrive sooner and make every pre-catalogue section have one unambiguous job.

## What's Working

- Hero has a focused primary action and can establish a confident premium promise.
- Store Story has real visual hierarchy through its spanning lead tile, avoiding a generic equal-card grid.
- Reviewed links use textual labels, focus styles, and reduced-motion fallbacks.

## Priority Issues

### [P1] Duplicate hero moments before products

The full-width carousel repeats image, eyebrow, large heading, and CTA immediately after the main hero. It delays product discovery and makes promotion feel mandatory. Move the carousel below the first product/collection rail, or reduce it to a compact single-message campaign strip.

Suggested command: `/impeccable layout`

### [P1] Category navigation breaks the premium register

Rounded coloured chips, emoji/folder fallbacks, and 60px-truncated labels look like marketplace utility UI between two editorial image sections. Make it a clearly labelled utility navigation or merge the purpose into Store Story; remove emoji fallbacks and avoid truncating a primary choice.

Suggested command: `/impeccable distill`

### [P1] Image-copy contrast is content-dependent

The hero only renders an overlay when the CMS provides one, while the carousel has a fixed black overlay for arbitrary photos. Make a contrast-safe gradient/scrim mandatory behind copy.

Suggested command: `/impeccable harden`

### [P2] Carousel has weak motion control

It loops every six seconds with arrows only: no pause, current slide, focus pause, or reduced-motion JS behavior. Default to non-autoplay, or add those controls.

Suggested command: `/impeccable audit`

### [P2] Store Story lacks a distinct role

It duplicates category choice before the category strip. Name it as a genuinely curated editorial collection, or replace it with product-led discovery.

Suggested command: `/impeccable clarify`

## Persona Red Flags

- **Jordan, first-time mobile shopper:** sees hero, three story choices, an overflowing category list, then a tall 4:3 carousel before any product; the intended shopping path is unclear.
- **Rina, repeat buyer:** must pass several brand and campaign blocks before reaching products or fast category access.
- **Dimas, motion-sensitive or low-vision shopper:** white copy can land on a bright CMS image and the autoplay carousel has no pause or state.

## Minor Observations

`selectCategory` is unused. The category menu hard-codes “Semua” while adjacent sections use i18n. Hero image alt text is the heading, not a descriptive image alternative; external images lack loading-error recovery.

## Questions to Consider

- Is the category strip task navigation, or should Store Story own curated category discovery?
- What commercial purpose does the carousel serve that a second hero cannot?
- On mobile, should a shopper reach products within the first viewport after interacting with the hero?
