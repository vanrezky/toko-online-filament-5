## MODIFIED Requirements

### Requirement: Customer can add or edit a shipping address inside checkout

Checkout SHALL provide an in-context address action that opens the reusable address form without navigating away from checkout. The form MUST preserve the existing server validation and dependent regional selections for both new and existing customer-managed addresses.

#### Scenario: Customer opens the edit action

- **WHEN** a customer selects the edit action for an address they can manage
- **THEN** checkout SHALL open the address form populated with that address and expose an accessible edit action

#### Scenario: Customer submits a valid address edit

- **WHEN** a customer submits valid changes for an existing customer-managed address
- **THEN** the system SHALL save through the existing address update route, close the form, refresh the address presentation, and refresh shipping costs when that address is selected

#### Scenario: Customer cannot edit an admin-managed address

- **WHEN** checkout displays an address that the customer cannot manage
- **THEN** checkout SHALL not expose a customer edit action for that address

#### Scenario: Address validation fails

- **WHEN** a customer submits incomplete or invalid address data
- **THEN** the form SHALL remain open and show each server validation error beside its applicable field

### Requirement: Address form remains reusable across customer surfaces

The shared address form SHALL preserve the existing add and edit behavior, field set, dependent regional lookups, loading state, and server-error rendering used by checkout and account address management.

#### Scenario: Customer changes a parent region

- **WHEN** a customer changes province, district, or sub-district in the shared form
- **THEN** the component SHALL clear invalid child selections and load the next available regional options before submission

### Requirement: Checkout keeps large address lists compact

Checkout SHALL keep the selected shipping address visible while collapsing unselected addresses until the customer requests them.

#### Scenario: Customer has multiple saved addresses

- **WHEN** checkout loads more than two saved addresses
- **THEN** checkout SHALL show at most two addresses initially and provide an accessible action to reveal or collapse the remaining addresses
