# Design: Product Detail Related-Product Service

## Decision

Keep the product detail controller responsible for primary-product loading and deferred-prop composition. Move related-product cache lookup, candidate loading, relation loading, reseller pricing, ordering, and catalog-stat attachment into `RelatedProductService`.

The deferred callback calls the service and maps its collection through the existing `ProductSimpleResource`. The service returns models rather than HTTP resources so it remains reusable by application code and keeps presentation mapping at the controller boundary.

## Rationale

The related-product query is a cohesive catalog operation and does not belong beside route handling or primary product loading. A single service preserves the existing behavior without introducing a new endpoint, repository, interface, or dependency.

## Invariants

- The cache namespace, key format, five-minute TTL, six-item limit, active filter, ordering, selected fields, media, flash-sale pricing, wholesale pricing, reseller pricing, and catalog statistics remain unchanged.
- The primary `product` prop remains eager; only the secondary `relatedProducts` prop is deferred.
- Empty and failed deferred rendering behavior remains owned by the existing Vue `<Deferred>` section.
