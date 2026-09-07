# Proposal: Compact Product Image URL Repeater

GitHub Issue: #101

## Why

The product create and edit forms display the `image_urls` repeater with more
vertical space than necessary. This makes managing several image links less
efficient for catalog administrators.

## What Changes

Configure the existing Filament `image_urls` repeater with Filament's
`compact()` option. The change applies only to this repeater and preserves its
existing URL validation, five-item limit, add/remove actions, and ordering.

## Scope

### In scope

- Compact presentation of the product form's `image_urls` repeater.
- Verification on both product create and edit forms.

### Out of scope

- Local image upload repeater presentation.
- Data structure, validation rules, or maximum image count.
- Other admin repeaters.

## Definition of Done

- Issue #101 acceptance criteria are implemented.
- Focused automated or structural validation covers the configuration.
- OpenSpec validation passes.
- The focused diff is ready for pull request review.
