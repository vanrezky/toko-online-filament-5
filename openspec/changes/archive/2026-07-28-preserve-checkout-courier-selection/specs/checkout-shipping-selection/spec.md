## ADDED Requirements

### Requirement: Preserve available courier selections during shipping-cost refresh
The checkout SHALL retain the selected `courier_code` for each warehouse when the latest shipping-cost response for the active address offers that courier.

#### Scenario: Selected courier remains available
- **WHEN** a customer changes the shipping address and the latest response still contains the selected courier for a warehouse
- **THEN** checkout SHALL keep that courier selected and replace its price, estimation, and weight with the latest response values

### Requirement: Resolve unavailable courier selections safely
The checkout SHALL select a currently available fallback for each warehouse whose selected courier is absent from the latest shipping-cost response, and SHALL inform the customer that one or more shipping choices changed.

#### Scenario: Selected courier becomes unavailable
- **WHEN** a customer changes the shipping address and the selected courier is absent from the latest options for a warehouse
- **THEN** checkout SHALL select the first available option for that warehouse and show a non-blocking warning

#### Scenario: Shipping options cover multiple warehouses
- **WHEN** the latest response contains options for multiple warehouses
- **THEN** checkout SHALL reconcile the selection independently for each warehouse and SHALL only include warehouses present in that response in the checkout payload

### Requirement: Apply only the latest shipping-cost response
The checkout SHALL ignore a shipping-cost response whose request is older than the most recent request initiated for the selected address.

#### Scenario: Address changes before an earlier request completes
- **WHEN** a customer selects another address while an earlier shipping-cost request remains pending
- **THEN** checkout SHALL keep the options and selections produced by the newest request
