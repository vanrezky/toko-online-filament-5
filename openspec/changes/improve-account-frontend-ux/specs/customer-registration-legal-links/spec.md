## Purpose

Let customers review registration legal documents without losing the values already entered in the registration form.

## ADDED Requirements

### Requirement: Registration legal links open in a separate browser tab
The system SHALL render the Terms & Conditions and Privacy Policy links on registration as separate-tab links while preserving their existing destinations and consent behavior. New-tab links MUST use safe opener isolation attributes.

#### Scenario: Customer opens Terms & Conditions
- **WHEN** a customer activates the Terms & Conditions link on the registration form
- **THEN** the legal page opens in a new browser tab and the registration page remains available in the original tab

#### Scenario: Customer opens Privacy Policy
- **WHEN** a customer activates the Privacy Policy link on the registration form
- **THEN** the legal page opens in a new browser tab and the registration page remains available in the original tab
