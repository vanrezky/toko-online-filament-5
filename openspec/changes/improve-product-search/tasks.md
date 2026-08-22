## 1. Search indexes

- [x] 1.1 Add a migration for the product code index and full-text indexes covering product, category, attribute, and attribute-option search fields.
- [x] 1.2 Verify the migration rollback and full-text index definitions against the MySQL test database.

## 2. Search implementation

- [x] 2.1 Create `ProductSearchService` to normalize terms, build safe boolean full-text tokens, and apply multi-field predicates.
- [x] 2.2 Add exact/prefix relevance ranking for product code/name and full-text/related-field relevance scoring.
- [x] 2.3 Integrate the service into `ProductController` while preserving active, category, price, sorting, pagination, and Inertia query parameters.
- [x] 2.4 Confirm category and attribute matching uses existence checks and never duplicates product rows.

## 3. Tests

- [x] 3.1 Add feature coverage for exact name/code ranking and multi-word product-field search.
- [x] 3.2 Add coverage for category, variant, direct attribute, and variant-option matching without duplicates.
- [x] 3.3 Add coverage for category/price filters, pagination query retention, active products, sorting, and empty results.
- [x] 3.4 Add a query-plan or SQL assertion proving the primary search path does not use a leading-wildcard name predicate.

## 4. Validation

- [x] 4.1 Run Pint and relevant PHP static checks.
- [x] 4.2 Run the product search tests and related existing feature tests.
- [x] 4.3 Run the frontend build to verify the existing catalog UI remains compatible.
- [x] 4.4 Run OpenSpec validation and verify Issue #75 acceptance criteria.
