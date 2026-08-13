## Context

Transactions own one optional installment record. The existing installment resource already exposes its financial terms, progress, and payment schedule, while transaction detail lacks that context.

## Goals / Non-Goals

**Goals:** Show a compact, read-only installment summary in transaction detail and link to the canonical installment detail.

**Non-Goals:** Duplicate the payment schedule, alter installment data, or add payment actions to the transaction page.

## Decisions

- Use an installment-only infolist section with the existing relation fields to avoid new persistence or calculations.
- Use a URL action to the existing Installment resource detail; retain payment-schedule management there.
- Test the infolist schema and relation data rather than coupling the test to rendered HTML.

## Risks / Trade-offs

- [A legacy installment transaction has no relation] → hide the section unless the relation exists.
