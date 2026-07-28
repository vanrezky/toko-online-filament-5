## Context

`AccountController` currently rejects every storefront address mutation. The profile page consequently renders every address as admin-managed, even though it retains unused CRUD form logic. `GeneralSettings::is_private_store` is the backend source of truth and is already shared to Inertia. Address provenance is available through the existing `customer_addresses.source_type` string, which currently distinguishes `school_unit` from existing manual addresses.

## Goals / Non-Goals

**Goals:**

- Permit only public-store customers to manage addresses they created.
- Preserve admin and school-unit address control from the storefront.
- Make public/private behavior clear in the address tab while keeping backend enforcement authoritative.
- Preserve admin address management in both modes.

**Non-Goals:**

- Migrate existing address records or change the private-store configuration flow.
- Change checkout shipping/payment behavior or regional data APIs.

## Decisions

### Use `source_type=customer` for storefront-created records

The existing column is a flexible string and already records `school_unit`. A dedicated `customer` value separates customer-owned records from the existing `manual` admin records without a schema migration. The source is assigned server-side and is never trusted from the request.

### Authorize every mutation in the controller

`GeneralSettings::is_private_store`, authenticated customer identity, and `source_type` will be checked before validation and persistence. This prevents crafted requests from bypassing the frontend and rejects cross-customer routes or admin-managed records with `403`.

### Expose editability explicitly to the profile resource

The address resource will communicate whether a record is customer-editable. The profile uses that field plus shared store mode to show available controls and the correct explanation; it does not become the security boundary.

### Keep one customer-selected default address

When a customer selects a customer-owned address as default, it becomes the only featured address for that customer so checkout continues to resolve a deterministic default. Existing admin/school-unit addresses are not mutated by ordinary field edits, but their featured state can be superseded as part of selecting the customer's own default.

### Restore admin CRUD through the existing relation manager

The Filament relation manager remains the admin entrypoint and will expose create/edit/delete actions in both store modes. Customer-created addresses remain distinguishable but are still fully manageable by admins.

## Risks / Trade-offs

- [Existing address sources may contain unexpected values] → treat only `customer` as storefront-editable; all other sources remain admin-managed.
- [A customer default can supersede an admin-provided default] → this is necessary for the public-store customer default to affect checkout; private stores retain the existing admin-controlled state.
- [Frontend visibility can drift from configuration] → backend checks settings on every mutation and tests cover direct endpoints.

## Migration Plan

No database migration is needed because `source_type` is an existing indexed `varchar`. Deploy the application change, then rollback by restoring the prior controller behavior; customer-created records remain safely admin-managed after rollback.

## Open Questions

None.
