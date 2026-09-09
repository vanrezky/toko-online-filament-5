# Storefront Cart Experience Specification

## Purpose

Define the Bristol Shop cart presentation across desktop, tablet, and mobile while retaining existing cart interactions and keeping checkout-only information out of the cart.

## Requirements

### Requirement: Cart presentation follows the reference viewport

The `/cart` page SHALL follow the hierarchy, colors, typography, spacing, borders, imagery treatment, and content arrangement represented by the desktop, tablet, and mobile cart references. Real cart content SHALL take precedence over sample product identities in the references.

#### Scenario: Desktop cart

- **WHEN** a customer views a non-empty cart at a desktop viewport
- **THEN** item rows SHALL appear on the left and the shopping summary SHALL appear on the right
- **AND** product recommendations SHALL follow the reference placement

#### Scenario: Tablet cart

- **WHEN** a customer views a non-empty cart at a tablet viewport
- **THEN** the item list and summary SHALL retain the represented columns with dimensions adapted to the available width
- **AND** text controls SHALL remain readable and usable without horizontal page overflow

#### Scenario: Mobile cart

- **WHEN** a customer views a non-empty cart at a mobile viewport
- **THEN** the page SHALL present items, shopping summary, and recommendations in that order
- **AND** a fixed bottom bar SHALL contain the primary checkout action
- **AND** final content SHALL remain accessible above the bar without horizontal page overflow

### Requirement: Item information preserves available cart data

Cart items SHALL display selection, photo, product name, available variant information, price, quantity, and reference actions in an arrangement appropriate to the viewport. Per-item subtotals and supporting details SHALL follow the reference where space permits. Existing cart data SHALL remain the source for cart identity, prices, quantities, selection, and calculated subtotal.

#### Scenario: Existing cart content displayed

- **WHEN** the cart contains products
- **THEN** each product row SHALL expose its current selection state, image, name, price, quantity controls, and item actions
- **AND** a selected variant SHALL be shown as read-only text
- **AND** no variant selector SHALL be rendered in the cart

#### Scenario: Supplemental data absent

- **WHEN** a supporting display field has no existing backend data
- **THEN** the frontend MAY use presentation-only supplemental data where needed to reproduce the reference
- **AND** supplemental data SHALL NOT override existing cart values or become transaction input

### Requirement: Supplemental content stays isolated and checkout information is omitted

The cart SHALL display only cart and recommendation content without changing transaction values. Recommendations SHALL use active, in-stock catalog products related to categories represented by distinct cart lines. Products already present in the cart SHALL be excluded. The cart SHALL NOT display voucher entry, placeholder voucher discount rows, shipping or payment method blocks, or security-information blocks; those concerns belong to checkout.

#### Scenario: Checkout-only blocks are removed

- **WHEN** the cart is rendered at any viewport
- **THEN** voucher, shipping or payment method, and security-information blocks SHALL be absent
- **AND** the summary SHALL retain the selected subtotal, total, and concise notice that shipping is calculated at checkout

#### Scenario: Catalog recommendations are displayed

- **WHEN** a customer sees recommendations
- **THEN** at most five real catalog products SHALL be shown from category-matched candidate pools with global fallback when necessary
- **AND** recommendation links SHALL navigate to real product detail pages
- **AND** existing cart records and transaction values SHALL remain unchanged

#### Scenario: Recommendation candidates are cached

- **WHEN** recommendations are resolved repeatedly for carts sharing categories
- **THEN** the service SHALL reuse a short-lived managed cache containing candidate product IDs
- **AND** current active status, stock, pricing relations, media, and catalog statistics SHALL be hydrated before candidate serialization
- **AND** the cache key SHALL NOT include the complete cart composition

### Requirement: Cart inherits the surrounding storefront design

The cart SHALL use the shared storefront container, typography, colors, buttons, and card treatments consistent with the product-detail and homepage experiences while preserving the cart reference composition. Cart layout styling SHALL use Tailwind CSS utilities and shared design tokens; no page-level cart stylesheet is required.

#### Scenario: Shared storefront conventions

- **WHEN** the cart is rendered
- **THEN** its content SHALL align with the shared storefront container convention and button styles
- **AND** header and footer behavior on other pages SHALL remain unchanged

### Requirement: Existing cart actions remain operational

The redesigned cart SHALL preserve select-all, individual selection, quantity updates, item deletion, selected-item subtotal calculation, and checkout navigation using only selected `cart_item_ids`. Exactly one primary checkout action SHALL be visible and accessible for the current viewport.

#### Scenario: Select all items

- **WHEN** a customer selects all cart items
- **THEN** all item selections SHALL be displayed as selected
- **AND** the selected-item subtotal SHALL reflect every cart item
- **AND** checkout SHALL receive all selected cart item IDs

#### Scenario: Select a subset

- **WHEN** a customer selects only some cart items
- **THEN** the subtotal SHALL reflect only those items
- **AND** checkout SHALL receive only those cart item IDs

#### Scenario: Clear selection

- **WHEN** no cart items are selected
- **THEN** the primary checkout action SHALL be disabled
- **AND** checkout SHALL not be navigated to

#### Scenario: Change quantity

- **WHEN** a customer changes an item's quantity within existing allowed behavior
- **THEN** the existing cart quantity update behavior SHALL remain operational
- **AND** displayed subtotals SHALL reflect the resulting quantity
- **AND** the decrement control SHALL not reduce quantity below one

#### Scenario: Remove an item

- **WHEN** a customer deletes an existing cart item
- **THEN** existing deletion behavior SHALL remain operational
- **AND** the selection subtotal SHALL reflect the remaining cart items

#### Scenario: Resize between desktop and mobile

- **WHEN** the viewport changes between summary-column and fixed-bottom-bar layouts
- **THEN** the currently visible primary checkout action SHALL use the same selected items
- **AND** the inactive layout's checkout action SHALL not remain keyboard-accessible

### Requirement: Empty cart content is localized and usable

The cart SHALL retain an empty state with navigation back to shopping. New interface text SHALL support the existing Indonesian and English frontend locales.

#### Scenario: Empty cart

- **WHEN** the cart initially contains no items or the last item is removed
- **THEN** an empty-cart state SHALL be shown
- **AND** fake cart items SHALL not be shown
- **AND** checkout SHALL remain unavailable

#### Scenario: Change interface language

- **WHEN** the cart is displayed in Indonesian or English
- **THEN** new labels, actions, placeholders, and status copy SHALL use the active locale
- **AND** missing translation keys SHALL not be exposed
