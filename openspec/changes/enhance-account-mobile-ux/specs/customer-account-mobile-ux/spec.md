## Purpose

This capability gives mobile customers a clear, touch-friendly account experience that preserves the existing destinations while presenting the account overview and navigation in the hierarchy established by the approved mockup.

## ADDED Requirements

### Requirement: Mobile account navigation is scannable and touch-friendly

On mobile viewports, the account experience SHALL present every available account destination in a full-width, vertically scannable menu without requiring horizontal scrolling. Each destination SHALL expose a touch target of at least 44 CSS pixels in height, and the current destination SHALL have a distinct visual and accessible active state.

#### Scenario: Customer opens the account overview on a narrow viewport

- **WHEN** an authenticated customer opens `/account` at a mobile viewport
- **THEN** the available account destinations are presented without clipped labels or horizontal scrolling
- **AND** the overview destination is visibly marked as active

#### Scenario: Customer selects a mobile account destination

- **WHEN** the customer taps an account destination
- **THEN** the application navigates to the same existing URL-backed destination used by the desktop account navigation
- **AND** the selected destination remains visibly active after navigation

### Requirement: Mobile overview prioritizes identity and account context

The mobile account overview SHALL present the customer's identity before the account summary cards, followed by balance and order summaries, the default shipping address, recent activity, and the account menu. Existing data, actions, and empty states SHALL remain available.

#### Scenario: Customer views a populated account overview

- **WHEN** the account overview has customer, balance, order, address, and activity data
- **THEN** the mobile page presents those sections in the specified priority order
- **AND** each existing action remains usable without changing its destination or behavior

#### Scenario: Customer views an overview without a default address or recent activity

- **WHEN** the account overview lacks a default address or recent orders
- **THEN** the corresponding existing empty or fallback content remains visible in the mobile composition
- **AND** the account menu remains reachable after the overview content

### Requirement: Non-overview destinations provide mobile context

Mobile account destinations other than the overview SHALL provide a clear account context and a way back to the overview while preserving their existing forms, data, validation, and submit actions.

#### Scenario: Customer opens a non-overview account destination

- **WHEN** the customer opens wallet balance, settings, password, or shipping addresses on mobile
- **THEN** the page identifies the active destination and provides a visible navigation affordance back to the account overview
- **AND** the destination's existing content and actions remain available

### Requirement: Account mobile UX remains localized and responsive

The mobile account experience SHALL use the active Indonesian or English locale for all newly introduced visible labels and SHALL leave the existing desktop account navigation and functionality intact.

#### Scenario: Customer changes the active locale

- **WHEN** the customer views the mobile account experience in Indonesian or English
- **THEN** navigation, context, and newly introduced labels use the matching locale
- **AND** no new user-facing label is hardcoded in a Vue template or script

#### Scenario: Customer views the account on desktop

- **WHEN** the customer opens the account page at a desktop viewport
- **THEN** the existing desktop account shell and destination behavior remain available
- **AND** the mobile-only composition does not replace or obscure the desktop navigation
