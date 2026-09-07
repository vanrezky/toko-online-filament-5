## 1. Planning and traceability

- [x] 1.1 Link the implementation plan to GitHub Issue #100.
- [x] 1.2 Define merged-group and responsive-navigation scenarios.

## 2. Implementation

- [x] 2.1 Rename the registered `Sistem` navigation group to `Pengaturan`.
- [x] 2.2 Move all current `Sistem` assignments to `Pengaturan` without
  changing individual menu metadata or access behavior.

## 3. Verification

- [x] 3.1 Verify no application navigation assignment still resolves to
  `Sistem` and existing `Pengaturan` destinations remain available.
- [x] 3.2 Run focused navigation validation and strict OpenSpec validation.
- [x] 3.3 Review the final diff against every Issue #100 acceptance criterion.
