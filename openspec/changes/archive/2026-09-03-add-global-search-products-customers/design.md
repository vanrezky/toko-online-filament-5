## Context

`ProductResource` and `CustomerResource` are existing Filament resources with authorized Edit/View pages. Filament's default Global Search provider discovers resources that expose searchable attributes and a record title, then resolves the resource URL according to available permissions.

## Goals / Non-Goals

**Goals:**

- Configure the two existing resources for Global Search using Filament's resource hooks.
- Use database-backed attributes for matching and readable result titles/details.
- Preserve Filament authorization and existing resource routes.
- Add automated coverage for configuration and browser-visible search results.

**Non-Goals:**

- Adding a custom search provider or external search engine.
- Changing database schema, resource forms, tables, or authorization policy.
- Registering other resources in Global Search.

## Decisions

1. Use Filament resource-level Global Search configuration rather than a custom provider.
   This keeps search behavior within the existing resource authorization and URL resolution. A custom provider would add unnecessary infrastructure for two Eloquent resources.

2. Search Product fields that are stored and meaningful to admins: `name`, `code`, `slug`, and the related `category.name`.
   The category relationship is included through Filament's supported dot notation so an admin can locate products by category without adding denormalized columns.

3. Search Customer fields `first_name`, `last_name`, `email`, `username`, and `phone`, and compose the result title from the existing `full_name` accessor.
   The accessor is suitable for presentation but not a direct database search field, so the underlying columns remain the searchable attributes.

4. Provide concise result details and eager-load only required relationships.
   Product results may show category; customer results use stored identity/contact fields. Eager loading prevents avoidable relationship queries when details are rendered.

5. Cover the resource hooks with focused tests and verify the actual Global Search interaction through Playwright on the running admin panel.
   Unit-level checks protect the configuration contract, while browser testing confirms the Livewire search UI and navigation behavior.

## Risks / Trade-offs

- [Risk] Searching relationship attributes can add joins or relationship constraints to the query. → Mitigation: keep the relationship scope limited to the product category and rely on Filament's built-in query implementation.
- [Risk] Customer names are split across two columns. → Mitigation: configure both name columns and use the full-name accessor only for the displayed title.
- [Risk] Resource authorization can hide a result URL for an administrator. → Mitigation: rely on Filament's default permission-aware Edit/View URL behavior and test with the authorized admin account.
