# Midtrans API Documentation Reference

**Last Updated:** April 2026
**Source:** https://docs.midtrans.com

---

## 1. Overview

Midtrans provides two integration methods:

| Method | Description | Use Case |
|--------|-------------|----------|
| **Snap** | Built-in checkout page (popup/redirect) | E-commerce checkout, quick integration |
| **Core API** | RESTful API, custom UI | POS, IoT, custom payment flow |

Project ini menggunakan **Snap API** sebagai primary integration method (lihat `.docs/gateways-prd.md`).

---

## 2. API Endpoints

### 2.1 Base URLs

| Environment | URL |
|-------------|-----|
| Sandbox | `https://app.sandbox.midtrans.com` |
| Production | `https://app.midtrans.com` |

### 2.2 Snap API - Create Transaction Token

```
POST /snap/v1/transactions
```

Mendapatkan Snap Token untuk menampilkan halaman pembayaran Snap.

### 2.3 Core API - Charge Transaction

```
POST /v2/charge
```

### 2.4 Status API - Get Transaction Status

```
GET /v2/{order_id}/status
```

### 2.5 Cancel Transaction

```
POST /v2/{order_id}/cancel
```

### 2.6 Refund

```
POST /v2/{order_id}/refund
```

---

## 3. Authentication

### 3.1 Server Key (Backend)

Digunakan untuk API request dari backend. Authorization header menggunakan **Basic Auth**:

```
Authorization: Basic base64(ServerKey + ":")
```

Contoh:
- Server Key: `SB-Mid-server-abc123cde456`
- String to encode: `SB-Mid-server-abc123cde456:`
- Base64 result: `U0ItTWlkLXNlcnZlci1hYmMxMjNjZGU0NTY6`
- Header: `Authorization: Basic U0ItTWlkLXNlcnZlci1hYmMxMjNjZGU0NTY6`

### 3.2 Client Key (Frontend)

Digunakan untuk request dari frontend (e.g., GET Card Token). Dikirim sebagai query parameter:

```
GET /v2/token?client_key=<YOUR-CLIENT-KEY>&card_cvv=123&...
```

### 3.3 Webhook Signature Verification

Verifikasi signature_key dari webhook notification:

```
signature_key = SHA512(order_id + status_code + gross_amount + server_key)
```

---

## 4. Snap Integration - PHP SDK

Project menggunakan package `midtrans/midtrans-php` (sudah di composer.json).

### 4.1 Create Snap Token (Backend)

```php
\Midtrans\Config::$serverKey = config('midtrans.server_key');
\Midtrans\Config::$isProduction = config('midtrans.is_production');
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

$params = [
    'transaction_details' => [
        'order_id' => $order->order_number,
        'gross_amount' => (int) $order->total,
    ],
    'item_details' => [
        [
            'id' => $product->id,
            'price' => (int) $product->price,
            'quantity' => $product->qty,
            'name' => $product->name,
        ],
    ],
    'customer_details' => [
        'first_name' => $customer->name,
        'email' => $customer->email,
        'phone' => $customer->phone,
    ],
];

$snapToken = \Midtrans\Snap::getSnapToken($params);
```

### 4.2 Display Snap on Frontend (JS)

```html
<script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="<CLIENT_KEY>"></script>
<script>
  snap.pay('<SNAP_TOKEN>', {
    onSuccess: function(result) { /* handle success */ },
    onPending: function(result) { /* handle pending */ },
    onError: function(result) { /* handle error */ },
    onClose: function() { /* user closed popup */ },
  });
</script>
```

Production URL: `https://app.midtrans.com/snap/snap.js`

---

## 5. Transaction Request Payload

### 5.1 Required Fields

```json
{
  "transaction_details": {
    "order_id": "ORDER-ID-123",
    "gross_amount": 100000
  }
}
```

| Field | Type | Description |
|-------|------|-------------|
| `order_id` | string (max 50 chars) | Unique order ID. Allowed: alphanumeric, dash, underscore, tilde, dot |
| `gross_amount` | integer | Total amount in IDR (no decimals) |

### 5.2 Customer Details

```json
{
  "customer_details": {
    "first_name": "Budi",
    "last_name": "Pratama",
    "email": "budi@example.com",
    "phone": "08111222333",
    "billing_address": {
      "first_name": "Budi",
      "last_name": "Pratama",
      "address": "Jl. Example No. 1",
      "city": "Jakarta",
      "postal_code": "12345",
      "country_code": "IDN"
    },
    "shipping_address": {
      "first_name": "Budi",
      "last_name": "Pratama",
      "address": "Jl. Example No. 1",
      "city": "Jakarta",
      "postal_code": "12345",
      "country_code": "IDN"
    }
  }
}
```

### 5.3 Item Details

```json
{
  "item_details": [
    {
      "id": "ITEM1",
      "price": 50000,
      "quantity": 2,
      "name": "Product Name"
    }
  ]
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | string | Yes | Item ID |
| `price` | integer | Yes | Price per unit (IDR, no decimals) |
| `quantity` | integer | Yes | Quantity |
| `name` | string | Yes | Item name (max 50 chars) |

### 5.4 Credit Card Options

```json
{
  "credit_card": {
    "secure": true,
    "save_card": true
  }
}
```

---

## 6. Payment Method Types (payment_type)

| Type | Value | Description |
|------|-------|-------------|
| Credit Card | `credit_card` | Visa, Mastercard, JCB, AMEX |
| Bank Transfer (VA) | `bank_transfer` | BCA, BNI, Permata, Mandiri Bill |
| E-Wallet | `gopay`, `shopeepay` | GoPay, ShopeePay |
| QRIS | `qris` | Generic QRIS |
| Convenience Store | `cstore` | Indomaret, Alfamart |
| Cardless Credit | `akulaku` | Akulaku |

### 6.1 Bank Transfer Specific

```json
{
  "payment_type": "bank_transfer",
  "bank_transfer": {
    "bank": "bca"
  }
}
```

Available banks: `bca`, `bni`, `permata`, `mandiri` (bill payment)

### 6.2 E-Wallet (GoPay)

```json
{
  "payment_type": "gopay",
  "gopay": {}
}
```

### 6.3 QRIS

```json
{
  "payment_type": "qris",
  "qris": {}
}
```

---

## 7. Webhook / HTTP Notification

### 7.1 Setup

Configure Payment Notification URL di Midtrans Dashboard:
**Settings > Configuration > Payment Notification URL**

### 7.2 Notification Payload (Sample - Credit Card)

```json
{
  "transaction_time": "2020-01-09 18:27:19",
  "transaction_status": "capture",
  "transaction_id": "57d5293c-e65f-4a29-95e4-5959c3fa335b",
  "status_message": "midtrans payment notification",
  "status_code": "200",
  "signature_key": "16d6f84b2fb0468e2a9cf99a8ac4e5d8...",
  "payment_type": "credit_card",
  "order_id": "ORDER-ID-123",
  "merchant_id": "G141532850",
  "masked_card": "48111111-1114",
  "gross_amount": "10000.00",
  "fraud_status": "accept",
  "eci": "05",
  "currency": "IDR",
  "channel_response_message": "Approved",
  "channel_response_code": "00",
  "card_type": "credit",
  "bank": "bni",
  "approval_code": "1578569243927"
}
```

### 7.3 Key Fields

| Field | Description |
|-------|-------------|
| `transaction_status` | Status transaksi (lihat status cycle) |
| `signature_key` | SHA512(order_id + status_code + gross_amount + server_key) |
| `payment_type` | Metode pembayaran |
| `order_id` | Order ID merchant |
| `gross_amount` | Total amount |
| `fraud_status` | `accept` / `challenge` / `deny` |

### 7.4 Transaction Status Cycle

| Status | Description |
|--------|-------------|
| `capture` | Transaksi berhasil di-charge (card payment, fraud_status=accept) |
| `settlement` | Dana sudah diterima merchant |
| `pending` | Transaksi menunggu pembayaran |
| `deny` | Transaksi ditolak |
| `cancel` | Transaksi dibatalkan |
| `expire` | Transaksi kedaluwarsa |
| `refund` | Transaksi di-refund |

### 7.5 Status Flow Diagrams

**Credit Card:**
```
authorize -> capture -> settlement
                      \-> deny
```

**Bank Transfer (VA):**
```
pending -> settlement
        \-> cancel
        \-> expire
```

**E-Wallet:**
```
pending -> settlement
        \-> deny
        \-> expire
```

### 7.6 Verification

Always verify signature_key sebelum memproses notification:

```php
$signatureKey = hash('sha512',
    $notification['order_id'] .
    $notification['status_code'] .
    $notification['gross_amount'] .
    $serverKey
);

if ($signatureKey !== $notification['signature_key']) {
    // Invalid signature - reject
}
```

---

## 8. Error Handling

### 8.1 HTTP Status Codes

| Code | Description |
|------|-------------|
| `200` | Success |
| `201` | Created (transaction registered) |
| `400` | Validation error |
| `401` | Unauthorized (invalid server key) |
| `402` | Merchant doesn't have access for payment type |
| `411` | Token ID missing/invalid/expired |
| `500` | Internal server error |

### 8.2 Error Response Format

```json
{
  "error_messages": [
    "Access denied due to unauthorized transaction"
  ],
  "status_code": "401",
  "status_message": "Access denied due to unauthorized transaction, please check client key or server key"
}
```

---

## 9. Supported Currencies

| Code | Name | Symbol |
|------|------|--------|
| IDR | Indonesian Rupiah | Rp |
| USD | US Dollar | $ |
| EUR | Euro | € |
| GBP | British Pound | £ |
| JPY | Japanese Yen | ¥ |
| SGD | Singapore Dollar | S$ |

Default currency: **IDR**

---

## 10. Configuration in Project

Midtrans config di project ini ada di:

- **Config:** `config/midtrans.php`
- **Env variables:**
  - `MIDTRANS_SERVER_KEY`
  - `MIDTRANS_CLIENT_KEY`
  - `MIDTRANS_IS_PRODUCTION` (default: false for sandbox)

### 10.1 Laravel Config Reference

```php
// config/midtrans.php
return [
    'server_key' => env('MIDTRANS_SERVER_KEY'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),
    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    'is_sanitized' => env('MIDTRANS_IS_SANITIZED', true),
    'is_3ds' => env('MIDTRANS_IS_3DS', true),
];
```

### 10.2 PHP SDK Setup (in Service Provider)

```php
Config::$serverKey = config('midtrans.server_key');
Config::$isProduction = config('midtrans.is_production');
Config::$isSanitized = config('midtrans.is_sanitized');
Config::$is3ds = config('midtrans.is_3ds');
```

---

## 11. Laravel Controller - Webhook Handler Reference

```php
public function webhook(Request $request)
{
    $notification = $request->all();

    $signatureKey = hash('sha512',
        $notification['order_id'] .
        $notification['status_code'] .
        $notification['gross_amount'] .
        config('midtrans.server_key')
    );

    if ($signatureKey !== $notification['signature_key']) {
        return response()->json(['message' => 'Invalid signature'], 403);
    }

    $order = Order::where('order_number', $notification['order_id'])->first();

    if (!$order) {
        return response()->json(['message' => 'Order not found'], 404);
    }

    $status = match ($notification['transaction_status']) {
        'capture', 'settlement' => 'success',
        'pending' => 'pending',
        'deny', 'reject' => 'failed',
        'cancel' => 'cancelled',
        'expire' => 'expired',
        'refund' => 'refunded',
        default => 'unknown',
    };

    $order->update(['payment_status' => $status]);

    return response()->json(['message' => 'OK'], 200);
}
```

**Important:** Webhook endpoint harus selalu return `200 OK` setelah berhasil diproses, agar Midtrans tidak mengirim ulang.

---

## 12. Links

- **Docs:** https://docs.midtrans.com
- **Snap Integration Guide:** https://docs.midtrans.com/docs/snap-snap-integration-guide
- **Core API Docs:** https://docs.midtrans.com/docs/custom-interface-core-api
- **Webhook Docs:** https://docs.midtrans.com/docs/https-notification-webhooks
- **API Auth:** https://docs.midtrans.com/docs/api-authorization-headers
- **Transaction Status Cycle:** https://docs.midtrans.com/docs/transaction-status-cycle
- **Error Codes:** https://docs.midtrans.com/docs/error-code-and-response-code
- **PHP SDK:** https://github.com/Midtrans/midtrans-php
- **Sandbox Dashboard:** https://dashboard.sandbox.midtrans.com
- **Production Dashboard:** https://dashboard.midtrans.com