## MODIFIED Requirements

### Requirement: Preserve available courier selections during shipping-cost refresh

Checkout SHALL request shipping options after an active delivery address is selected and SHALL retain the selected `courier_code` for each warehouse when that courier remains available in the latest response. Checkout SHALL present address selection before shipping-method selection.

#### Scenario: Customer selects an existing address

- **WHEN** a customer selects a delivery address in checkout
- **THEN** checkout SHALL request shipping options for that address and present the resulting shipping-method choices in the next checkout step

#### Scenario: Selected courier remains available

- **WHEN** the selected courier remains in the latest response for a warehouse
- **THEN** checkout SHALL keep it selected and replace its price, estimation, and weight with the latest values

### Requirement: Resolve unavailable courier selections safely

When a selected courier is absent from the latest shipping-cost response, checkout SHALL select the first available option for that warehouse and inform the customer that shipping choices changed.

#### Scenario: Selected courier becomes unavailable

- **WHEN** a selected courier is absent from the latest options for a warehouse
- **THEN** checkout SHALL select the first available option for that warehouse and show a non-blocking warning

#### Scenario: Shipping options cover multiple warehouses

- **WHEN** the latest response contains options for multiple warehouses
- **THEN** checkout SHALL reconcile selection independently per warehouse and include only returned warehouses in the checkout payload

### Requirement: Apply only the latest shipping-cost response

Checkout SHALL ignore a shipping-cost response from a request older than the most recent request initiated for the selected address.

#### Scenario: Address changes before an earlier request completes

- **WHEN** a customer selects another address while an earlier shipping-cost request is pending
- **THEN** checkout SHALL keep the options and selections produced by the newest request

## ADDED Requirements

### Requirement: Present courier details clearly

Checkout SHALL display each courier name in full without ellipsis, show delivery estimation at a readable size, and display the matching existing courier logo when one is available. If no logo mapping exists, checkout SHALL retain the current pickup or truck icon.

#### Scenario: Known courier has a logo

- **WHEN** a shipping option has a known courier code with an existing public asset
- **THEN** its card SHALL display that courier logo beside the full courier name and estimation

#### Scenario: Courier logo is unavailable

- **WHEN** a shipping option has no matching logo mapping or uses pickup
- **THEN** its card SHALL display the existing truck or pickup icon without a broken image
