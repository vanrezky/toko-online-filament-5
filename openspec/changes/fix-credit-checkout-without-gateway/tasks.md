## 1. Internal checkout flow

- [x] 1.1 Restrict external payment creation to no internal credit checkout type, while preserving the balance ledger debit path.
- [x] 1.2 Preserve the existing successful response contract for full, installment, and balance checkout without a gateway payment response.

## 2. Regression coverage

- [x] 2.1 Add full credit-limit checkout coverage using no active gateway and verify billing, due date, cart completion, and no gateway invocation.
- [x] 2.2 Add valid installment checkout coverage using no active gateway and verify installment creation, billing state, cart completion, and no gateway invocation.
- [x] 2.3 Make the balance checkout test fail on an unexpected gateway invocation while preserving its ledger assertions.

## 3. Validation

- [x] 3.1 Run the focused checkout feature test suite.
- [x] 3.2 Run OpenSpec validation and inspect the scoped diff.
