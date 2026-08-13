## ADDED Requirements

### Requirement: Admin transaction detail summarizes installment obligations
The system SHALL show a read-only installment summary for a transaction with an existing installment relation. The summary MUST show monthly amount, tenor, total amount, paid-installment progress, status, start date, expected end date, and a link to the related installment detail.

#### Scenario: Admin views an installment transaction
- **WHEN** an admin opens a transaction with an installment relation
- **THEN** the transaction detail shows the installment summary and links to its installment detail

#### Scenario: Admin views a non-installment transaction
- **WHEN** an admin opens a transaction without an installment relation
- **THEN** the installment summary is not shown
