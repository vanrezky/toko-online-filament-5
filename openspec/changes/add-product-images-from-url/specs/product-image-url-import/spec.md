## ADDED Requirements

### Requirement: Product images can be imported from public URLs
The system SHALL allow an authorized administrator to enter one or more public HTTP(S) image URLs while creating or editing a product. Each successfully imported URL SHALL be added to the product's existing media collection alongside uploaded product images.

#### Scenario: Create a product with a valid image URL
- **WHEN** an administrator submits a valid public image URL while creating a product
- **THEN** the system imports the image into the product media collection and saves the product successfully

#### Scenario: Add linked images to a product with uploaded images
- **WHEN** an administrator submits valid image URLs and uploaded image files for the same product
- **THEN** the system retains both sets of images in the product media collection

### Requirement: Linked image imports are validated and bounded
The system SHALL accept only valid HTTP(S) URLs that can be retrieved and verified as a supported image. The combined uploaded and linked image count SHALL NOT exceed five, and a failed linked-image import SHALL NOT create an invalid or partial media record.

#### Scenario: Reject a malformed or unsupported URL
- **WHEN** an administrator submits a malformed URL, an unreachable URL, or a URL whose retrieved content is not a supported image
- **THEN** the system reports a validation or import error and does not add media for that URL

#### Scenario: Reject image URLs beyond the product image limit
- **WHEN** submitted uploads and linked images would make the product contain more than five images
- **THEN** the system rejects the excess linked images without removing existing product media

### Requirement: Imported images use the existing product response contract
The system SHALL expose successfully imported product images through the existing media-derived `thumbnail` and `images` fields returned by `ProductResource` and `ProductSimpleResource`, without requiring new consumer fields.

#### Scenario: Serialize a product imported from an image URL
- **WHEN** a product with an imported linked image is transformed by a product resource
- **THEN** its existing thumbnail and image output references the imported media in the same manner as an uploaded product image
