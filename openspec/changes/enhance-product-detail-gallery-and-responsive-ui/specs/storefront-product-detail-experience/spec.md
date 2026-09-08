## Purpose

This capability defines a clear, high-quality product detail experience that lets customers inspect the complete product gallery, understand purchase information, and act confidently across desktop, tablet, and mobile viewports.

## ADDED Requirements

### Requirement: Product detail exposes full-quality gallery media

The product detail experience SHALL provide the original URL for every product media item used by the main gallery and SHALL provide a separate lower-resolution URL for thumbnail navigation. The absence of a thumbnail conversion SHALL NOT prevent the original media from being displayed.

#### Scenario: Product has multiple media items

- **WHEN** a customer opens a product with multiple media items
- **THEN** the main gallery uses the original asset for the initially selected item and for every item selected afterward, while thumbnail controls may use the lower-resolution asset

#### Scenario: Customer navigates the gallery

- **WHEN** a customer selects a thumbnail or uses the previous or next gallery control
- **THEN** the selected main image changes to the corresponding original asset without replacing it with a thumbnail conversion

#### Scenario: Product media lacks a conversion

- **WHEN** an individual media item has no generated thumbnail conversion
- **THEN** the gallery remains usable by using the original asset for both the thumbnail control and the main image

#### Scenario: Product has no media

- **WHEN** a product has no media items
- **THEN** the page shows an intentional no-image fallback and gallery controls do not reference missing URLs

### Requirement: Product detail preserves real product information and actions

The page SHALL present available product name, category, price, discount, stock, SKU or product code, weight, media, variants, quantity, and review data from the existing product data. Static content MAY be used only for non-transactional presentation metadata such as trust notes, benefits, and fallback specifications. Existing add-to-cart, buy-now, wishlist, quantity, variant, review, and image zoom actions SHALL remain available and functional.

#### Scenario: Product has available purchase data

- **WHEN** a customer views the product summary
- **THEN** the displayed price, discount, stock, variant choices, quantity limits, and purchase actions correspond to the existing product data and rules

#### Scenario: Product lacks optional presentation metadata

- **WHEN** a product has no stored benefits, trust notes, or detailed specifications
- **THEN** the page may show approved static presentation content without changing transactional data or claiming unavailable review results

#### Scenario: Customer completes a product action

- **WHEN** a customer changes quantity or variant, saves the product, adds it to the cart, starts buy-now, loads reviews, or opens image zoom
- **THEN** the existing action behavior remains intact and reflects the selected product state

### Requirement: Desktop composition follows the approved product detail reference

At desktop widths, the page SHALL present the visual hierarchy represented by `detail-products/dekstop.png`: product gallery, product summary and purchase controls, supporting trust cards, product benefits, detail navigation, rating summary, similar products, and footer. The composition SHALL keep primary product information and purchase actions visually prominent.

#### Scenario: Customer views the desktop detail page

- **WHEN** the viewport is a desktop width supported by the storefront
- **THEN** the customer can identify the gallery, product identity, price, options, purchase actions, trust information, detail content, rating, similar products, and footer in the reference hierarchy without horizontal overflow

### Requirement: Tablet composition follows the approved product detail reference

At tablet widths, the page SHALL present the visual hierarchy represented by `detail-products/tablet.png`, including the two-area product section, benefit row, detail and rating panels, payment methods, and similar products. Content SHALL remain readable and controls SHALL remain operable without horizontal overflow.

#### Scenario: Customer views the tablet detail page

- **WHEN** the viewport is a tablet width supported by the storefront
- **THEN** the gallery, product information, trust content, detail content, rating, payment methods, and similar products are arranged in the tablet reference composition and remain accessible

### Requirement: Mobile composition follows the approved product detail reference

At mobile widths, the page SHALL present the visual hierarchy represented by `detail-products/mobile.png`, including a compact header, gallery controls, product information, option selectors, benefit panel, collapsible detail sections, and persistent purchase actions. The page SHALL remain usable without horizontal scrolling.

#### Scenario: Customer views the mobile detail page

- **WHEN** the viewport is a mobile width supported by the storefront
- **THEN** the customer can inspect the gallery, identify price and stock, select options, expand product details, and reach add-to-cart or buy-now actions in the mobile reference composition

#### Scenario: Customer expands a mobile detail section

- **WHEN** the customer activates a mobile detail accordion item
- **THEN** only the selected section changes visibility, its expanded state is communicated to assistive technology, and the page keeps the purchase actions reachable

### Requirement: Related products remain bounded and cache-safe
The product detail page SHALL select at most six active products from the current product category, exclude the current product, and order candidates by newest product first. Candidate selection MAY be cached, but current active status, media, stock, flash-sale pricing, wholesale pricing, and reseller pricing MUST be resolved from current data when the page is rendered.

#### Scenario: Customer opens a product detail page
- **WHEN** the current product has products in the same category
- **THEN** the related-products section receives no more than six active peers and never includes the current product

#### Scenario: Related-product candidate cache is reused
- **WHEN** another detail request uses the same category and excluded product within the cache lifetime
- **THEN** the bounded candidate selection can be served from cache while dynamic product presentation data remains current

#### Scenario: Catalog product changes
- **WHEN** a product is created, updated, or deleted
- **THEN** the related-product candidate cache is invalidated without flushing unrelated managed cache groups

### Requirement: Product detail UI is localized and accessible

All new user-facing labels, control names, empty states, and accessible names SHALL be available in Indonesian and English. Interactive gallery, accordion, option, wishlist, and purchase controls SHALL expose meaningful accessible names and keyboard focus states.

#### Scenario: Customer changes the locale

- **WHEN** the storefront locale changes between Indonesian and English
- **THEN** all newly introduced interface labels use the selected locale while product-provided names and descriptions remain unchanged

#### Scenario: Customer uses keyboard navigation

- **WHEN** a customer navigates the product detail page with a keyboard
- **THEN** gallery controls, option selectors, accordions, wishlist, zoom, and purchase actions can be focused and operated with visible focus feedback
