---
target: resources/js/frontend/pages/Products/Show.vue
total_score: 24
max_score: 40
na_heuristics: 
p0_count: 0
p1_count: 2
timestamp: 2026-07-22T11-48-39Z
slug: resources-js-frontend-pages-products-show-vue
---
## Design Health Score

| # | Heuristic | Score | Key issue |
|---|---|---:|---|
| 1 | Visibility of system status | 2 | Review loading has no failure or retry state. |
| 2 | Match between system and real world | 3 | Familiar commerce model, but mobile price precedes the product name. |
| 3 | User control and freedom | 3 | Quantity and browsing are clear; unselected variants recover through a disruptive alert. |
| 4 | Consistency and standards | 3 | Shared tokens and controls are consistent; breakpoint hierarchy changes. |
| 5 | Error prevention | 2 | CTAs disable correctly, but variant guidance is not contextual. |
| 6 | Recognition rather than recall | 3 | Options and review counts are visible; gallery state is mostly visual. |
| 7 | Flexibility and efficiency | 2 | No keyboard/touch image viewer or shortcut for repeat shoppers. |
| 8 | Aesthetic and minimalist design | 3 | Calm premium base, but duplicated price markup makes hierarchy fragile. |
| 9 | Error recovery | 1 | No review error state or retry path. |
| 10 | Help and documentation | 2 | FAQ helps later; purchase decisions need inline help. |
| **Total** |  | **24/40** | **Acceptable - resolve P1s before calling it polished.** |

## Design-specificity

The warm tokens, restrained cards, sticky gallery, and trust framing fit a premium independent store. The purchase flow is still category-standard and several interaction cues promise behavior that is unavailable, so the surface is cohesive but not fully authored through its decision and recovery states.

## What works

- Desktop scan order is clear: gallery, facts/options, quantity, then two purchase paths.
- Mobile CTAs are thumb-reachable and remain protected from content overlap.
- Lazy-loaded reviews reduce initial load; counts support recognition when filtering.
- The shared button supplies native semantics and visible keyboard focus, while review filters and FAQs expose their state.

## Priority issues

### P1 - Expanded descriptions can still be clipped

`max-h-[2000px]` remains after expansion, so a long seller description can end without an affordance. Remove the cap once expanded.

### P1 - Failed variant and review actions have no recovery path

Incomplete variants use `alert()`, while review loading has no catch, error state, or retry control. Use inline guidance near unselected options and a retryable review error state.

### P2 - Mobile hierarchy changes meaningfully

Mobile places price before the product name, while desktop puts it after title/rating. Keep the scan order consistent across breakpoints.

### P2 - Gallery and trust rows advertise actions that do not exist

The zoom cursor/hover icon has no touch or keyboard equivalent, and trust-row chevrons imply navigation despite being inert. Add a real viewer/disclosure or remove the cues. Also correct the secure-payment note, which currently uses the easy-returns translation key.

### P2 - Social-proof message can overclaim

The "good quality" statement appears regardless of rating distribution. Derive it from the rating data or omit it for weak/insufficient reviews.

### P2 - Form and image semantics are incomplete

Quantity +/- buttons lack accessible names, option buttons do not expose selected state, review images have no alt text, and the quantity label is not associated with its input.

## Persona red flags

- A first-time buyer gets a modal alert instead of help locating the unselected variant.
- A mobile shopper must re-learn the product scan order and cannot act on the zoom cue.
- A keyboard or screen-reader user gets weak state feedback for quantity, variants, review loading, and review images.

## Mechanical evidence

The deterministic detector returned zero findings. Browser automation was unavailable, so no live rendered, responsive, or overlay evidence was captured. Source evidence confirms the mobile CTA padding, responsive breakpoints, button focus treatment, and ARIA state on review/FAQ controls; it also confirms the semantic gaps above.
