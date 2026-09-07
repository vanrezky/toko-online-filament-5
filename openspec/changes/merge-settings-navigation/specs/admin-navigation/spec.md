# Admin Navigation

## MODIFIED Requirements

### Requirement: Admin settings navigation uses one group

The admin panel MUST expose one `Pengaturan` navigation group containing all
items that were previously assigned to `Sistem` and all items that already
belonged to `Pengaturan`.

#### Scenario: System items appear under Pengaturan

- **WHEN** an administrator views the admin navigation
- **THEN** destinations previously assigned to `Sistem` appear under
  `Pengaturan`
- **AND** no separate `Sistem` group is rendered

#### Scenario: Existing settings destinations remain available

- **WHEN** an administrator opens the `Pengaturan` group
- **THEN** all destinations previously available under `Pengaturan` remain
  available
- **AND** their URLs, permissions, icons, and behavior are unchanged

### Requirement: Admin navigation remains consistent across layouts

The merged group MUST use the same navigation configuration for desktop and
responsive/mobile admin layouts.

#### Scenario: Responsive admin navigation uses the merged group

- **WHEN** an administrator views the admin navigation in desktop or
  responsive/mobile layout
- **THEN** the same `Pengaturan` group and its destinations are available
- **AND** no `Sistem` group is shown in either layout
