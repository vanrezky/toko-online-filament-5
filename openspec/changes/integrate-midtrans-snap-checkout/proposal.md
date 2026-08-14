## Why

Issue #30 identifies that the application contains Midtrans SDK, admin settings, a gateway adapter, webhook route, and Snap UI bootstrap, yet checkout never requests a Snap token. Customers therefore cannot use the configured Midtrans gateway, and payment status cannot be safely synchronized into the local order.

## What Changes

- Add a selectable Midtrans Snap checkout payment flow while preserving internal credit-limit, installment, and balance flows.
- Create Snap tokens on the backend from a reconciled local transaction and return only browser-safe Snap metadata.
- Make Midtrans notifications authenticated, amount-validated, idempotent, and monotonic before changing local billing or order state.
- Establish checkout-time whole-IDR rounding as the persisted financial source of truth for orders and gateway payments.
- Let customers resume an existing pending Midtrans payment from order detail, including choosing another available Midtrans channel, without creating a duplicate local order or reusing an invalid Midtrans order ID.
- Publish the existing credential form under the Filament Settings cluster and protect it with a dedicated Shield page permission plus the `is_super_user` override.
- Expose the public Midtrans notification and redirect URLs in the protected gateway settings page so administrators can configure the Midtrans dashboard without reconstructing application routes.
- Replace the currently-disabled Midtrans-related checkout gateway presentation with an available Midtrans option only when it is active and configured.

## Capabilities

### New Capabilities

- `midtrans-snap-checkout-payment`: Create and resume a secure Midtrans Snap payment from a local checkout transaction.
- `midtrans-payment-notification-sync`: Verify and apply Midtrans payment notifications safely to local transactions.
- `midtrans-gateway-settings-administration`: Manage Midtrans credentials through the protected Filament Settings cluster page.
- `checkout-idr-rounding-auditability`: Persist and display the same whole-IDR amounts used to validate and charge an order.

### Modified Capabilities

- `checkout-payment-method-grouping`: Present the configured Midtrans payment choice as usable while retaining unrelated future gateway options as unavailable.

## Impact

- Checkout and order-payment endpoints, `MidtransGateway`, `PaymentGatewayService`, and payment-webhook processing.
- Checkout and order-detail Vue pages plus frontend translations and Snap JavaScript loading.
- Gateway setting/admin validation and deployment documentation for Midtrans sandbox/production Notification URL.
- Focused PHP feature/unit tests and frontend tests/build; no database-schema change or new gateway SDK dependency is expected.
