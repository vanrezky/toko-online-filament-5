## Purpose

This capability keeps the Account Profile shell and core account actions usable immediately while loading secondary regional, order, and balance data asynchronously.

## ADDED Requirements

### Requirement: Core account data is available in the initial render

The Account Profile initial render SHALL provide customer profile data, customer addresses, account settings, authorization state, and existing balance visibility configuration without waiting for secondary datasets.

#### Scenario: Customer opens the account profile

- **WHEN** an authenticated customer opens the Account Profile page
- **THEN** profile information and address-management UI can render while secondary data is still loading

#### Scenario: Balance is disabled

- **WHEN** the store configuration disables customer balance
- **THEN** the Account Profile does not request or display balance history

### Requirement: Secondary account datasets load asynchronously

The Account Profile SHALL load province options, recent orders with their total count, and balance history asynchronously when those datasets are applicable.

#### Scenario: Secondary data is pending

- **WHEN** the initial Account Profile response has completed but secondary datasets are unavailable
- **THEN** each affected secondary section displays an accessible loading state and the core profile remains usable

#### Scenario: Secondary data resolves

- **WHEN** the deferred secondary data request succeeds
- **THEN** province options, recent orders, total order count, and balance history render with the same content, ordering, limits, and visibility rules as the existing page

### Requirement: Deferred failures preserve core account actions

The Account Profile SHALL handle a secondary dataset failure without preventing profile viewing, address management, or other already-available account actions.

#### Scenario: Secondary data request fails

- **WHEN** a deferred regional, order, or balance dataset cannot be loaded
- **THEN** the affected section shows its existing empty or error fallback and profile and address actions remain available
