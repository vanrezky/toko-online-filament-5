# Status Consistency Execution Plan

## Objective

Menyederhanakan dan menyatukan status transaksi/cicilan agar konsisten dengan domain toko private berbasis kredit.

## Final Status Matrix

### 1) Transaction Status (`transactions.status`)

- `packed`
- `in_transit`
- `shipped`
- `delivered`
- `picked_up`
- `completed`
- `cancelled`

Catatan: `unpaid` dihapus dari lifecycle transaksi.

### 2) Billing Status (`transactions.billing_status`)

- `not_applicable`
- `pending`
- `submitted`
- `paid`
- `failed`
- `cancelled`

Catatan: `billing_status` dipakai untuk alur billing/payroll transaksi `payment_type=full`.

### 3) Installment Status (`installments.status`)

- `active`
- `overdue`
- `completed`
- `cancelled`

### 4) Installment Payment Status (`installment_payments.status`)

- `unpaid`
- `partial`
- `paid`
- `overdue`
- `cancelled`

## Business Rules

### Checkout

- Transaction baru dibuat dengan `status=packed`.
- Jika `payment_type=full`, set `billing_status=pending`.
- Jika `payment_type=installment`, set `billing_status=not_applicable`.

### Cancel Order

- Hanya boleh cancel jika `transactions.status=packed`.
- Saat cancel:
  - `transactions.status=cancelled`
  - `transactions.billing_status=cancelled` untuk `payment_type=full`
  - `transactions.billing_status=not_applicable` untuk `payment_type=installment`
  - jika ada installment:
    - `installments.status=cancelled`
    - `installment_payments.status` yang `unpaid|partial|overdue` -> `cancelled`
    - `installment_payments.status=paid` tetap `paid`

### Non-cancellable Transaction Status

- `in_transit`
- `shipped`
- `delivered`
- `picked_up`
- `completed`

## Technical Changes

1. Update enum `TransactionStatus` untuk menghapus `unpaid`.
2. Tambahkan migration enum `transactions.billing_status` agar mencakup `cancelled`.
3. Refactor `OrderController`:
   - hapus ketergantungan `status=unpaid`
   - update guard cancel ke `status=packed`
   - update cascade cancel transaction/installment/installment_payments
4. Sinkronkan frontend order pages agar tidak lagi mengasumsikan `unpaid` transaction.
5. Sinkronkan job/query yang masih memakai `status=unpaid` pada transaction.
6. Update PRD `.docs/prd-toko-private.md` agar sejalan dengan matrix dan flow terbaru.

## Tests to Add/Adjust

1. Full payment order cancel:
   - transaction status menjadi `cancelled`
   - billing status menjadi `cancelled`
2. Installment order cancel:
   - transaction status `cancelled`
   - installment status `cancelled`
   - unpaid/partial/overdue payments menjadi `cancelled`
   - paid payments tetap `paid`
3. Cancel ditolak untuk status non-cancellable.
4. Checkout tidak menghasilkan `transactions.status=unpaid`.

## Rollout Note

- Tidak ada backward compatibility.
- Data lama akan di-clean oleh owner sebelum/selama rollout.
