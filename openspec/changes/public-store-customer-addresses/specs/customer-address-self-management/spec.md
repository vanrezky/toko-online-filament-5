## ADDED Requirements

### Requirement: Customer address management in public stores
The system SHALL allow an authenticated customer to create, update, delete, and select as default only addresses with `source_type` `customer` when `is_private_store` is false. The system MUST assign `source_type` `customer` when creating an address from the storefront and MUST persist a single customer-selected default address.

#### Scenario: Customer creates and manages an address in a public store
- **WHEN** an authenticated customer submits a valid address mutation while the store is public
- **THEN** the system persists the address for that customer with source `customer` and permits later mutations of that address

#### Scenario: Customer selects a customer-owned default address
- **WHEN** an authenticated customer marks one of their customer-owned addresses as default in a public store
- **THEN** the system marks that address as featured and clears the featured state from the customer's other addresses

### Requirement: Admin-managed address protection
The system SHALL treat every storefront address source other than `customer`, including `manual` and `school_unit`, as admin-managed. It MUST reject a customer attempt to mutate an address owned by another customer or an admin-managed address with HTTP 403.

#### Scenario: Customer attempts to edit a school-unit address
- **WHEN** a customer sends an update or delete request for an address with source `school_unit`
- **THEN** the system returns HTTP 403 and leaves the address unchanged

#### Scenario: Customer attempts to mutate another customer's address
- **WHEN** a customer sends a mutation request for another customer's address
- **THEN** the system returns HTTP 403 and leaves the address unchanged

### Requirement: Private-store address control
The system SHALL reject every customer address mutation with HTTP 403 when `is_private_store` is true. The profile address tab MUST inform the customer that an administrator manages addresses and MUST not expose customer mutation controls in private-store mode.

#### Scenario: Customer submits an address in a private store
- **WHEN** an authenticated customer posts, patches, or deletes an address while the store is private
- **THEN** the system returns HTTP 403 and does not change address data

### Requirement: Admin address management across store modes
The system SHALL allow an administrator to create, update, and delete customer addresses through the existing admin customer-address management surface regardless of `is_private_store`.

#### Scenario: Admin adds an address while the store is private
- **WHEN** an administrator opens a customer record in a private store
- **THEN** the customer-address management surface provides an action to create an address
