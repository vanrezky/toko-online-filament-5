## MODIFIED Requirements

### Requirement: Homepage presents approved storefront hierarchy

Homepage SHALL present a cohesive storefront composition with dynamic header, warm-peach hero, popular category tiles, promotional category content, product discovery, newsletter, trust service content, and multi-column footer. The composition SHALL use the project's Space Grotesk typography token-based warm orange, charcoal, cream, and border colors. For supported sections, the homepage SHALL use the active admin template's configured order and activation state, and SHALL apply its resolved color scheme.

#### Scenario: Desktop visitor scans homepage

- **WHEN** visitor opens homepage on desktop viewport
- **THEN** visitor can identify primary navigation, search, account and cart actions, main offer, category discovery, product discovery, trust content in visual order represented by approved references

#### Scenario: Mobile visitor scans homepage

- **WHEN** visitor opens homepage on mobile viewport
- **THEN** same content remains available in readable single-column flow without horizontal page overflow or controls too small to operate

#### Scenario: Active template changes supported section order

- **WHEN** administrator changes the order of supported active sections in the active template
- **THEN** the homepage presents those sections in the configured order while preserving the approved section presentation

#### Scenario: Template section is inactive

- **WHEN** administrator deactivates a supported homepage section
- **THEN** the homepage omits that section without leaving an empty layout gap

### Requirement: Homepage preserves dynamic content commerce actions

Homepage SHALL continue to use dynamic site settings, CMS template content, categories, products, sliders, flash sales, and existing localized labels. Product cards and discovery controls SHALL preserve category filtering, product navigation, pricing, flash-sale pricing, wishlist, cart, voucher, and newsletter behaviors. Supported template controls and content values SHALL be applied without changing the underlying commerce contracts.

#### Scenario: Customer uses category tile

- **WHEN** customer selects category tile or all-categories option
- **THEN** homepage navigates using existing category filter route and renders corresponding filtered product state

#### Scenario: Customer acts on product

- **WHEN** customer opens, favorites, or adds product to cart from any homepage product presentation
- **THEN** existing product route, wishlist behavior, price display, and cart action are used without changing their underlying contracts

#### Scenario: CMS content is absent or incomplete

- **WHEN** CMS or template content is absent or incomplete
- **THEN** layout remains usable and uses safe fallback content or visibility behavior

#### Scenario: Template content controls a supported section

- **WHEN** a supported section has configured limit, category, discount, timer, column, load-more, or background-style values
- **THEN** the homepage applies those values to the corresponding section output

## ADDED Requirements

### Requirement: Homepage preview reflects selected template settings

The homepage SHALL support a template preview mode that renders a selected template's supported section order, activation, content, and colors without replacing the published storefront state.

#### Scenario: Preview renders selected template

- **WHEN** an authorized administrator opens a selected template in preview mode
- **THEN** the homepage renders the selected template configuration using the same section and color contract as the storefront

#### Scenario: Published homepage is isolated from preview

- **WHEN** an administrator previews an un published or changed template
- **THEN** normal customer requests continue to use the published active template
