## Purpose

This capability gives storefront customers a complete and predictable way to discover products using common catalog filters, variant attributes, sorting, and numbered pagination across desktop and mobile layouts.

## ADDED Requirements

### Requirement: Catalog filters are represented in the product URL

The system SHALL accept and preserve catalog filter state through query parameters for search, category, price range, minimum rating, sorting, promotions, and page size. The supported variant attributes SHALL use the `variant_` prefix: `variant_color`, `variant_size`, and `variant_gender`.

#### Scenario: A single variant attribute is submitted

- **WHEN** a customer opens the catalog with `variant_size=M`
- **THEN** only products with at least one matching size variant are returned and the parameter remains in pagination links

#### Scenario: Multiple variant values are submitted

- **WHEN** a customer selects more than one value for a variant attribute
- **THEN** products matching any selected value for that attribute are returned without duplicate product records

#### Scenario: Invalid filter values are submitted

- **WHEN** a query contains an unsupported sort, malformed numeric range, or unknown variant value
- **THEN** the catalog remains usable, ignores or safely normalizes the invalid value, and does not expose a server error

### Requirement: Variant filters use product variant attributes

The system SHALL resolve supported color, size, and gender filters through the product variant attribute and option relationships. A product SHALL match when at least one of its variants has the requested attribute name and option value. Brand and unsupported `variant_*` keys SHALL be ignored safely.

#### Scenario: Product has a matching variant

- **WHEN** a product has a variant whose attribute is `Ukuran` and option is `M`
- **THEN** the product is included for `variant_size=M`

#### Scenario: Product has no matching variant

- **WHEN** none of a product’s variants has the requested attribute option
- **THEN** the product is excluded from the filtered result

#### Scenario: Product has several matching variants

- **WHEN** multiple variants of the same product match the selected filters
- **THEN** the product appears only once in the catalog result

### Requirement: The catalog supports common storefront filters

The system SHALL support category, price minimum and maximum, minimum rating, discount, new products, and active flash sale filters in addition to variant filters. Stock availability SHALL NOT be exposed as a catalog filter until preorder behavior is supported.

#### Scenario: Filters are combined

- **WHEN** a customer selects a category, price range, minimum rating, promotion, and a variant value
- **THEN** the result contains only products satisfying all selected filter groups

#### Scenario: No product matches

- **WHEN** the selected filter combination has no matching products
- **THEN** the catalog returns an empty result and the storefront displays its empty state with a reset-filter action

### Requirement: Filter options use the approved common storefront set

The storefront SHALL provide a hardcoded common option set for the supported variant filters and SHALL NOT fetch dynamic variant-option metadata. Color options SHALL be `Hitam`, `Putih`, `Merah`, `Biru`, `Hijau`, `Kuning`, `Orange`, `Ungu`, `Cokelat`, and `Abu Abu`. Size options SHALL be `S`, `M`, `L`, `XL`, and `XXL`. Gender options SHALL be `Pria`, `Wanita`, and `Unisex`.

#### Scenario: Approved attribute options are available

- **WHEN** the catalog filter is opened
- **THEN** the common color, size, and gender options are available for selection

#### Scenario: Dynamic or unsupported options exist

- **WHEN** the database contains additional variant options or a query contains `variant_brand`
- **THEN** those options are not fetched or presented and the unsupported query key is ignored

### Requirement: Catalog navigation uses numbered pagination

The system SHALL display numbered pagination for catalog results and SHALL preserve all active query parameters when navigating between pages. The customer SHALL be able to select the number of products shown per page from the supported page-size options.

#### Scenario: Customer changes page

- **WHEN** a customer selects another page while filters and sorting are active
- **THEN** the selected page loads the matching filtered results and retains every active query parameter

#### Scenario: Customer changes page size

- **WHEN** a customer selects a supported products-per-page value
- **THEN** the catalog reloads with that page size and resets to the first page

#### Scenario: Customer is on the last page

- **WHEN** the customer views the last available page
- **THEN** the next-page control is disabled or absent and no load-more action is displayed

### Requirement: Desktop and mobile filter controls expose the same catalog state

The system SHALL provide a persistent desktop filter sidebar and a mobile filter bottom-sheet. Both controls SHALL expose equivalent filter values, active-filter chips, reset behavior, sorting, and result counts while adapting to their respective layouts.

#### Scenario: Desktop filter interaction

- **WHEN** a customer uses the desktop sidebar to select or clear a filter
- **THEN** the catalog query, active chips, result count, and visible product grid reflect the selected state

#### Scenario: Mobile filter interaction

- **WHEN** a customer opens the mobile filter sheet, changes filters, and applies them
- **THEN** the sheet closes, the product list reflects the selected state, and the active filter count/chips are updated

#### Scenario: Reset all filters

- **WHEN** a customer activates the reset-all action
- **THEN** all catalog filters return to their defaults, the URL is cleared of filter parameters, and the first unfiltered page is shown
