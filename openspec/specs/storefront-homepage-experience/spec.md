# storefront-homepage-experience Specification

## Purpose

This capability defines a clear, responsive storefront homepage experience that helps customers understand the store offer, discover categories and products, and continue into existing commerce flows without changing their contracts.

## Requirements

### Requirement: Homepage presents the approved storefront hierarchy

The homepage SHALL present a cohesive storefront composition with a dynamic header, warm-peach hero, popular category tiles, promotional and category content, product discovery, newsletter, trust and service content, and a multi-column footer. The composition SHALL use the project's Space Grotesk typography and token-based warm orange, charcoal, cream, and border colors.

#### Scenario: Desktop visitor scans the homepage

- **WHEN** a visitor opens the homepage on a desktop viewport
- **THEN** the visitor can identify the primary navigation, search, account and cart actions, main offer, category discovery, product discovery, and trust content in the visual order represented by the approved references

#### Scenario: Mobile visitor scans the homepage

- **WHEN** a visitor opens the homepage on a mobile viewport
- **THEN** the same content remains available in a readable single-column flow without horizontal page overflow or controls that are too small to operate

### Requirement: Homepage preserves dynamic content and commerce actions

The homepage SHALL continue to use dynamic site settings, CMS template content, categories, products, sliders, flash sales, and existing localized labels. Product cards and discovery controls SHALL preserve category filtering, product navigation, pricing, flash-sale pricing, wishlist, cart, voucher, and newsletter behaviors.

#### Scenario: Customer uses a category tile

- **WHEN** a customer selects a category tile or the all-categories option
- **THEN** the homepage navigates using the existing category filter route and renders the corresponding filtered product state

#### Scenario: Customer acts on a product

- **WHEN** a customer opens, favorites, or adds a product to cart from any homepage product presentation
- **THEN** the existing product route, wishlist behavior, price display, and cart action are used without changing their underlying contracts

#### Scenario: CMS content is absent or incomplete

- **WHEN** a homepage template section has no configured content
- **THEN** the homepage displays the existing safe fallback content and keeps its surrounding layout usable

### Requirement: Homepage meets storefront accessibility and motion constraints

The homepage SHALL provide visible keyboard focus states, meaningful accessible names for icon-only controls, readable contrast for text and controls, responsive images with useful alternative text, and a reduced-motion presentation that keeps content visible and usable.

#### Scenario: Keyboard user navigates the homepage

- **WHEN** a customer moves through homepage links, search, account and cart actions, filters, product actions, and newsletter controls with a keyboard
- **THEN** focus remains visible, the reading order is logical, and every interactive control has an understandable accessible name

#### Scenario: Customer prefers reduced motion

- **WHEN** the browser reports `prefers-reduced-motion: reduce`
- **THEN** decorative transitions and carousel motion are reduced or disabled while the content and controls remain available

### Requirement: Homepage removes the superseded collection story

The homepage SHALL omit the legacy "Untuk setiap keseharian" collection story section while retaining shared category data for the category menu and existing filter behavior.

#### Scenario: Visitor scans the section order

- **WHEN** a visitor opens the homepage
- **THEN** the superseded collection story section is absent while the category menu remains available

### Requirement: Homepage uses the requested responsive hero composition

On desktop, the hero SHALL allocate approximately 70% of its width to the image-backed content panel and 30% to the service-information panel. On smaller viewports, the panels SHALL stack without horizontal overflow.

#### Scenario: Visitor views the responsive hero

- **WHEN** the homepage is rendered at desktop or mobile width
- **THEN** the hero uses the 70/30 image and service composition on desktop and stacks the panels on smaller viewports

### Requirement: Promotional carousel shows three desktop cards

The promotional carousel SHALL show three promotional cards simultaneously at desktop widths, two at intermediate widths, and one at mobile widths while preserving existing controls and slide links.

#### Scenario: Visitor browses promotional cards

- **WHEN** a visitor views the promotional carousel at a supported viewport width
- **THEN** the carousel shows the correct number of cards for that breakpoint and keeps its navigation controls and links usable

### Requirement: Product cards follow the approved reference hierarchy

Homepage product cards SHALL use a compact landscape product-image area, visible variant-colored status badges, product title followed by rating, and price and cart actions aligned to the card footer while preserving existing product, wishlist, pricing, and navigation behavior.

#### Scenario: Customer scans a product card

- **WHEN** a customer views a product card on the homepage
- **THEN** the product image, status badge, title, rating, price, and cart affordance are visually distinct and follow the approved reference hierarchy

### Requirement: Newsletter and footer follow the approved Bristol reference

The homepage SHALL present the newsletter as an update-focused treatment. The trust strip SHALL present four icon-led service assurances with desktop dividers. The shared footer SHALL present brand information, configured social links, shopping, help, and about navigation, configured contact information, copyright, and payment methods in a responsive multi-column composition.

#### Scenario: Customer reaches the homepage closing sections

- **WHEN** a customer scrolls past product and voucher content
- **THEN** the newsletter, four-item trust strip, and multi-column footer are visible in the approved visual order
- **AND** newsletter submission continues to use the existing subscription endpoint

#### Scenario: Store settings provide footer contact data

- **WHEN** GeneralSettings contains email, phone, WhatsApp, address, or social URLs
- **THEN** the footer renders those values and links without inventing values for unconfigured channels

### Requirement: Homepage closing sections avoid responsive overflow

The newsletter, trust strip, and footer SHALL remain within the viewport at supported desktop, intermediate, and mobile widths. Content SHALL stack or wrap instead of introducing horizontal page scrolling.

#### Scenario: Customer views closing sections on mobile

- **WHEN** the homepage is rendered at a mobile viewport
- **THEN** newsletter content stacks, trust items remain readable, footer columns flow vertically, and document scroll width equals the viewport width
