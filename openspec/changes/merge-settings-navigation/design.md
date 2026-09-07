# Design

The navigation group identity is represented in two places: the panel's
ordered `navigationGroups()` list and the group values returned by resources,
clusters, and settings pages. Update both representations from `Sistem` to
`Pengaturan` so Filament resolves all items to the same group key.

The affected assignments are the settings cluster, monitoring cluster, user
and email-template resources, template resources, and the settings page
translation values. The Shield role resource already uses its own
`Pengaturan` group and remains unchanged. Individual navigation metadata and
access control are not modified.

No migration, controller, model, route, permission, or frontend application
change is needed. Filament renders the same resolved navigation tree for its
desktop and responsive/mobile layouts, so one panel configuration keeps both
layouts consistent.
