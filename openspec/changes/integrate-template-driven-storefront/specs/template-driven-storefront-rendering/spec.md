## Purpose

Provides a stable contract for turning the active admin template into the storefront's ordered, enabled sections, shared colors, content values, and preview output.

## ADDED Requirements

### Requirement: Active template drives supported storefront sections

The storefront SHALL resolve the active template and render each supported homepage section according to the template's configured order. A section marked inactive SHALL not be rendered, and an unsupported or malformed section SHALL not prevent the remaining supported sections from rendering.

#### Scenario: Active sections render in configured order

- **WHEN** an active template contains supported sections with an explicit order
- **THEN** the storefront renders those sections in that order

#### Scenario: Inactive section is omitted

- **WHEN** a template section is marked inactive
- **THEN** the storefront does not render that section or its section data

#### Scenario: Unknown section does not break homepage

- **WHEN** an active template contains an unknown section type or incomplete section payload
- **THEN** the storefront skips that section and continues rendering valid supported sections

### Requirement: Template content keys have a documented storefront contract

The template system SHALL expose the content and display controls required by supported storefront sections, including existing controls for visibility, limits, discounts, timers, category selection, columns, load-more behavior, and background style, plus the enhanced hero content keys used by the storefront. Missing optional values SHALL use safe storefront defaults.

#### Scenario: Configured section controls affect output

- **WHEN** an administrator changes a supported section's configured display controls
- **THEN** the storefront applies those controls to the section output

#### Scenario: Optional content key is absent

- **WHEN** an optional template content key is absent
- **THEN** the storefront uses its documented fallback and remains usable

### Requirement: Template colors map to runtime design tokens

The storefront SHALL convert the configured template color values into the runtime design-token format consumed by the storefront. The mapping SHALL cover the five supported admin colors without silently dropping the configured text color, and invalid or missing values SHALL fall back to the existing safe theme.

#### Scenario: Five configured colors are applied

- **WHEN** the active template provides valid values for its five supported color fields
- **THEN** the storefront applies those values to the corresponding runtime tokens, including foreground text

#### Scenario: Color value is invalid or missing

- **WHEN** a configured color is missing or cannot be normalized
- **THEN** the storefront uses the safe fallback token and continues rendering

### Requirement: Preview uses the same template contract

The storefront SHALL provide an authorized preview path or mode that renders the selected template's section order, activation, content, and colors using the same mapping contract as the published storefront, without changing the active published template.

#### Scenario: Administrator previews a template

- **WHEN** an authorized administrator opens a template preview
- **THEN** the preview renders that selected template's supported configuration and clearly represents preview state

#### Scenario: Preview does not publish changes

- **WHEN** an administrator changes template settings while viewing preview
- **THEN** the published storefront remains unchanged until the template is explicitly activated or published through the existing admin flow
