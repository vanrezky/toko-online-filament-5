## Why

The product creation form currently requires administrators to upload image files even when the desired product image already has a public URL. Supporting image URLs lets administrators create products from existing online assets while preserving the media-backed product response consumed by storefront surfaces.

Linked GitHub Issue: #31

## What Changes

- Add a product-image URL input to the Filament product create and edit form.
- Import valid linked images into the product media collection alongside uploaded images, subject to the existing five-image limit and ordering behavior.
- Validate malformed, inaccessible, and unsupported image URLs without leaving partially imported media.
- Ensure imported media continues to populate the existing `thumbnail` and `images` fields in product resource responses.

## Capabilities

### New Capabilities

- `product-image-url-import`: Administrators can add product images from public image URLs and receive clear validation failures.

### Modified Capabilities

- None.

## Impact

- `app/Filament/Resources/Products/ProductResource.php` form and product media persistence flow.
- Product media handling in `app/Models/Product.php`.
- Storefront response transformers `app/Http/Resources/ProductResource.php` and `app/Http/Resources/ProductSimpleResource.php`.
- Focused Filament and resource serialization regression tests.
