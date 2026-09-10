# Storefront Product Detail Experience

## MODIFIED Requirements

### Requirement: Primary product detail renders independently

The product detail response SHALL keep the primary `product` prop in the initial Inertia response. Secondary related-product catalog work SHALL be exposed as a deferred prop named `relatedProducts` in its own group.

#### Scenario: Initial response omits related products

- **WHEN** a customer opens an active product detail page
- **THEN** the initial Inertia page contains the primary `product` prop
- **AND** the initial page does not contain resolved `relatedProducts`
- **AND** the page advertises the `relatedProducts` deferred group

#### Scenario: Deferred related products preserve semantics

- **WHEN** the `relatedProducts` deferred group is resolved
- **THEN** it preserves the existing cache key and five-minute TTL
- **AND** it returns the same active same-category candidates excluding the current product, limit, ordering, selected fields, media, pricing relations, reseller pricing, and catalog statistics

### Requirement: Related products fail independently

The product detail page SHALL render an accessible related-products loading fallback while the deferred group is unresolved and SHALL keep primary product content usable when the deferred request does not resolve.

#### Scenario: Loading fallback is visible

- **WHEN** the related-products deferred prop is unavailable
- **THEN** the product title and primary purchase content remain rendered
- **AND** the related-products area exposes a polite status fallback

#### Scenario: Resolved related products render normally

- **WHEN** the deferred prop resolves with related products
- **THEN** existing related-product cards and links render
- **AND** when it resolves empty, the existing empty-state behavior is unchanged
