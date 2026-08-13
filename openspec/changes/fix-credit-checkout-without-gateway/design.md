## Context

Checkout persists full credit-limit and installment transactions, including their internal billing records, before calling `PaymentGatewayService::createPayment()` for every non-balance payment type. That call rolls back the transaction when no gateway is active. Balance already bypasses the gateway and settles through `BalanceService`.

## Goals / Non-Goals

**Goals:**

- Treat full credit-limit and installment checkout as internal credit flows that do not create a gateway payment.
- Preserve existing transaction, billing, installment, voucher, shipping, flash-sale, cart, and notification behavior.
- Lock the behavior with focused regression tests using the real, unconfigured gateway service.

**Non-Goals:**

- Enable bank transfer or any external gateway.
- Change checkout request or response payloads, schema, payment providers, or the future-method UI.
- Change the legacy full-payment retry endpoint outside checkout.

## Decisions

- Extend the existing internal-payment branch in `CheckoutController` so `full` and `installment`, like `balance`, bypass `PaymentGatewayService::createPayment()`. This keeps the smallest change at the checkout orchestration boundary and leaves gateway behavior intact for the legacy retry endpoint.
- Keep balance settlement separate: balance alone calls `BalanceService::pay()` because it debits the customer ledger immediately; credit-limit types only create their existing billing/installment records.
- Return the existing internal-payment response shape, with `payment.provider` representing the internal credit flow rather than an active gateway. The frontend only starts a provider SDK for Midtrans, so no frontend behavior needs changing.
- Add full and installment regression tests without replacing `PaymentGatewayService`; the default settings have no active gateway. For balance, use a mock expectation that rejects an unexpected gateway call while retaining its ledger assertions.

## Risks / Trade-offs

- [A gateway was intentionally expected for full-credit checkout] → The issue and existing payment-choice contract identify full credit and installment as internal funding; preserve the legacy retry endpoint unchanged and scope this change to initial checkout.
- [A future payment type reaches the internal branch] → Keep the branch explicit to `full`, `installment`, and `balance`, leaving unknown types invalidated by existing request validation.
- [A regression bypasses balance ledger settlement] → Keep `BalanceService::pay()` as a distinct branch and cover it in the focused test suite.

## Migration Plan

- Deploy as an application-only change; no migration or data backfill is required.
- If an unexpected production issue occurs, restore the prior gateway call in checkout; transactions created by the corrected flow already use existing internal billing records.
