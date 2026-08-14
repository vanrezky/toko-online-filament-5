## ADDED Requirements

### Requirement: Customer can add a shipping address inside checkout
The checkout SHALL provide a customer-managed add-address action that opens a reusable address form dialog without navigating away from checkout. The form MUST collect the same address fields and use the same server validation and dependent regional selections as the existing account address form.

#### Scenario: Customer opens the add-address dialog
- **WHEN** a customer selects the add-address action in checkout
- **THEN** checkout SHALL open an accessible dialog containing the complete customer address form and preserve the checkout state behind it

#### Scenario: Customer submits a valid new address
- **WHEN** a customer submits a valid address from the checkout dialog
- **THEN** the system SHALL save it through the existing customer address route, close the dialog, show it in the checkout address list, and select it for the order

#### Scenario: Address validation fails
- **WHEN** the customer submits an incomplete or invalid address from the dialog
- **THEN** the dialog SHALL remain open and show each server validation error beside its applicable field

### Requirement: Address form is reusable across customer surfaces
The address form component SHALL expose the add-address workflow without duplicating field validation, regional lookup, loading, or error behavior between checkout and account address management.

#### Scenario: Customer uses the form from account management
- **WHEN** a customer opens the existing add-address experience from account management
- **THEN** it SHALL retain the same field set, dependent region behavior, validation feedback, and save behavior as the checkout form

#### Scenario: Customer changes a parent region
- **WHEN** the customer changes province, district, or sub-district in the shared form
- **THEN** the component SHALL clear invalid child selections and load the next available regional options before the customer submits

### Requirement: Checkout keeps large address lists compact
The checkout SHALL keep the selected shipping address visible while collapsing unselected addresses until the customer requests them.

#### Scenario: Customer has multiple saved addresses
- **WHEN** checkout loads with more than one saved address
- **THEN** it SHALL show up to two addresses in full-width cards and an action to reveal or collapse the remaining addresses
