## Purpose

Provide public catalog users with precise, relevant product results across the product information that shoppers can reasonably search.

## ADDED Requirements

### Requirement: Product keyword search

The public product catalog SHALL search a normalized keyword across product name, product code, description, category name, variant fields, and product attributes/options.

#### Scenario: Search matches product metadata

- **WHEN** a user submits a keyword matching a product code, category, variant, or approved product attribute/option
- **THEN** the matching active product is included in the catalog results

#### Scenario: Search matches multiple words

- **WHEN** a user submits a keyword containing multiple words
- **THEN** the catalog considers the words together and returns products matching the searchable product fields without duplicating a product because it has multiple matching attributes

### Requirement: Relevant result ordering

The catalog SHALL rank keyword results before applying a stable secondary sort. Exact product code and exact or prefix product-name matches SHALL rank above weaker full-text and related-field matches.

#### Scenario: Exact match ranks first

- **WHEN** one active product has an exact product-name or product-code match and other products only partially match
- **THEN** the exact match appears before the partial matches

#### Scenario: Existing sort remains available

- **WHEN** a user selects an existing catalog sort while a keyword is active
- **THEN** the selected sort is used as the secondary ordering for products with comparable relevance

### Requirement: Search preserves catalog behavior

Keyword search SHALL preserve active-product visibility, category and price filters, URL query parameters, pagination, and Load More behavior.

#### Scenario: Search combines with filters

- **WHEN** a user searches while selecting a category and price range
- **THEN** only products satisfying the keyword, category, and price constraints are returned

#### Scenario: Search pagination retains parameters

- **WHEN** a user loads another product page after searching
- **THEN** the next page retains the keyword and all active filters without duplicate products

#### Scenario: No products match

- **WHEN** no active product satisfies the keyword and filters
- **THEN** the existing empty state is returned and clearing the keyword allows the catalog to show products again

### Requirement: Bounded searchable query

The catalog SHALL use indexed exact, prefix, or full-text search mechanisms instead of relying on a leading-wildcard `LIKE` as the primary keyword mechanism. Relational category and attribute matching SHALL not multiply product rows.

#### Scenario: Search query uses searchable indexes

- **WHEN** a keyword search is executed
- **THEN** the query can use the configured code and full-text indexes and its plan can be inspected with `EXPLAIN`

#### Scenario: Matching multiple attributes does not duplicate results

- **WHEN** a product has multiple matching attributes or variant options
- **THEN** the product appears at most once in the paginated result set
