## ADDED Requirements

### Requirement: Account content uses a consistent card hierarchy
The system SHALL use a consistent card treatment for account content sections, including common spacing, radius, and elevation. Section headers SHALL consistently explain the active customer task.

#### Scenario: Customer switches account sections
- **WHEN** a customer opens overview, profile settings, or addresses
- **THEN** the active section presents a consistent header and content-surface treatment

### Requirement: Profile photo editing is touch accessible
The system SHALL expose a visible, accessible profile-photo edit control that works without hover input.

#### Scenario: Customer updates a profile photo on touch device
- **WHEN** a customer uses a touch device on profile settings
- **THEN** they can discover and activate the photo edit control without hover

### Requirement: Existing account states remain clear
The system SHALL retain understandable empty, validation-error, and private-store address states after the layout is refined.

#### Scenario: Customer cannot manage an address
- **WHEN** a private-store customer views an admin-managed address
- **THEN** the address ownership status remains visible and unavailable actions are not presented as customer controls
