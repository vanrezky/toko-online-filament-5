## Purpose

Define the Bristol Shop cart presentation across desktop, tablet, and mobile while retaining existing cart interactions and keeping unsupported supplemental data local to the frontend.

## ADDED Requirements

### Requirement: Cart presentation follows the reference for each viewport

The `/cart` page SHALL follow the hierarchy, colors, typography, spacing, borders, imagery treatment, and content arrangement of the corresponding references in `.docs/design-references/carts/`: `dekstop.png`, `tablet.png`, and `mobile.png` as closely as possible. Real cart content SHALL take precedence over sample product identities in the mockups.

#### Scenario: Desktop cart
- **WHEN** a customer views a non-empty cart at a desktop viewport
- **THEN** the cart SHALL show item rows on the left and the shopping summary on the right, matching `dekstop.png`
- **AND** product recommendations SHALL follow their reference placement

#### Scenario: Tablet cart
- **WHEN** a customer views a non-empty cart at a tablet viewport
- **THEN** the cart SHALL retain the item-list and summary columns represented in `tablet.png`, with dimensions adapted to the available width
- **AND** text and controls SHALL remain readable and usable without horizontal page overflow

#### Scenario: Mobile cart
- **WHEN** a customer views a non-empty cart at a mobile viewport
- **THEN** the page SHALL follow `mobile.png` in this order: items, shopping summary, and recommendations
- **AND** a fixed bottom bar SHALL contain the primary checkout action
- **AND** the final content SHALL be accessible above the bar without horizontal page overflow

### Requirement: Item information preserves available cart data

Cart items SHALL display selection, photo, product name, available variant information, price, quantity, and reference actions in the arrangement appropriate to the viewport. Per-item subtotals and supporting details SHALL follow the reference where space permits. Existing cart data SHALL remain the source for cart identity, prices, quantities, selection, and calculated subtotal.

#### Scenario: Existing cart content is displayed
- **WHEN** the cart contains products with available images, variants, and prices
- **THEN** the page SHALL display that content using the reference's visual treatment
- **AND** it SHALL NOT replace real cart items or prices with the mockup's sample products or prices

#### Scenario: Supplemental data is absent
- **WHEN** a supporting display field has no existing backend data
- **THEN** the frontend SHALL use supplemental presentation data where needed to reproduce the reference
- **AND** that supplemental data SHALL NOT override existing cart values or become transaction input

### Requirement: Supplemental content stays isolated and checkout information is omitted

Recommendations SHALL use active, in-stock catalog products related to categories represented by distinct cart lines without changing transaction values. Products already present in the cart SHALL be excluded. The cart SHALL NOT display voucher entry, a placeholder voucher-discount row, shipping/payment method blocks, or the security-information block; those concerns belong to checkout.

#### Scenario: Checkout-only blocks are removed
- **WHEN** the cart is rendered at any viewport
- **THEN** voucher entry, placeholder voucher discount, shipping/payment method blocks, and the security-information block SHALL be absent
- **AND** the summary SHALL retain selected subtotal, total, and a concise notice that shipping is calculated at checkout

#### Scenario: Catalog recommendations are displayed
- **WHEN** a customer sees recommendations
- **THEN** at most five real catalog products SHALL be shown, ordered from category-matched candidate pools with a global fallback when necessary
- **AND** recommendation links SHALL navigate to their real product detail pages
- **AND** existing cart records and transaction values SHALL remain unchanged

#### Scenario: Recommendation candidates are cached
- **WHEN** recommendations are resolved repeatedly for carts sharing a category
- **THEN** the service SHALL reuse a short-lived managed cache containing candidate product IDs
- **AND** current active status, stock, pricing relations, media, and catalog statistics SHALL be hydrated after candidate selection
- **AND** the cache key SHALL NOT include the complete cart composition

### Requirement: Cart inherits the surrounding storefront design

The cart SHALL use container, typography, colors, buttons, and card treatments consistent with the existing product-detail and homepage storefront while preserving the reference's cart composition.

#### Scenario: Navigate from product detail or homepage to cart
- **WHEN** a customer opens the cart from those storefront pages
- **THEN** the cart SHALL use the shared storefront theme, typography, and button conventions
- **AND** header and footer behavior on other pages SHALL remain unchanged

### Requirement: Existing cart actions remain operational

The redesigned cart SHALL preserve select-all, individual selection, quantity updates, item deletion, selected-item subtotal calculation, and navigation to checkout using only the selected `cart_item_ids`. Exactly one primary checkout action SHALL be visible and accessible for a given viewport.

#### Scenario: Select all items
- **WHEN** the customer selects all cart items
- **THEN** all item selections and the displayed selected-item subtotal SHALL reflect every cart item
- **AND** checkout SHALL receive all selected cart item IDs

#### Scenario: Select a subset
- **WHEN** the customer selects only some cart items
- **THEN** the subtotal SHALL reflect only those items
- **AND** checkout SHALL receive only their cart item IDs

#### Scenario: Clear the selection
- **WHEN** no cart items are selected
- **THEN** the primary checkout action SHALL be disabled and SHALL NOT navigate

#### Scenario: Change quantity
- **WHEN** a customer changes an item's quantity within existing allowed behavior
- **THEN** the existing cart quantity update behavior SHALL remain operational and displayed subtotals SHALL reflect the resulting quantity
- **AND** the decrement control SHALL NOT reduce quantity below one

#### Scenario: Remove an item
- **WHEN** a customer deletes an existing cart item
- **THEN** the existing deletion behavior SHALL remain operational
- **AND** selection and subtotal SHALL reflect the remaining cart items

#### Scenario: Resize between desktop and mobile
- **WHEN** the viewport changes between the summary-column layout and the fixed-bottom-bar layout
- **THEN** the currently visible primary checkout action SHALL use the same selected items
- **AND** an inactive layout's checkout action SHALL NOT remain keyboard-accessible

### Requirement: Empty cart and localized content remain usable

The cart SHALL retain an empty state with navigation back to shopping. New interface text SHALL support the existing Indonesian and English frontend locales.

#### Scenario: Empty cart
- **WHEN** the cart initially contains no items or its last item is removed
- **THEN** the empty-cart state and shopping navigation SHALL be available
- **AND** supplemental presentation data SHALL NOT create fake cart items or enable checkout

#### Scenario: Change interface language
- **WHEN** the cart is displayed in Indonesian or English
- **THEN** new labels, actions, placeholders, and status copy SHALL use the active locale without exposing missing translation keys
