## 1. Product image URL input and import flow

- [x] 1.1 Add a transient, repeatable URL input to the ProductResource image tab with server-side HTTP(S) URL validation and administrator-facing helper/error text.
- [x] 1.2 Add a focused product media import path that validates remote image content, enforces the remaining five-image capacity, and imports successful URLs into the existing media collection in input order.
- [x] 1.3 Integrate URL imports with the Filament create and edit lifecycle without persisting transient form state as a Product model attribute or disturbing uploaded images.

## 2. Product media response coverage

- [x] 2.1 Confirm the media import uses the existing collection and thumbnail conversion consumed by ProductResource and ProductSimpleResource; adjust resource code only if a compatibility gap is demonstrated.
- [x] 2.2 Add regression coverage proving imported media is serialized through the existing thumbnail and images response fields.

## 3. Validation and verification

- [x] 3.1 Add focused tests for valid URL imports, malformed/unavailable/non-image inputs, combined image-limit enforcement, and coexistence with uploads.
- [x] 3.2 Run the focused backend test suite and static/style checks relevant to changed PHP files.
- [x] 3.3 Run OpenSpec validation and reconcile every Issue #31 acceptance criterion with specs, implementation, and tests.

## 4. Image-source progressive disclosure

- [x] 4.1 Make the URL repeater an initially collapsed secondary panel while retaining upload as the primary image entry surface and mixed-source support.
- [x] 4.2 Run focused validation for the adjusted Filament image form and OpenSpec artifacts.

## 5. Combined image-count validation

- [x] 5.1 Validate the combined uploaded-file and linked-image count in the ProductResource form before persistence.
- [x] 5.2 Add and run regression coverage for three existing images plus three linked images exceeding the five-image limit.
