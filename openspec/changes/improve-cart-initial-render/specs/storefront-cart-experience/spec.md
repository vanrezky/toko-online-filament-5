## MODIFIED Requirements

### Requirement: Supplemental recommendation content stays isolated from cart transactions

The cart SHALL display active, in-stock catalog recommendations related to distinct cart categories while excluding products already in the cart. Recommendation resolution MAY be deferred from the initial page response, but the cart item data, selected-item subtotal, stock information, and checkout action SHALL remain available in the initial response. Guest and empty-cart requests SHALL retain the existing empty recommendation behavior.

#### Scenario: Initial cart response renders the actionable cart first

- **WHEN** an authenticated customer opens a non-empty cart
- **THEN** the initial response contains the serialized cart, totals inputs, stock data, and checkout shell without resolved recommendations
- **AND** the response identifies the recommendations prop for deferred loading

#### Scenario: Deferred recommendations preserve existing service behavior

- **WHEN** the recommendations deferred group resolves
- **THEN** it uses the existing cart recommendation service and product resource shape
- **AND** active status, stock, category relevance, cart exclusion, pricing, media, and catalog statistics remain unchanged

#### Scenario: Guest or empty cart keeps empty recommendations

- **WHEN** a guest or customer without an active cart opens the cart page
- **THEN** the page retains an empty recommendations collection without running the authenticated-cart recommendation flow

### Requirement: Cart remains usable while recommendations resolve

The cart page SHALL render an accessible, localized loading fallback in the recommendation position while the deferred prop is unavailable. Cart selection, quantity controls, item removal, subtotal, and checkout interactions SHALL remain usable, and a successful resolution SHALL replace the fallback with the existing recommendation component.

#### Scenario: Recommendation loading fallback is visible

- **WHEN** the cart shell has rendered but recommendations have not resolved
- **THEN** the recommendation position contains a visible localized fallback with `role="status"` and a polite live region
- **AND** the rest of the cart remains rendered

#### Scenario: Recommendation fallback is replaced after resolution

- **WHEN** the deferred recommendations prop resolves
- **THEN** the loading fallback is removed
- **AND** the existing recommendation cards and links are rendered without changing cart totals or checkout selection
