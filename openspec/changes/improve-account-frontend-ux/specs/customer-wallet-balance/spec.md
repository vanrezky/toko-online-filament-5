## Purpose

Give authenticated customers a clear account destination for their current wallet balance and the balance usage history already maintained by the application.

## ADDED Requirements

### Requirement: Customer can view wallet balance and history in a dedicated account destination
The system SHALL provide an account destination where an authenticated customer can view their current wallet balance and the available balance history belonging to that customer. The destination MUST respect the existing balance-enabled setting.

#### Scenario: Customer opens wallet balance destination when enabled
- **WHEN** an authenticated customer selects the wallet balance destination while balance is enabled
- **THEN** the application displays the customer's current balance and balance history entries with their amount direction, notes, and date

#### Scenario: Customer has no wallet history
- **WHEN** an authenticated customer opens the wallet balance destination with no balance history
- **THEN** the application displays the current balance and a localized empty-state message for the history

#### Scenario: Wallet balance feature is disabled
- **WHEN** an authenticated customer views account navigation while balance is disabled
- **THEN** the wallet balance destination and its balance content are not presented

### Requirement: Account overview does not duplicate wallet history
The system SHALL keep the account overview focused on summary information and MUST NOT render the wallet balance history list there after the dedicated wallet balance destination is available.

#### Scenario: Customer views account overview
- **WHEN** an authenticated customer opens the account overview
- **THEN** the overview may show the current balance summary but does not show the wallet balance history list
