# Design

The existing `Repeater::make('image_urls')` in
`app/Filament/Resources/Products/ProductResource.php` is shared by Filament's
resource form schema, so the same configuration is used by both the create and
edit pages. Filament's `compact()` option is rendered by the table repeater
view, so configure this repeater with one URL table column and then enable
`compact()`.

No controller, page, model, migration, or data-processing changes are needed.
The table repeater changes presentation only: its single URL column keeps the
existing field and the built-in add, delete, and reorder controls while
preserving validation and the persisted payload.
