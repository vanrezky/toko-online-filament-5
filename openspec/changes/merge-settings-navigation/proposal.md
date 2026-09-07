# Proposal: Merge System and Settings Navigation Groups

GitHub Issue: #100

## Why

The admin panel currently presents system and settings destinations in separate
navigation groups. Administrators should find these related controls under one
familiar `Pengaturan` group.

## What Changes

Rename the existing `Sistem` navigation group to `Pengaturan` and move every
resource, cluster, and page currently assigned to `Sistem` into that group.
Existing items that already use `Pengaturan` remain in the same group.

## Scope

### In scope

- Admin panel navigation group registration.
- Navigation-group assignments for existing system/settings resources,
  clusters, and pages.
- Indonesian navigation-group translation values.

### Out of scope

- Individual menu names, URLs, permissions, icons, or page behavior.
- Other navigation groups.
- Storefront or customer navigation.

## Definition of Done

- Issue #100 acceptance criteria are implemented.
- Navigation regression validation covers the merged group and preserved items.
- OpenSpec validation passes.
- A pull request targets `dev` and links Issue #100.
