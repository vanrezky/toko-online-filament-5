## Context

`ProductController` currently builds the public catalog query and filters `products.name` with `LIKE '%term%'`. Products have searchable text and variant columns, while category and attribute data is stored in related tables. The current product schema has a name index but no code or full-text indexes.

## Goals / Non-Goals

**Goals:**

- Keep the implementation inside MySQL and Laravel.
- Rank exact code/name matches above prefix and full-text/related-field matches.
- Search related category and attribute data without joins that duplicate products.
- Keep existing catalog filters, sorts, pagination, and Inertia response shape.
- Make the searchable query indexable and verifiable with `EXPLAIN`.

**Non-Goals:**

- Typo tolerance, autocomplete, synonym expansion, AI search, or an external search engine.
- Changing the product listing UI or public route contract.

## Decisions

### Dedicated search service

Put keyword normalization, search predicates, and relevance scoring in `ProductSearchService`. The controller remains responsible for catalog filters and response composition, while the search logic can be tested independently.

Alternative: keep all SQL in the controller. Rejected because the ranking expression and related-table predicates would make the controller difficult to read and test.

### Hybrid indexed search

Use exact equality and prefix matching for product code/name ranking, plus MySQL boolean full-text matching for product name, description, variant, and sub-variant. Add full-text indexes to the searchable columns on products, categories, product attributes, and attribute options. Keep the existing name B-Tree index for name sorting and prefix use, and add a B-Tree index for product code.

Alternative: replace the query with multiple `%term%` predicates. Rejected because leading wildcards prevent effective left-prefix index use and do not provide relevance scoring.

### Related-field matching with EXISTS

Use correlated `EXISTS` predicates for category, direct product attributes/options, and variant attribute/options. This includes related matches without changing the product row cardinality, so pagination remains correct even when several attributes match.

Alternative: join all related tables and use `DISTINCT`. Rejected because it creates a larger intermediate result and makes ranking and pagination more expensive.

### Relevance and sorting

Add a calculated relevance score only when a keyword is present. Exact code, exact name, code prefix, name prefix, product full-text score, category match, and attribute/variant match contribute descending weights. Order by relevance first, then preserve the requested catalog sort as a stable secondary ordering.

### Search term handling

Trim and collapse whitespace before searching. Build a safe boolean full-text expression from alphanumeric search tokens, using prefix tokens for multi-word matching. Exact and prefix code/name predicates remain available for short terms that fall below MySQL full-text token length limits.

## Risks / Trade-offs

- [MySQL full-text token rules vary for short words and language morphology] -> Retain exact/prefix name and code matching; document typo tolerance and synonym support as later work.
- [Additional full-text indexes increase write and storage cost] -> Index only the fields required by the public catalog and verify the query plan before deployment.
- [Related attribute tables can grow independently] -> Use indexed foreign keys and `EXISTS`, and keep the product query paginated.
- [Relevance-first ordering changes the interpretation of existing sorts during keyword search] -> Make relevance the primary order and existing sort the deterministic secondary order, covered by tests.
- [Schema migration may fail on unsupported engines] -> The application is MySQL-based; validate migration and full-text queries against the configured MySQL test database.

## Migration Plan

1. Add product code and full-text indexes through a Laravel migration.
2. Deploy the search service and controller integration with the migration.
3. Run the product search feature tests and `EXPLAIN` verification against MySQL.
4. Roll back by reverting the code and dropping the added indexes through the migration rollback.

## Open Questions

None.
