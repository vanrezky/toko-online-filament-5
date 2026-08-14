## ADDED Requirements

### Requirement: Customer account destinations use consistent URL-backed navigation
The system SHALL present overview, profile settings, change password, addresses, orders, and wishlist as grouped customer-account destinations. The active destination MUST be derivable from the URL so refresh and browser navigation preserve customer context.

#### Scenario: Customer changes account destination
- **WHEN** a customer selects an account or shopping destination
- **THEN** the application navigates to its URL-backed destination and indicates it as active

#### Scenario: Customer refreshes an account destination
- **WHEN** a customer refreshes or returns with browser navigation
- **THEN** the same destination remains active without requiring the customer to select it again

#### Scenario: Customer opens the direct wishlist route
- **WHEN** a customer opens the Wishlist route directly
- **THEN** Wishlist remains a dedicated product-list page and the shared account navigation is displayed with Wishlist active

### Requirement: Customer account navigation adapts to mobile
The system SHALL keep every account destination available on mobile through a compact touch-friendly navigation control before the active content. It MUST NOT require the customer to scroll through a desktop sidebar to reach the selected content.

#### Scenario: Customer opens account on mobile
- **WHEN** a customer views the account page on a narrow viewport
- **THEN** they can select all account and shopping destinations from the compact navigation control

### Requirement: Session actions are distinct from navigation
The system SHALL visually separate logout from ordinary account and shopping destinations while preserving the existing logout behavior.

#### Scenario: Customer views account navigation
- **WHEN** the account navigation is displayed
- **THEN** logout is shown in a distinct session group after the regular destinations
