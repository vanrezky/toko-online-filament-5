## ADDED Requirements

### Requirement: Homepage shell loads independently from catalog datasets

The homepage SHALL return its template, navigation shell, and SEO presentation without waiting for product, category, slider, or flash-sale datasets that are required by active sections. Each required dataset SHALL be marked for asynchronous loading, and independent datasets SHALL be eligible to resolve in separate batches. Datasets for section types that are not active SHALL not be queried or requested.

#### Scenario: Initial homepage response contains the shell first

- **WHEN** a visitor opens the homepage with active product, category, slider, and flash-sale sections
- **THEN** the initial response contains the template and shell presentation without the resolved catalog datasets
- **AND** the response identifies each required dataset for asynchronous loading

#### Scenario: Unused datasets are skipped

- **WHEN** an active template contains no product, category, slider, or flash-sale section
- **THEN** the homepage does not query or request the corresponding unused datasets
- **AND** the final page still renders the configured section order

### Requirement: Deferred homepage sections remain usable while data resolves

Each homepage section that depends on an asynchronously loaded dataset SHALL expose an accessible loading fallback while that dataset is unavailable. The shell, navigation, static sections, and links SHALL remain usable if a deferred dataset fails, and a successful resolution SHALL replace the fallback with the same section content, links, and order as the current homepage.

#### Scenario: Section fallback is visible during loading

- **WHEN** the homepage shell has loaded but a section dataset is still unavailable
- **THEN** the section position contains a visible, localized loading fallback with an accessible status
- **AND** static sections and navigation remain available

#### Scenario: Deferred data resolves successfully

- **WHEN** a deferred product, category, slider, or flash-sale dataset resolves
- **THEN** its loading fallback is replaced by the corresponding section
- **AND** section order, content, links, filtering behavior, and template preview behavior remain unchanged

#### Scenario: Deferred data fails

- **WHEN** a deferred dataset cannot be loaded
- **THEN** the homepage shell and all unrelated sections remain usable
- **AND** the affected position retains a non-blocking accessible fallback instead of making the whole page unusable
