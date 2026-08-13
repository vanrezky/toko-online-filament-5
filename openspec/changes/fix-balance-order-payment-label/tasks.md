## 1. Payment-label resolution

- [x] 1.1 Add localized store-balance display labels in Indonesian and English.
- [x] 1.2 Resolve balance, installment, and full-credit labels from persisted order payment data in the order detail.

## 2. Regression coverage and validation

- [x] 2.1 Add focused frontend tests for balance payment type, saldo payment method fallback, installment, and full-credit fallback labels.
- [x] 2.2 Run frontend tests, production build, OpenSpec validation, and diff checks.
- [x] 2.3 Preserve recognized payment-type precedence over conflicting legacy payment-method data.

## 3. Admin transaction detail

- [x] 3.1 Display the persisted payment type as a read-only value in the Filament transaction view and edit form, including balance orders.
- [x] 3.2 Add focused regression coverage for the admin transaction payment-type display.
- [x] 3.3 Re-run relevant backend and frontend validation.
