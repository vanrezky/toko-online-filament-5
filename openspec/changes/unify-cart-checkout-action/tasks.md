## 1. Cart checkout action

- [x] 1.1 Remove the separate checkout-all link and retain one primary Cart checkout action.
- [x] 1.2 Preserve selected `cart_item_ids` navigation for partial and full Cart selections, with the existing empty-selection disabled state.

## 2. Regression coverage

- [x] 2.1 Add Playwright coverage for customer login, adding two distinct catalog products, selecting Cart items, and activating the single checkout action.
- [x] 2.2 Assert the checkout navigation retains the selected-item contract and the Cart renders only one checkout action.
- [x] 2.3 Exclude Playwright E2E files from Vitest discovery.

## 3. Validation

- [x] 3.1 Run the focused Playwright test against the existing Sail application.
- [x] 3.2 Run the frontend production build and OpenSpec validation.
