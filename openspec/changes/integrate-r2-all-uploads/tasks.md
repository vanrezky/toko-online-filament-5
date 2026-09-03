## 1. Base configuration and inventory

- [ ] 1.1 Verify the R2 filesystem disk, S3-compatible dependency, environment example, and tester prerequisite from Issue #84/PR #85 are available on this branch.
- [x] 1.2 Add a shared, environment-backed persistent-upload disk resolution without changing the global default or the user's unrelated `ProductResource.php` and `helper.php` edits.
- [x] 1.3 Add focused test helpers/fakes for asserting R2 disk selection and failed-write cleanup.

## 2. Admin persistent uploads

- [x] 2.1 Route product main images, admin review images, and custom product variant persistence to R2 while preserving collections, paths, validation, previews, and conversions.
- [x] 2.2 Route product variant relation-manager images to R2.
- [x] 2.3 Route category, voucher, and slider images to R2.
- [x] 2.4 Route blog post, CMS page, and admin customer profile images to R2.
- [x] 2.5 Route website settings assets (logo, login logo, favicon, and social image) to R2.
- [ ] 2.6 Keep product Excel import staging temporary and document/test that it is not persisted as R2 media.

## 3. Frontend and service uploads

- [ ] 3.1 Route customer account profile uploads to R2 while preserving the profile media association and stored URL behavior.
- [ ] 3.2 Route product review image uploads on create and update to R2 while preserving validation and the `images` collection.
- [ ] 3.3 Route downloaded product images from public URLs to R2 with existing filename and collection behavior.

## 4. Regression coverage and compatibility

- [ ] 4.1 Add tests covering each persistent upload family and asserting the configured R2 disk is used.
- [ ] 4.2 Add tests for temporary Livewire/Excel lifecycle and for unchanged generated operational file dispositions.
- [ ] 4.3 Add failure-path tests proving an unavailable R2 write does not leave an orphaned media association.
- [ ] 4.4 Verify existing media retrieval, URLs, collections, conversions, and validation remain compatible.

## 5. Validation and traceability

- [ ] 5.1 Run focused backend tests and formatter/static checks.
- [ ] 5.2 Run the relevant admin/browser upload checks against `http://localhost:81` when the environment is available.
- [ ] 5.3 Run the frontend build and OpenSpec strict validation.
- [ ] 5.4 Update Issue #86 and PR documentation with explicit feature coverage, validation evidence, risks, and deployment instructions.
