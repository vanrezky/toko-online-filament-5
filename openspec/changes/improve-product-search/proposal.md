## Why

The public product catalog currently searches only `products.name` with a leading-wildcard `LIKE`, which returns broadly matching products without relevance ranking and misses useful product metadata. The catalog needs more precise results while remaining within the existing MySQL-based architecture.

## What Changes

- Add relevance-ranked product keyword search for exact, prefix, and full-text matches.
- Search product name, code, description, category name, product variants, and product attributes/options.
- Preserve the existing active-product, category, price, sorting, pagination, and Load More behavior.
- Add the indexes needed for code and full-text search.
- Ensure relational matching uses existence checks so products are not duplicated.
- Verify the search query plan and add regression coverage for ranking, fields, filters, pagination, and empty results.

## Capabilities

### New Capabilities

- `product-search`: Relevance-ranked, multi-field keyword search for the public product catalog.

### Modified Capabilities

- None.

## Impact

- Updates the frontend product controller query and adds a focused product-search service.
- Adds database indexes for product code and searchable text on products, categories, and product attributes/options.
- Adds feature tests for the public product listing search.
- No external search dependency, typo correction, autocomplete, synonym engine, or AI search is introduced.
