## Purpose

Memberikan akses pencarian cepat kepada admin untuk menemukan dan membuka record Produk serta Pelanggan dari seluruh panel admin.

## ADDED Requirements

### Requirement: Admin can search products globally

The system SHALL include product records in the admin Global Search and SHALL match queries against the product's primary searchable attributes.

#### Scenario: Product search returns matching records

- **WHEN** an authorized administrator enters a query matching a product attribute
- **THEN** Global Search displays the matching product with a clear title and a link to the product resource

#### Scenario: Product search has no matches

- **WHEN** an administrator enters a query that matches no product
- **THEN** Global Search does not return a product result and the panel remains usable

### Requirement: Admin can search customers globally

The system SHALL include customer records in the admin Global Search and SHALL match queries against the customer's primary searchable attributes.

#### Scenario: Customer search returns matching records

- **WHEN** an authorized administrator enters a query matching a customer attribute
- **THEN** Global Search displays the matching customer with the customer's name as a clear title and a link to the customer resource

#### Scenario: Customer search has no matches

- **WHEN** an administrator enters a query that matches no customer
- **THEN** Global Search does not return a customer result and the panel remains usable

### Requirement: Global search results respect resource access

The system SHALL only return records and destination URLs that the current administrator is authorized to access.

#### Scenario: Search result opens the authorized resource page

- **WHEN** an administrator selects a product or customer result
- **THEN** the system opens the corresponding resource page permitted for that administrator
