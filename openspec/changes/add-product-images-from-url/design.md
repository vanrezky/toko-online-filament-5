## Context

Products store their images in Spatie Media Library and the Filament form currently accepts only uploaded files in the `images` field. Storefront resources derive both `thumbnail` and `images` from that media collection, so retaining a remote URL as an unverified string would create a second image source and diverge from existing response behavior.

## Goals / Non-Goals

**Goals:**

- Let authorized administrators import public image URLs into the existing product media collection.
- Keep uploads and imported URLs within the existing five-image limit and media order.
- Preserve the existing product response contract by serving imported images through the normal media collection.
- Fail individual invalid imports clearly without leaving partial media records.

**Non-Goals:**

- Store remote URLs as a new product field or expose a new response field.
- Support authenticated remote sources, non-image content, product variant images, or a remote-media-provider integration.

## Decisions

### Import linked images into the existing media collection

The product save flow will use the installed Media Library v11 URL-import capability after the product exists, adding each accepted source to the same collection used by uploads. This keeps tables, Filament image columns/infolists, conversions, ordering, and resource serialization unchanged.

Alternative considered: persist URLs in a JSON or database column and merge them into resource responses. Rejected because it would bypass image conversions, split ordering logic, and alter multiple response consumers.

### Treat URL imports as a separate transient form state

The Filament form will capture URL entries separately from the file-upload state and remove that transient state before Eloquent fill/create. A post-create/post-save hook will perform imports, making the field safe for both create and edit without adding a model attribute.

Alternative considered: overload the upload component state with URL strings. Rejected because the component expects uploaded file state and would make its lifecycle and validation unreliable.

### Reveal URL entry in a collapsed secondary panel

The file-upload gallery remains the primary image-entry surface. A collapsed, optional panel labeled for linked images contains the URL repeater and opens only when the administrator chooses it. This preserves mixed-source support without presenting two competing input methods simultaneously.

Alternative considered: automatically hide uploads after a URL is added. Rejected because it would make a valid mixed-source workflow disappear unexpectedly and could cause confusion when changing input methods.

### Validate the combined count in the form and importer

The URL repeater validates the count of selected uploads plus non-empty URL entries before the form is created or saved. The importer retains its media-collection count check because form state can be bypassed or become stale between validation and persistence.

### Validate before import and preserve collection consistency

Each entry must be an HTTP(S) URL. The importer will reject fetch failures and non-supported image media; the effective remaining collection capacity will be checked before importing. Failed validation or import must not add a media record; successful imports retain their input order after existing media.

Alternative considered: rely only on client-side URL validation. Rejected because redirects, availability, and remote content type are server-observable conditions.

## Risks / Trade-offs

- [Server-side request to arbitrary public URL] → Restrict URL schemes to HTTP(S), reject local/private network targets and unsupported image responses, and use the framework/media-library import path rather than accepting user-controlled file paths.
- [Remote host unavailable or content changes] → Download accepted assets into managed media storage; report import failures to the admin.
- [External image formats or large assets] → Apply the same supported image type and size safeguards as uploaded product images before media is persisted.
- [Partial multi-URL import] → Process entries independently, retain only successful media records, and show which inputs failed; do not remove existing images.
