## Purpose

Provide a consistent visual empty state for account collections while preserving each collection's localized message, data condition, icon meaning, and available action.

## ADDED Requirements

### Requirement: Account collection empty states share the Wishlist treatment
The system SHALL present empty shipping addresses, empty orders, and empty wishlist collections using the same box surface, spacing, rounded border, icon treatment, title hierarchy, and description typography established by the Wishlist empty state.

#### Scenario: Customer has no shipping addresses
- **WHEN** an authenticated customer opens shipping addresses without any address records
- **THEN** the application displays the address-specific empty content inside the shared empty-state treatment

#### Scenario: Customer has no orders
- **WHEN** an authenticated customer opens orders without any order records
- **THEN** the application displays the order-specific empty content inside the shared empty-state treatment

#### Scenario: Customer has no wishlist items
- **WHEN** a customer opens wishlist without any wishlist records
- **THEN** the application displays the existing wishlist empty content and catalogue action using the shared treatment

#### Scenario: Collection contains data
- **WHEN** shipping addresses, orders, or wishlist contains one or more records
- **THEN** the application renders the populated collection layout and does not display its empty state
