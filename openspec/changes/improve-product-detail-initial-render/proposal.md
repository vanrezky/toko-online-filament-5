# Proposal: Defer Product Detail Related Products

GitHub Issue: #120

## Why

The product detail action resolves the secondary related-product catalog before the initial Inertia response. That work does not block the primary product detail experience and can delay the first render.

## Changes

- Return `relatedProducts` through the native Inertia deferred-prop mechanism.
- Preserve the existing related-product cache key, selection, ordering, pricing relations, catalog statistics, and resource shape.
- Show an accessible loading fallback while related products resolve, while keeping the primary product content usable.
- Add backend and frontend regression coverage plus before/after response measurements.

## Scope

### In scope

- Product detail controller deferred `relatedProducts` group.
- Product detail related-products loading and resolved rendering.
- Tests and verification evidence for Issue #120.

### Out of scope

- Product schema or relation changes.
- SEO metadata changes.
- Related-product selection semantics.
- Product detail redesign.

## Definition of Done

- Issue #120 acceptance criteria are implemented and traceable.
- Focused tests, formatter, build, and strict OpenSpec validation pass where the environment permits.
- Browser and before/after performance evidence are recorded.
- A PR targets `dev` and links Issue #120.

## Verification Evidence

Three sequential requests to the same local product detail URL produced the following initial-response measurements:

| Branch | Initial bytes | TTFB samples | Median TTFB |
| --- | ---: | --- | ---: |
| `origin/dev` before | 35,645 | 1.214s / 0.440s / 0.592s | 0.592s |
| Issue #120 after | 32,272 | 0.369s / 0.099s / 0.095s | 0.099s |

The initial payload is 9.5% smaller; the related-products deferred request is intentionally excluded from that initial-response measurement. Browser verification confirmed the primary detail renders with `Memuat...`, then `Produk Serupa` cards and links appear after resolution.
