# Product Image URL Repeater

## ADDED Requirements

### Requirement: Compact product image URL repeater

The product create and edit forms MUST configure the `image_urls` repeater as
a one-column table repeater with Filament's compact presentation option.

#### Scenario: Product image URL fields use a compact layout

- **WHEN** an administrator opens the Images tab of the product create or edit
  form
- **THEN** the `image_urls` repeater uses a compact table layout
- **AND** the URL field, add action, remove action, and item ordering controls
  remain available

### Requirement: Existing image URL behavior remains unchanged

The compact presentation MUST NOT change the existing image URL repeater data
contract or validation behavior.

#### Scenario: Existing URL constraints remain active

- **WHEN** an administrator manages product image links
- **THEN** the repeater still accepts at most five items
- **AND** each item still requires an HTTP(S) URL and applies the existing URL
  validation rules
