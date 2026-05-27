# Product Requirements Document (PRD)

## Toko Online Private — Sistem Katalog & Cicilan Anggota

**Version:** 1.0  
**Status:** Planned  
**Last Updated:** May 2026  
**Branch:** `toko-online-private`  
**Platform:** Laravel 11 + Vue 3 (Inertia.js) + Filament 3  
**Path Implement:**

- Backend: `app/` (Models, Services, Controllers, Filament)
- Frontend: `resources/js/frontend/`
- Database: `database/migrations/`, `database/settings/`
- Reference Schema: `database/schema/mysql-schema.sql`

---

## 1. Executive Summary

Toko Online Private adalah modul transformasi dari platform e-commerce umum (UMKM) menjadi **toko khusus anggota** (sekolah/instansi) dengan sistem **kredit & cicilan otomatis**. Hanya anggota terdaftar (Guru, Staf, Siswa) yang dapat mengakses katalog, melihat harga, dan melakukan transaksi. Sistem mendukung pembayaran hybrid: potong gaji otomatis oleh admin DAN pembayaran manual oleh anggota.

**Key Features:**

- Partial Private Access: Katalog bisa dilihat publik, harga & transaksi hanya untuk anggota
- Sistem Cicilan: Pilihan tenor dengan bunga/fee yang dikonfigurasi admin
- Credit Limit per Anggota: Batas kredit yang dapat diatur per individu
- Hybrid Payment: Potong gaji (payroll deduction) + bayar manual
- Modul Piutang: Laporan cicilan aktif per anggota untuk pemantauan saldo
- Ekspor Payroll: Download data potongan gaji ke Excel/Spreadsheet

---

## 2. Goals & Objectives

### Primary Goals

1. **Kontrol Akses** — Katalog dan transaksi hanya untuk anggota terdaftar
2. **Transparansi Cicilan** — Anggota bisa simulasi angsuran sebelum checkout
3. **Manajemen Piutang** — Admin bisa memantau saldo piutang per anggota
4. **Integrasi Payroll** — Data cicilan bisa diekspor untuk potongan gaji otomatis
5. **Fleksibilitas Kredit** — Limit kredit per anggota, default per level

### Success Metrics

- Anggota dapat melihat simulasi cicilan < 3 detik
- Admin dapat mengelola credit limit < 1 menit per anggota
- Ekspor payroll < 5 detik per bulan
- Cicilan berjalan terpantau real-time di dashboard admin
- 0 transaksi melebihi credit limit anggota

---

## 3. User Roles & Access Matrix

### 3.1 Customer Levels (Model Baru: `CustomerLevel`)

| Level    | Label              | Default Credit Limit | Description                  |
| -------- | ------------------ | -------------------- | ---------------------------- |
| `guru`   | Guru/Pengajar     | Rp 5.000.000         | Tenaga pendidik              |
| `staf`   | Staf/Karyawan     | Rp 3.000.000         | Tenaga kependidikan          |
| `siswa`  | Siswa/Murid       | Rp 1.000.000         | Peserta didik                |
| `custom` | Kustom             | Rp 0                 | Credit limit diatur manual  |

> **Catatan:** Model `CustomerLevel` adalah tabel BARU, terpisah dari `Reseller`. Default credit limit diatur per level, tapi bisa dioverride per individu di `Customer.credit_limit`.

### 3.2 Access Matrix

| Fitur                    | Publik (Guest) | Anggota (Login) | Admin |
| ------------------------ | --------------- | ---------------- | ----- |
| Lihat katalog produk     | ✅ (tanpa harga) | ✅ (dengan harga) | ✅    |
| Lihat harga & detail     | ❌              | ✅               | ✅    |
| Tambah ke keranjang      | ❌              | ✅               | ✅    |
| Checkout & cicilan       | ❌              | ✅               | ✅    |
| Riwayat transaksi        | ❌              | ✅ (milik sendiri)| ✅ (semua) |
| Lihat cicilan aktif      | ❌              | ✅ (milik sendiri)| ✅ (semua) |
| Manajemen anggota        | ❌              | ❌               | ✅    |
| Atur credit limit         | ❌              | ❌               | ✅    |
| Ekspor payroll           | ❌              | ❌               | ✅    |
| Validasi pesanan         | ❌              | ❌               | ✅    |

---

## 4. Core Features

### 4.0 Status Matrix (Source of Truth)

Status harus konsisten dan tidak overlap antar domain.

- `transactions.status`: `packed`, `in_transit`, `shipped`, `delivered`, `picked_up`, `completed`, `cancelled`
- `transactions.billing_status`: `not_applicable`, `pending`, `submitted`, `paid`, `failed`, `cancelled`
- `installments.status`: `active`, `overdue`, `completed`, `cancelled`
- `installment_payments.status`: `unpaid`, `partial`, `paid`, `overdue`, `cancelled`

Aturan cancel order:

- Hanya order `packed` yang boleh dibatalkan.
- Saat cancel full-payment: `transactions.status=cancelled` dan `transactions.billing_status=cancelled`.
- Saat cancel installment: `transactions.status=cancelled`, `installments.status=cancelled`, dan `installment_payments` unpaid/partial/overdue menjadi `cancelled`.

### 4.1 Partial Private Access

**Katalog Publik (tanpa login):**

- Produk bisa dilihat: gambar, nama, kategori, deskripsi
- Harga **disembunyikan** → tampil "Login untuk lihat harga"
- Button "Tambah ke Keranjang" → redirect ke login
- Kategori dan search tetap bisa digunakan

**Katalog Anggota (setelah login):**

- Harga terlihat full (harga normal, harga cicilan)
- Bisa tambah ke keranjang
- Bisa pilih metode pengiriman (Ambil di Toko / Delivery)
- Bisa lihat simulasi cicilan

**Implementasi:**

- Middleware `EnsureCustomerIsMember` untuk route yang memerlukan login
- Blade/Inertia: conditional rendering berdasarkan `auth()` check
- API: return `price` field hanya jika user authenticated

### 4.2 E-Katalog & Keranjang

**Fitur yang sudah ada (perlu modifikasi):**

- ✅ Product listing, search, filter kategori — sudah ada
- ✅ Cart management (add, remove, update quantity) — sudah ada
- ✅ Cart voucher integration — sudah ada
- 🔧 Penyesuaian: Tampilkan harga cicilan di product card dan product detail
- 🔧 Penyesuaian: Sembunyikan harga untuk guest

**Pengiriman — 2 opsi:**

| Metode          | Kode         | Deskripsi                                      |
| --------------- | ------------ | ---------------------------------------------- |
| Ambil di Toko   | `PICKUP`     | Anggota ambil pesanan di toko, tanpa ongkir    |
| Kurir Internal  | `KURIR_TOKO` | Diantar oleh kurir internal toko, biaya tetap   |

> **Catatan:** Enum `CourierCode::PICKUP` dan `CourierCode::KURIR_TOKO` sudah ada di codebase. Perlu disesuaikan UI checkout.

### 4.3 Sistem Cicilan Otomatis

#### 4.3.1 Installment Plans (Master Tenor)

Admin mengkonfigurasi tenor cicilan yang tersedia:

| Tenor | Fee/Bunga | Contoh: Rp300.000                        |
| ----- | --------- | ----------------------------------------- |
| 3     | 2%        | Rp300.000 × 1.02 / 3 = Rp102.000/bulan  |
| 6     | 5%        | Rp300.000 × 1.05 / 6 = Rp52.500/bulan   |
| 12    | 10%       | Rp300.000 × 1.10 / 12 = Rp27.500/bulan  |
| 24    | 20%       | Rp300.000 × 1.20 / 24 = Rp15.000/bulan  |

> Bunga/fee dapat diatur admin per tenor. Admin bisa tambah/hapus tenor.

#### 4.3.2 Simulasi Cicilan (Frontend)

Di halaman product detail dan checkout, anggota bisa melihat simulasi cicilan:

```
┌──────────────────────────────────────────────┐
│ 💳 Simulasi Cicilan                         │
├──────────────────────────────────────────────┤
│                                              │
│  Harga Produk:  Rp 300.000                   │
│                                              │
│  ┌─────────┐ ┌─────────┐ ┌──────────┐      │
│  │3 bulan  │ │6 bulan  │ │12 bulan  │      │
│  │2% fee   │ │5% fee   │ │10% fee   │      │
│  └─────────┘ └─────────┘ └──────────┘      │
│                                              │
│  ┌─ Ringkasan ─────────────────────────────┐ │
│  │ Harga Produk:     Rp 300.000            │ │
│  │ Fee Cicilan (5%): Rp 15.000            │ │
│  │ Total Cicilan:    Rp 315.000            │ │
│  │ Angsuran/bulan:   Rp 52.500             │ │
│  └─────────────────────────────────────────┘ │
│                                              │
│  ⚠️ Limit tersisa: Rp 2.500.000             │
└──────────────────────────────────────────────┘
```

#### 4.3.3 Checkout dengan Cicilan

Flow checkout:

```
1. Anggota pilih produk → tambah ke keranjang
2. Buka halaman checkout
3. Pilih metode pengiriman (Ambil di Toko / delivery)
4. Pilih metode pembayaran:
   a. Bayar Penuh (lunas) → lanjut ke payment gateway
   b. Cicilan → pilih tenor → simulasi angsuran → konfirmasi
5. Cek credit limit:
   ├── ✅ Mencukupi → Proses pesanan
   └── ❌ Tidak mencukupi → Show error "Limit kredit tidak mencukupi"
6. Buat transaksi + jadwal cicilan
7. Anggota lihat jadwal cicilan di halaman "Riwayat Transaksi"
```

#### 4.3.4 Installment Schedule

Setelah checkout dengan cicilan, sistem membuat jadwal:

```
┌─────────────────────────────────────────────────────────────┐
│ Jadwal Cicilan — Pesanan #TRX-20260515-001                 │
├───────┬────────────┬───────────┬───────────┬────────────────┤
│ Bulan │ Tgl Jatuh Tempo │ Angsuran │ Status    │ Pembayaran     │
├───────┬────────────┬───────────┬───────────┬────────────────┤
│ 1/6   │ 15 Jun 2026│ Rp 52.500 │ ⏳ Belum   │ -              │
│ 2/6   │ 15 Jul 2026│ Rp 52.500 │ ⏳ Belum   │ -              │
│ 3/6   │ 15 Aug 2026│ Rp 52.500 │ ⏳ Belum   │ -              │
│ 4/6   │ 15 Sep 2026│ Rp 52.500 │ ⏳ Belum   │ -              │
│ 5/6   │ 15 Oct 2026│ Rp 52.500 │ ⏳ Belum   │ -              │
│ 6/6   │ 15 Nov 2026│ Rp 52.500 │ ⏳ Belum   │ -              │
├───────┴────────────┴───────────┴───────────┴────────────────┤
│ Total: Rp 315.000 (Rp 300.000 + fee Rp 15.000)            │
└─────────────────────────────────────────────────────────────┘
```

### 4.4 Credit Limit System

#### 4.4.1 Hierarki Limit

```
CustomerLevel (default) → Customer (individual override) → Aktif
     ↓                        ↓
  Rp 5.000.000           Rp 7.000.000 (override)
       ↓                      ↓
       └──── Saldo Terpakai ──┘
              ↓
    Sisa Limit = credit_limit - total_outstanding
```

#### 4.4.2 Logic Credit Check

```php
// Pada saat checkout cicilan
$totalOutstanding = $customer->getOutstandingBalance(); // Sum semua cicilan aktif
$remainingLimit = $customer->credit_limit - $totalOutstanding;

if ($installmentTotal > $remainingLimit) {
    // Reject: "Limit kredit tidak mencukupi"
}

// Pada saat checkout bayar penuh
// Tidak menggunakan limit kredit, langsung bayar lunas
```

### 4.5 Hybrid Payment (Potong Gaji + Bayar Manual)

#### 4.5.1 Potong Gaji (Payroll Deduction)

- Admin menandai cicilan sebagai "potong gaji"
- Setiap bulan, admin generate daftar potongan per anggota
- Export ke Excel/Spreadsheet untuk diserahkan ke bagian keuangan
- Setelah potongan terkonfirmasi, admin update status cicilan → "Lunas"

#### 4.5.2 Bayar Manual

- Anggota bisa membayar cicilan langsung ke toko
- Admin mencatat pembayaran manual
- Status cicilan diupdate → "Lunas"

#### 4.5.3 Flow Pembayaran Cicilan

```
Cicilan jatuh tempo
      ↓
┌──────────────────────────────────┐
│ Metode Pembayaran                │
├──────────────────────────────────┤
│ A. Potong Gaji (default)         │
│    → Admin tandai → Export payroll│
│    → Keuangan potong → Konfirmasi│
│    → Status: Lunas               │
├──────────────────────────────────┤
│ B. Bayar Manual                   │
│    → Anggota bayar ke toko       │
│    → Admin verifikasi → Update   │
│    → Status: Lunas               │
└──────────────────────────────────┘
```

### 4.6 Riwayat Transaksi Anggota

Halaman `/orders` yang dimodifikasi:

**Tab 1 — Pesanan:**

| Kolom         | Keterangan                          |
| ------------- | ----------------------------------- |
| No. Pesanan   | UUID transaksi                      |
| Tanggal       | Tanggal transaksi                   |
| Total         | Total pembayaran                    |
| Metode        | Tunai / Cicilan                     |
| Status        | Diproses / Dikirim / Selesai / Dibatalkan |
| Aksi          | Lihat Detail                        |

**Tab 2 — Cicilan Aktif:**

| Kolom             | Keterangan                              |
| ----------------- | --------------------------------------- |
| No. Pesanan       | UUID transaksi                         |
| Produk            | Nama produk                             |
| Total Cicilan     | Total nominal cicilan + fee             |
| Angsuran/bulan    | Nominal angsuran per bulan              |
| Bulan Dibayar     | 3 dari 6 bulan                          |
| Status            | Aktif / Lunas / Terlambat               |
| Aksi              | Lihat Jadwal Detail                     |

### 4.7 Admin — Manajemen Anggota

Filament Resource untuk `Customer` dengan tambahan:

**Kolom Baru di Tabel Customer:**

| Kolom             | Tipe          | Keterangan                              |
| ----------------- | ------------- | --------------------------------------- |
| `customer_level_id` | FK           | Level anggota (Guru/Staf/Siswa/Custom) |
| `credit_limit`    | decimal(12,2)  | Limit kredit individual (override default) |
| `credit_used`     | decimal(12,2)  | Total cicilan aktif (computed/cached)  |
| `credit_remaining`| decimal(12,2)  | Sisa limit (computed accessor)         |

**Filament Form — Customer:**

```
┌─────────────────────────────────────────────┐
│ Data Anggota                                │
├─────────────────────────────────────────────┤
│ Nama Depan:     [__________]                │
│ Nama Belakang:  [__________]                │
│ Email:          [__________]                │
│ Telepon:        [__________]                │
│ Level:          [Guru ▼]                    │
│ Status Aktif:   [✓]                         │
│                                             │
│ ── Pengaturan Kredit ──                    │
│ Default Limit:  Rp 5.000.000 (dari level) │
│ Custom Limit:   [Rp 7.000.000]             │
│ Kredit Terpakai: Rp 2.500.000             │
│ Sisa Limit:      Rp 4.500.000             │
│                                             │
│ [💾 Simpan]                                │
└─────────────────────────────────────────────┘
```

### 4.8 Admin — Modul Piutang & Cicilan

**Filament Resource — Installment:**

- Daftar semua cicilan aktif
- Filter per anggota, per level, per status
- Detail jadwal cicilan per transaksi
- Aksi: Tandai "Potong Gaji", "Bayar Manual", "Lunas"

**Filament Page — Piutang Dashboard:**

- Total piutang seluruh anggota
- Piutang per level (Guru/Staf/Siswa)
- Anggota dengan piutang tertinggi
- Cicilan yang akan jatuh tempo bulan ini
- Cicilan yang terlambat

### 4.9 Admin — Ekspor Laporan Payroll

**Fitur Export:**

- Ekspor data cicilan bulanan ke Excel/Spreadsheet
- Format siap untuk potongan gaji
- Kolom: NIP/Nama, Level, Total Potongan, Bulan
- Filter: Per bulan, per level, per anggota

**Contoh Format Export:**

| NIP    | Nama             | Level | Total Potongan | Bulan    |
| ------ | ---------------- | ----- | -------------- | -------- |
| 198501 | Budi Santoso     | Guru  | Rp 52.500      | Jun 2026 |
| 199002 | Siti Rahayu      | Staf  | Rp 27.500      | Jun 2026 |
| 200503 | Ahmad Fadillah   | Siswa | Rp 15.000      | Jun 2026 |

---

## 5. Database Schema

### 5.1 New Table — `customer_levels`

**Purpose:** Master level anggota (Guru, Staf, Siswa, Custom)

```php
Schema::create('customer_levels', function (Blueprint $table) {
    $table->id();
    $table->string('name');                              // "Guru", "Staf", "Siswa"
    $table->string('slug')->unique();                   // "guru", "staf", "siswa"
    $table->text('description')->nullable();
    $table->decimal('default_credit_limit', 12, 2)->default(0); // Default limit kredit
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();
});
```

### 5.2 New Table — `installment_plans`

**Purpose:** Master tenor cicilan (3 bulan, 6 bulan, dst)

```php
Schema::create('installment_plans', function (Blueprint $table) {
    $table->id();
    $table->unsignedInteger('tenor');                   // Jumlah bulan: 3, 6, 12, 24
    $table->decimal('fee_percentage', 5, 2)->default(0); // Fee/bunga dalam persen: 2.00, 5.00, 10.00
    $table->text('description')->nullable();            // "Cicilan 3 bulan dengan fee 2%"
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();

    $table->unique('tenor');
});
```

### 5.3 New Table — `installments`

**Purpose:** Record cicilan per transaksi

```php
Schema::create('installments', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
    $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
    $table->foreignId('installment_plan_id')->constrained('installment_plans');
    $table->decimal('principal_amount', 12, 2);         // Harga pokok produk
    $table->decimal('fee_amount', 12, 2);                // Total fee cicilan
    $table->decimal('total_amount', 12, 2);              // Total = principal + fee
    $table->decimal('monthly_amount', 12, 2);            // Angsuran per bulan
    $table->unsignedInteger('tenor');                     // Jumlah bulan cicilan
    $table->decimal('paid_amount', 12, 2)->default(0);   // Total yang sudah dibayar
    $table->unsignedInteger('paid_installments')->default(0); // Bulan sudah dibayar
    $table->enum('status', [
        'active',      // Cicilan berjalan
        'completed',   // Cicilan lunas
        'overdue',     // Ada yang terlambat
        'defaulted',   // Wanprestasi
    ])->default('active');
    $table->date('start_date');                          // Tanggal mulai cicilan
    $table->date('expected_end_date');                   // Tanggal selesai cicilan
    $table->timestamps();
    $table->softDeletes();

    $table->index('customer_id');
    $table->index('status');
    $table->index('transaction_id');
});
```

### 5.4 New Table — `installment_payments`

**Purpose:** Jadwal pembayaran cicilan per bulan

```php
Schema::create('installment_payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('installment_id')->constrained('installments')->cascadeOnDelete();
    $table->unsignedInteger('installment_number');      // Angsuran ke-1, ke-2, dst
    $table->decimal('amount', 12, 2);                   // Nominal angsuran
    $table->date('due_date');                            // Tanggal jatuh tempo
    $table->decimal('paid_amount', 12, 2)->default(0);  // Nominal yang dibayar
    $table->date('paid_date')->nullable();               // Tanggal pembayaran
    $table->enum('payment_method', [
        'payroll_deduction',  // Potong gaji
        'manual',             // Bayar manual ke toko
        'transfer',           // Transfer bank
    ])->nullable();
    $table->enum('status', [
        'unpaid',      // Belum dibayar
        'partial',     // Dibayar sebagian
        'paid',        // Lunas
        'overdue',     // Terlambat
    ])->default('unpaid');
    $table->text('notes')->nullable();                   // Catatan admin
    $table->timestamps();

    $table->index('installment_id');
    $table->index('status');
    $table->index('due_date');
});
```

### 5.5 Modify — `customers` table (Add columns)

```php
// Migration: add_credit_columns_to_customers_table
Schema::table('customers', function (Blueprint $table) {
    $table->foreignId('customer_level_id')->nullable()->after('reseller_id')
        ->constrained('customer_levels')->nullOnDelete();
    $table->decimal('credit_limit', 12, 2)->nullable()->after('balance')
        ->comment('Override default credit_limit dari level, NULL = pakai default');
    $table->boolean('is_banned')->default(false)->after('is_active')
        ->comment('Ban anggota jika ada pelanggaran');
});
```

**Logic:**

- Jika `credit_limit` tidak NULL → pakai nilai individual
- Jika `credit_limit` NULL → pakai `customerLevel.default_credit_limit`
- Accessor: `Customer::getEffectiveCreditLimitAttribute()`

### 5.6 Modify — `transactions` table (Add columns)

```php
// Migration: add_installment_columns_to_transactions_table
Schema::table('transactions', function (Blueprint $table) {
    $table->enum('payment_type', ['full', 'installment'])->default('full')
        ->after('payment_method')
        ->comment('full = bayar lunas, installment = cicilan');
    $table->foreignId('installment_plan_id')->nullable()->after('payment_type')
        ->constrained('installment_plans')->nullOnDelete();
});
```

### 5.7 Existing Tables — No Changes Required

| Tabel             | Status      | Keterangan                |
| ----------------- | ----------- | ------------------------- |
| `products`        | ✅ Existing | Tidak ada perubahan       |
| `categories`      | ✅ Existing | Tidak ada perubahan       |
| `carts`           | ✅ Existing | Tidak ada perubahan       |
| `cart_items`      | ✅ Existing | Tidak ada perubahan       |
| `transactions`    | 🔧 Modify  | Tambah `payment_type`, `installment_plan_id` |
| `transcation_products` | ✅ Existing | Tidak ada perubahan       |
| `customers`       | 🔧 Modify  | Tambah `customer_level_id`, `credit_limit` |
| `balances`        | ✅ Existing | Bisa dipakai untuk track pembayaran |
| `couriers`        | ✅ Existing | `PICKUP` dan `KURIR_TOKO` sudah ada |

---

## 6. Models

### 6.1 New Model — `CustomerLevel`

```php
// app/Models/CustomerLevel.php

class CustomerLevel extends Model
{
    use HasFactory, HasModelTrait;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'default_credit_limit',
        'is_active',
    ];

    protected $casts = [
        'default_credit_limit' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
```

### 6.2 New Model — `InstallmentPlan`

```php
// app/Models/InstallmentPlan.php

class InstallmentPlan extends Model
{
    use HasFactory, HasModelTrait;

    protected $fillable = [
        'tenor',
        'fee_percentage',
        'description',
        'is_active',
    ];

    protected $casts = [
        'fee_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function calculateTotal(float $principalAmount): float
    {
        return $principalAmount * (1 + ($this->fee_percentage / 100));
    }

    public function calculateMonthly(float $principalAmount): float
    {
        return $this->calculateTotal($principalAmount) / $this->tenor;
    }
}
```

### 6.3 New Model — `Installment`

```php
// app/Models/Installment.php

class Installment extends Model
{
    use HasFactory, HasModelTrait, HasUuidsTrait;

    protected $fillable = [
        'uuid',
        'transaction_id',
        'customer_id',
        'installment_plan_id',
        'principal_amount',
        'fee_amount',
        'total_amount',
        'monthly_amount',
        'tenor',
        'paid_amount',
        'paid_installments',
        'status',
        'start_date',
        'expected_end_date',
    ];

    protected $casts = [
        'principal_amount' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'monthly_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'start_date' => 'date',
        'expected_end_date' => 'date',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function installmentPlan(): BelongsTo
    {
        return $this->belongsTo(InstallmentPlan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InstallmentPayment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function getRemainingAmountAttribute(): float
    {
        return $this->total_amount - $this->paid_amount;
    }

    public function getRemainingInstallmentsAttribute(): int
    {
        return $this->tenor - $this->paid_installments;
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->payments()
            ->where('status', 'overdue')
            ->exists();
    }
}
```

### 6.4 New Model — `InstallmentPayment`

```php
// app/Models/InstallmentPayment.php

class InstallmentPayment extends Model
{
    protected $fillable = [
        'installment_id',
        'installment_number',
        'amount',
        'due_date',
        'paid_amount',
        'paid_date',
        'payment_method',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_date' => 'date',
    ];

    public function installment(): BelongsTo
    {
        return $this->belongsTo(Installment::class);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('status', 'unpaid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
            ->orWhere(function ($q) {
                $q->where('status', 'unpaid')
                  ->where('due_date', '<', now());
            });
    }

    public function scopeDueThisMonth($query)
    {
        return $query->whereYear('due_date', now()->year)
            ->whereMonth('due_date', now()->month);
    }

    public function markAsPaid(float $paidAmount, string $method, ?string $notes = null): void
    {
        $this->update([
            'paid_amount' => $paidAmount,
            'paid_date' => now(),
            'payment_method' => $method,
            'status' => 'paid',
            'notes' => $notes,
        ]);

        $installment = $this->installment;
        $installment->increment('paid_amount', $paidAmount);
        $installment->increment('paid_installments');

        if ($installment->paid_installments >= $installment->tenor) {
            $installment->update(['status' => 'completed']);
        }
    }
}
```

### 6.5 Modify — `Customer` Model

```php
// Add to existing Customer model

// New relationship
public function customerLevel(): BelongsTo
{
    return $this->belongsTo(CustomerLevel::class);
}

// New relationship
public function installments(): HasMany
{
    return $this->hasMany(Installment::class);
}

// Accessor: effective credit limit (individual override or default from level)
public function getEffectiveCreditLimitAttribute(): float
{
    return $this->credit_limit ?? $this->customerLevel?->default_credit_limit ?? 0;
}

// Accessor: outstanding balance (total cicilan aktif)
public function getOutstandingBalanceAttribute(): float
{
    return $this->installments()
        ->where('status', 'active')
        ->sum('total_amount') -
        $this->installments()
        ->where('status', 'active')
        ->sum('paid_amount');
}

// Accessor: remaining credit limit
public function getRemainingCreditLimitAttribute(): float
{
    return $this->effective_credit_limit - $this->outstanding_balance;
}

// Check if customer can create installment
public function canCreateInstallment(float $amount): bool
{
    return $this->remaining_credit_limit >= $amount;
}

// Scope: member with level
public function scopeWithLevel($query, $levelId)
{
    return $query->where('customer_level_id', $levelId);
}
```

---

## 7. Backend Implementation

### 7.1 Services

```
📁 app/Services/
├── InstallmentService.php           (new) — Logic cicilan
├── CreditLimitService.php          (new) — Validasi & kalkulasi limit kredit
├── PayrollExportService.php        (new) — Export data potongan gaji
└── Gateways/
    └── (existing — no changes)
```

#### InstallmentService

```php
class InstallmentService
{
    public function createInstallment(Transaction $transaction, InstallmentPlan $plan): Installment;
    public function generatePaymentSchedule(Installment $installment): Collection;
    public function processPayment(InstallmentPayment $payment, float $amount, string $method): void;
    public function markAsPayrollDeduction(InstallmentPayment $payment): void;
    public function markOverduePayments(): int;
    public function getCustomerInstallments(Customer $customer): Collection;
    public function getInstallmentSchedule(Installment $installment): Collection;
}
```

#### CreditLimitService

```php
class CreditLimitService
{
    public function getEffectiveLimit(Customer $customer): float;
    public function getOutstandingBalance(Customer $customer): float;
    public function getRemainingLimit(Customer $customer): float;
    public function canCreateInstallment(Customer $customer, float $amount): bool;
    public function validateCheckout(Customer $customer, float $totalAmount, string $paymentType): bool;
}
```

#### PayrollExportService

```php
class PayrollExportService
{
    public function getMonthlyDeductions(int $month, int $year, ?int $customerLevelId = null): Collection;
    public function exportToExcel(int $month, int $year, ?int $customerLevelId = null): \Symfony\Component\HttpFoundation\StreamedResponse;
    public function getPayrollSummary(int $month, int $year): array;
}
```

### 7.2 Controllers

```
📁 app/Http/Controllers/
├── Frontend/
│   ├── HomeController.php          (modify — conditional price display)
│   ├── CatalogController.php       (modify — conditional price display)
│   ├── CheckoutController.php      (modify — installment & credit check)
│   └── InstallmentController.php   (new — installment schedule page)
└── Api/
    └── InstallmentController.php   (new — installment simulation API)
```

### 7.3 Form Requests

```
📁 app/Http/Requests/
├── StoreInstallmentRequest.php      (new)
├── ProcessInstallmentPaymentRequest.php (new)
└── UpdateCreditLimitRequest.php     (new)
```

### 7.4 API Endpoints

| Endpoint                                    | Method | Description                         | Auth  |
| ------------------------------------------- | ------ | ----------------------------------- | ----- |
| `/api/installment/simulate`                 | POST   | Simulasi cicilan berdasarkan jumlah | Yes   |
| `/api/installment/plans`                    | GET    | Daftar tenor cicilan aktif          | Yes   |
| `/api/customer/credit-limit`                | GET    | Cek sisa limit kredit anggota       | Yes   |
| `/checkout/installment`                     | POST   | Proses checkout cicilan             | Yes   |
| `/installments/{uuid}`                      | GET    | Detail jadwal cicilan               | Yes   |
| `/installments/{uuid}/schedule`             | GET    | Jadwal pembayaran cicilan           | Yes   |

### 7.5 Frontend Routes

| Route                       | Method | Description                              | Auth  |
| --------------------------- | ------ | ---------------------------------------- | ----- |
| `/`                         | GET    | Homepage (partial — harga hidden)       | Guest |
| `/catalog`                  | GET    | Katalog produk (partial — harga hidden)  | Guest |
| `/products/{slug}`          | GET    | Detail produk (partial — harga hidden)   | Guest |
| `/cart`                     | GET    | Keranjang belanja                        | Yes   |
| `/checkout`                 | GET    | Halaman checkout                         | Yes   |
| `/orders`                   | GET    | Riwayat pesanan                          | Yes   |
| `/orders/{uuid}`            | GET    | Detail pesanan                            | Yes   |
| `/installments`             | GET    | Daftar cicilan anggota                   | Yes   |
| `/installments/{uuid}`      | GET    | Detail jadwal cicilan                    | Yes   |

### 7.6 Middleware

```php
// app/Http/Middleware/EnsureCustomerIsMember.php
// Memastikan user adalah customer yang terautentikasi
// Guest tetap bisa akses katalog (tanpa harga)
// Hanya anggota terautentikasi yang bisa lihat harga & transaksi
```

---

## 8. Filament Admin Implementation

### 8.1 New Resources

```
📁 app/Filament/Resources/
├── CustomerLevelResource.php       (new) — CRUD level anggota
├── InstallmentPlanResource.php     (new) — CRUD tenor cicilan
├── InstallmentResource.php         (new) — View/manage cicilan
├── InstallmentPaymentResource.php (new) — Kelola pembayaran cicilan
└── CustomerResource.php            (modify) — Tambah credit limit, level
```

### 8.2 New Pages

```
📁 app/Filament/Pages/
└── PayrollExportPage.php           (new) — Ekspor data payroll
```

### 8.3 Filament Widgets (Dashboard)

```
📁 app/Filament/Widgets/
├── OutstandingDebtWidget.php       (new) — Total piutang
├── OverdueInstallmentsWidget.php   (new) — Cicilan terlambat
├── DueThisMonthWidget.php         (new) — Jatuh tempo bulan ini
└── CreditUtilizationWidget.php    (new) — Pemanfaatan kredit per level
```

### 8.4 CustomerLevelResource

**Table Columns:**

| Kolom               | Tipe     | Searchable | Sortable |
| ------------------- | -------- | ---------- | -------- |
| name                | Text     | ✅         | ✅       |
| slug                | Text     | ✅         | ✅       |
| default_credit_limit| Money   | ✅         | ✅       |
| is_active           | Boolean  | ✅         | ✅       |
| customers_count     | Number   | -          | ✅       |

**Form Fields:**

- name (text, required)
- slug (text, auto-generated from name)
- description (textarea)
- default_credit_limit (currency input, default 0)
- is_active (toggle)

### 8.5 InstallmentPlanResource

**Table Columns:**

| Kolom           | Tipe    | Searchable | Sortable |
| --------------- | ------- | ---------- | -------- |
| tenor           | Number  | ✅         | ✅       |
| fee_percentage  | Number  | ✅         | ✅       |
| is_active       | Boolean | ✅         | ✅       |
| description     | Text    | ✅         | -        |

**Form Fields:**

- tenor (number, min 1, required)
- fee_percentage (decimal, min 0, default 0)
- description (textarea)
- is_active (toggle)

### 8.6 InstallmentResource

**Table Columns:**

| Kolom              | Tipe    | Description                              |
| -------------------| ------- | ---------------------------------------- |
| uuid                | Text   | Kode cicilan                             |
| customer.name      | Text   | Nama anggota                             |
| customer_level.name| Text   | Level anggota                             |
| total_amount       | Money  | Total cicilan                            |
| monthly_amount     | Money  | Angsuran per bulan                        |
| tenor              | Number | Jumlah bulan                              |
| paid_installments  | Number | Bulan sudah dibayar                       |
| status             | Badge  | active/completed/overdue/defaulted       |

**Filter:**

- Status (active, completed, overdue, defaulted)
- Level anggota
- Bulan mulai
- Bulan selesai

**Actions per row:**

- Lihat detail cicilan + jadwal pembayaran
- Tandai "Potong Gaji" pada angsuran yang jatuh tempo
- Catat pembayaran manual
- Lihat riwayat pembayaran

### 8.7 PayrollExportPage

**Form:**

- Bulan (dropdown: Januari - Desember)
- Tahun (number input)
- Level Anggota (dropdown: Semua, Guru, Staf, Siswa)
- Preview data sebelum export

**Actions:**

- Export Excel (.xlsx)
- Export CSV
- Preview di browser

---

## 9. Frontend Implementation

### 9.1 New Pages

```
📁 resources/js/frontend/pages/
├── Installment/
│   ├── Index.vue          — Daftar cicilan anggota
│   └── Show.vue           — Detail jadwal cicilan
└── (modify existing pages)
```

### 9.2 New Components

```
📁 resources/js/frontend/components/
├── Installment/
│   ├── InstallmentCalculator.vue   — Simulasi cicilan
│   ├── InstallmentPlanSelector.vue — Pilih tenor
│   ├── InstallmentSchedule.vue     — Jadwal cicilan
│   ├── InstallmentSummary.vue      — Ringkasan cicilan di checkout
│   └── CreditLimitBadge.vue        — Badge limit tersisa
├── Product/
│   └── PriceLoginPrompt.vue        — "Login untuk lihat harga"
└── Checkout/
    └── PaymentMethodSelector.vue   — Pilih: bayar penuh / cicilan
```

### 9.3 Modified Pages

| Halaman              | Perubahan                                                     |
| -------------------- | ------------------------------------------------------------- |
| Home/Index.vue       | Conditional: sembunyikan harga untuk guest, tampilkan "Login" |
| Product/Index.vue    | Conditional: sembunyikan harga, tampilkan simulasi cicilan   |
| Product/Show.vue     | Tambah section simulasi cicilan (jika login)                 |
| Cart/Index.vue       | Tambah pilihan cicilan di summary                             |
| Checkout/Index.vue   | Tambah opsi pembayaran (penuh/cicilan), pilih kurir internal  |
| Orders/Index.vue     | Tambah tab "Cicilan Aktif"                                    |
| Orders/Show.vue      | Tambah section jadwal cicilan (jika cicilan)                  |

### 9.4 New Composables

```javascript
// resources/js/frontend/composables/useInstallment.js
export function useInstallment() {
    const simulate = async (amount, planId) => { ... }
    const getPlans = async () => { ... }
    const getSchedule = async (installmentUuid) => { ... }
    const getCreditLimit = async () => { ... }
    return { simulate, getPlans, getSchedule, getCreditLimit }
}
```

### 9.5 New API Services

```javascript
// resources/js/frontend/services/installmentService.js
export const installmentService = {
    simulate: (amount, planId) => api.post('/api/installment/simulate', { amount, plan_id: planId }),
    getPlans: () => api.get('/api/installment/plans'),
    getCreditLimit: () => api.get('/api/customer/credit-limit'),
    getSchedule: (uuid) => api.get(`/installments/${uuid}/schedule`),
}
```

---

## 10. User Flows

### 10.1 Anggota Browse Produk (Partial Private)

```
1. Guest buka homepage → lihat produk tanpa harga
2. Klik "Login untuk lihat harga" → redirect ke /login
3. Setelah login → lihat produk dengan harga + simulasi cicilan
4. Tambah ke keranjang
```

### 10.2 Checkout dengan Cicilan

```
1. Anggota buka halaman checkout
2. Pilih metode pengiriman: "Ambil di Toko" / "Delivery (Kurir Internal)"
3. Pilih metode pembayaran:
   a. "Bayar Penuh" → lanjut ke payment gateway
   b. "Cicilan" → pilih tenor → lihat simulasi
4. Jika cicilan:
   a. Sistem cek credit limit
      ├── ✅ Mencukupi → konfirmasi checkout
      └── ❌ Tidak mencukupi → error dengan info sisa limit
   b. Buat transaksi + installment + jadwal cicilan
    c. Status transaksi: "packed" (diproses)
5. Anggota diarahkan ke halaman konfirmasi + jadwal cicilan
```

### 10.3 Admin Kelola Cicilan

```
1. Admin buka Filament → Installment Resource
2. Lihat daftar cicilan aktif
3. Filter per anggota / level / status
4. Pilih cicilan → Lihat jadwal pembayaran
5. Untuk angsuran jatuh tempo:
   a. Tandai "Potong Gaji" → angsuran ditandai untuk payroll
   b. Catat pembayaran manual → angsuran diupdate lunas
6. Export payroll → download Excel
```

### 10.4 Anggota Lihat Cicilan

```
1. Anggota login → buka /installments
2. Lihat daftar cicilan aktif
3. Klik cicilan → lihat jadwal pembayaran
4. Lihat status setiap angsuran (Belum / Lunas / Terlambat)
```

### 10.5 Admin Atur Credit Limit

```
1. Admin buka Filament → Customer Resource
2. Edit anggota → lihat level & default credit limit
3. Override credit limit individual (jika perlu)
4. Lihat ringkasan: limit terpakai, sisa limit
5. Simpan perubahan
```

---

## 11. Middleware & Access Control

### 11.1 Route Middleware

```php
// routes/web.php

// Public routes (tanpa harga)
Route::get('/', [HomeController::class, 'index']);
Route::get('/products', [CatalogController::class, 'index']);
Route::get('/products/{slug}', [CatalogController::class, 'show']);

// Protected routes (harus login)
Route::middleware(['auth:customer'])->group(function () {
    Route::get('/cart', [CartController::class, 'index']);
    Route::get('/checkout', [CheckoutController::class, 'index']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{uuid}', [OrderController::class, 'show']);
    Route::get('/installments', [InstallmentController::class, 'index']);
    Route::get('/installments/{uuid}', [InstallmentController::class, 'show']);
});
```

### 11.2 Price Visibility Logic

```php
// Di Controller atau Middleware
$data = [
    'products' => ProductResource::collection($products)->resolve(auth('customer')->user()),
];

// Di ProductResource
public function toArray($request)
{
    $isAuthenticated = auth('customer')->check();
    
    return [
        'id' => $this->id,
        'name' => $this->name,
        'slug' => $this->slug,
        'description' => $this->description,
        'image' => $this->getFirstMediaUrl('images'),
        'category' => $this->category->name,
        'price' => $isAuthenticated ? $this->price : null,
        'sale_price' => $isAuthenticated ? $this->sale_price : null,
        'installment_plans' => $isAuthenticated ? $this->getInstallmentPlans() : null,
    ];
}
```

### 11.3 Frontend Conditional Rendering

```vue
<!-- ProductCard.vue -->
<template>
  <div v-if="isAuthenticated">
    <span class="price">{{ formatCurrency(product.price) }}</span>
    <span class="installment-info">
      Cicilan mulai {{ formatCurrency(product.monthlyInstallment) }}/bulan
    </span>
  </div>
  <div v-else>
    <router-link to="/login" class="login-prompt">
      🔒 Login untuk lihat harga
    </router-link>
  </div>
</template>
```

---

## 12. Scheduled Tasks

### 12.1 Overdue Check (Daily)

```php
// app/Console/Commands/MarkOverdueInstallments.php
// Cron: Setiap hari jam 00:01

protected function handle()
{
    $count = 0;
    
    // Find installment payments that are overdue
    InstallmentPayment::where('status', 'unpaid')
        ->where('due_date', '<', now()->startOfDay())
        ->each(function ($payment) use (&$count) {
            $payment->update(['status' => 'overdue']);
            
            // Update parent installment status
            if ($payment->installment->status === 'active') {
                $payment->installment->update(['status' => 'overdue']);
            }
            
            $count++;
        });
    
    $this->info("Marked {$count} installment payments as overdue.");
});
```

**Scheduler:**

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('installments:mark-overdue')->dailyAt('00:01');
}
```

---

## 13. Error Handling

### 13.1 Credit Limit Errors

| Code                         | HTTP | Message                                  |
| ---------------------------- | ---- | ---------------------------------------- |
| `CREDIT_LIMIT_EXCEEDED`      | 400  | "Limit kredit tidak mencukupi"          |
| `CREDIT_LIMIT_NOT_SET`       | 400  | "Limit kredit belum diatur"             |
| `INSTALLMENT_NOT_AVAILABLE`  | 400  | "Produk ini tidak tersedia untuk cicilan"|
| `INSTALLMENT_PLAN_INACTIVE`  | 400  | "Tenor cicilan tidak tersedia"          |
| `ACTIVE_INSTALLMENT_LIMIT`   | 400  | "Anda masih memiliki cicilan aktif yang melebihi batas" |

### 13.2 Validation Rules

```php
// StoreInstallmentRequest
'installment_plan_id' => 'required|exists:installment_plans,id',
'transaction_id' => 'required|exists:transactions,id',

// UpdateCreditLimitRequest
'credit_limit' => 'nullable|numeric|min:0',
'customer_level_id' => 'required|exists:customer_levels,id',
```

---

## 14. Ekspor Payroll (Excel/Spreadsheet)

### 14.1 Format Export

**Filename:** `potongan-gaji-{bulan}-{tahun}.xlsx`

**Sheet 1 — Ringkasan:**

| Kolom              | Isi                         |
| ------------------ | --------------------------- |
| Bulan              | Juni 2026                   |
| Total Anggota      | 150                         |
| Total Potongan     | Rp 45.000.000              |
| Total Cicilan Aktif| 85                          |

**Sheet 2 — Detail Potongan:**

| NIP    | Nama           | Level | Dept      | Total Potongan | Cicilan Aktif |
| ------ | -------------- | ----- | --------- | -------------- | ------------- |
| 198501 | Budi Santoso   | Guru  | IPA       | Rp 105.000    | 2             |
| 199002 | Siti Rahayu    | Staf  | TU        | Rp 52.500     | 1             |

**Sheet 3 — Detail Angsuran:**

| NIP    | Nama           | No. Pesanan | Bulan Ke | Angsuran | Jatuh Tempo  |
| ------ | -------------- | ----------- | -------- | -------- | ------------ |
| 198501 | Budi Santoso   | TRX-001     | 3/6      | Rp 52.500| 15 Jun 2026 |
| 198501 | Budi Santoso   | TRX-002     | 1/3      | Rp 52.500| 15 Jun 2026 |

### 14.2 Library

- Menggunakan `maatwebsite/excel` (Laravel Excel) — sudah umum di ekosistem Laravel
- Export menggunakan `Export` class di `app/Exports/`

```
📁 app/Exports/
└── PayrollDeductionExport.php   (new)
```

---

## 15. Testing Scenarios

### 15.1 Backend Tests

- [ ] CustomerLevel CRUD (create, read, update, delete)
- [ ] InstallmentPlan CRUD
- [ ] Create installment from transaction
- [ ] Generate installment payment schedule
- [ ] Credit limit validation (exceed, under, edge cases)
- [ ] Mark overdue installment payments (scheduled command)
- [ ] Process manual payment
- [ ] Process payroll deduction
- [ ] Complete installment (all payments paid)
- [ ] Payroll export (correct data, correct format)
- [ ] Price visibility for guest vs authenticated customer

### 15.2 Frontend Tests

- [ ] Product card shows "Login untuk lihat harga" for guest
- [ ] Product card shows price for authenticated customer
- [ ] Installment calculator simulation
- [ ] Checkout with installment flow
- [ ] Credit limit exceeded error handling
- [ ] Installment schedule display
- [ ] Order history with installment tab

### 15.3 Integration Tests

- [ ] Full checkout flow: add to cart → select installment → verify credit check → create transaction
- [ ] Scheduled overdue marking
- [ ] Payroll export matches expected data
- [ ] Credit limit updates reflect immediately

---

## 16. Acceptance Criteria

### 16.1 Akses Privat

- [ ] Guest bisa melihat katalog produk tanpa harga
- [ ] Product menampilkan "Login untuk lihat harga" untuk guest
- [ ] Setelah login, harga dan simulasi cicilan terlihat
- [ ] Cart, checkout, dan order hanya bisa diakses anggota terautentikasi

### 16.2 Sistem Cicilan

- [ ] Admin bisa membuat tenor cicilan (3, 6, 12, 24 bulan) dengan fee
- [ ] Anggota bisa simulasi cicilan di halaman produk dan checkout
- [ ] Checkout cicilan membuat transaksi + jadwal cicilan otomatis
- [ ] Jadwal cicilan menampilkan angsuran per bulan dengan tanggal jatuh tempo
- [ ] Status cicilan: active, completed, overdue, cancelled

### 16.3 Credit Limit

- [ ] Setiap level punya default credit limit
- [ ] Admin bisa override credit limit per anggota
- [ ] Checkout cicilan memvalidasi credit limit
- [ ] Error ditampilkan jika cicilan melebihi sisa limit
- [ ] Sisa limit diupdate secara real-time

### 16.4 Pengiriman

- [ ] Opsi "Ambil di Toko" tanpa ongkir
- [ ] Opsi "Kurir Internal" dengan biaya tetap yang dikonfigurasi admin
- [ ] Checkout menampilkan opsi pengiriman yang sesuai

### 16.5 Riwayat Transaksi

- [ ] Tab "Pesanan" menampilkan daftar transaksi
- [ ] Tab "Cicilan Aktif" menampilkan cicilan yang sedang berjalan
- [ ] Detail cicilan menampilkan jadwal pembayaran per bulan
- [ ] Status angsuran terupdate (Belum / Lunas / Terlambat)

### 16.6 Admin — Manajemen Anggota

- [ ] Admin bisa CRUD level anggota (Guru, Staf, Siswa, Custom)
- [ ] Admin bisa set default credit limit per level
- [ ] Admin bisa override credit limit per anggota individual
- [ ] Admin bisa lihat ringkasan kredit per anggota

### 16.7 Admin — Modul Piutang

- [ ] Dashboard menampilkan total piutang dan statistik
- [ ] Daftar cicilan dengan filter (status, level, anggota)
- [ ] Detail cicilan dengan jadwal pembayaran
- [ ] Aksi: tandai potong gaji, catat pembayaran manual
- [ ] Notifikasi cicilan terlambat

### 16.8 Admin — Ekspor Payroll

- [ ] Admin bisa pilih bulan dan tahun untuk ekspor
- [ ] Filter per level anggota
- [ ] Export format Excel (.xlsx) dan CSV
- [ ] Data yang diekspor sesuai format potongan gaji

---

## 17. Implementation Files

### 17.1 Database

```
📁 database/
├── migrations/
│   ├── xxxx_xx_xx_create_customer_levels_table.php
│   ├── xxxx_xx_xx_create_installment_plans_table.php
│   ├── xxxx_xx_xx_create_installments_table.php
│   ├── xxxx_xx_xx_create_installment_payments_table.php
│   ├── xxxx_xx_xx_add_credit_columns_to_customers_table.php
│   └── xxxx_xx_xx_add_installment_columns_to_transactions_table.php
├── settings/
│   └── xxxx_xx_xx_create_toko_private_settings.php   (opsional)
└── seeders/
    └── CustomerLevelSeeder.php
```

### 17.2 Models

```
📁 app/Models/
├── CustomerLevel.php              (new)
├── InstallmentPlan.php            (new)
├── Installment.php                (new)
├── InstallmentPayment.php         (new)
├── Customer.php                   (modify — add relationships, accessors)
└── Transaction.php                (modify — add installment relationship)
```

### 17.3 Services

```
📁 app/Services/
├── InstallmentService.php         (new)
├── CreditLimitService.php         (new)
├── PayrollExportService.php       (new)
└── (existing services — no changes)
```

### 17.4 Controllers

```
📁 app/Http/Controllers/
├── Frontend/
│   ├── HomeController.php          (modify)
│   ├── CatalogController.php      (modify — or existing ProductController)
│   ├── CheckoutController.php     (modify)
│   └── InstallmentController.php  (new)
├── Api/
│   └── InstallmentController.php   (new)
└── (existing controllers — no changes)
```

### 17.5 Filament

```
📁 app/Filament/
├── Resources/
│   ├── CustomerLevelResource.php      (new)
│   ├── InstallmentPlanResource.php    (new)
│   ├── InstallmentResource.php        (new)
│   ├── InstallmentPaymentResource.php (new)
│   └── CustomerResource.php          (modify — add credit limit, level)
├── Pages/
│   └── PayrollExportPage.php          (new)
└── Widgets/
    ├── OutstandingDebtWidget.php     (new)
    ├── OverdueInstallmentsWidget.php  (new)
    ├── DueThisMonthWidget.php         (new)
    └── CreditUtilizationWidget.php    (new)
```

### 17.6 Frontend

```
📁 resources/js/frontend/
├── pages/
│   ├── Installment/
│   │   ├── Index.vue                  (new)
│   │   └── Show.vue                   (new)
│   ├── Home/Index.vue                 (modify)
│   ├── Product/Index.vue              (modify)
│   ├── Product/Show.vue              (modify)
│   ├── Cart/Index.vue                 (modify)
│   ├── Checkout/Index.vue             (modify)
│   └── Orders/Index.vue               (modify)
├── components/
│   ├── Installment/
│   │   ├── InstallmentCalculator.vue  (new)
│   │   ├── InstallmentPlanSelector.vue (new)
│   │   ├── InstallmentSchedule.vue    (new)
│   │   ├── InstallmentSummary.vue    (new)
│   │   └── CreditLimitBadge.vue      (new)
│   ├── Product/
│   │   └── PriceLoginPrompt.vue      (new)
│   └── Checkout/
│       └── PaymentMethodSelector.vue  (new)
├── composables/
│   └── useInstallment.js             (new)
└── services/
    └── installmentService.js          (new)
```

### 17.7 Exports

```
📁 app/Exports/
└── PayrollDeductionExport.php         (new)
```

### 17.8 Console Commands

```
📁 app/Console/Commands/
└── MarkOverdueInstallments.php       (new)
```

---

## 18. Data Seeding

### 18.1 CustomerLevelSeeder

```php
// database/seeders/CustomerLevelSeeder.php

CustomerLevel::create([
    'name' => 'Guru/Pengajar',
    'slug' => 'guru',
    'description' => 'Tenaga pendidik',
    'default_credit_limit' => 5000000,  // Rp 5.000.000
    'is_active' => true,
]);

CustomerLevel::create([
    'name' => 'Staf/Karyawan',
    'slug' => 'staf',
    'description' => 'Tenaga kependidikan',
    'default_credit_limit' => 3000000,  // Rp 3.000.000
    'is_active' => true,
]);

CustomerLevel::create([
    'name' => 'Siswa/Murid',
    'slug' => 'siswa',
    'description' => 'Peserta didik',
    'default_credit_limit' => 1000000,  // Rp 1.000.000
    'is_active' => true,
]);
```

### 18.2 InstallmentPlanSeeder

```php
// database/seeders/InstallmentPlanSeeder.php

InstallmentPlan::create(['tenor' => 3, 'fee_percentage' => 2.00, 'description' => 'Cicilan 3 bulan', 'is_active' => true]);
InstallmentPlan::create(['tenor' => 6, 'fee_percentage' => 5.00, 'description' => 'Cicilan 6 bulan', 'is_active' => true]);
InstallmentPlan::create(['tenor' => 12, 'fee_percentage' => 10.00, 'description' => 'Cicilan 12 bulan', 'is_active' => true]);
InstallmentPlan::create(['tenor' => 24, 'fee_percentage' => 20.00, 'description' => 'Cicilan 24 bulan', 'is_active' => true]);
```

---

## 19. Glossary

| Istilah           | Definisi                                              |
| ----------------- | ----------------------------------------------------- |
| Anggota           | Customer terdaftar (Guru/Staf/Siswa)                  |
| Katalog           | Daftar produk yang tersedia                            |
| Cicilan           | Sistem pembayaran bertahap per bulan                  |
| Tenor             | Jangka waktu cicilan (3, 6, 12, 24 bulan)             |
| Fee/Bunga         | Tambahan biaya per tenor cicilan                       |
| Credit Limit      | Batas kredit maksimal per anggota                      |
| Piutang           | Total nominal cicilan yang belum dibayar              |
| Payroll Deduction | Potongan gaji otomatis untuk cicilan                  |
| DP                | Down Payment / Uang muka (opsional, di masa depan)   |
| Angsuran          | Pembayaran cicilan per bulan                           |
| Jatuh Tempo       | Tanggal batas pembayaran angsuran                      |

---

## 20. Future Enhancements (Phase 2+)

### Phase 2

- [ ] Down Payment (DP) — uang muka sebelum cicilan dimulai
- [ ] Notifikasi email — pengingat jatuh tempo cicilan
- [ ] Notifikasi WhatsApp — pengingat jatuh tempo (opsional)
- [ ] Denda keterlambatan — penalty untuk angsuran terlambat
- [ ] Partial payment — bayar sebagian angsuran

### Phase 3

- [ ] Mobile app (React Native / Flutter)
- [ ] Auto-debit dari rekening anggota
- [ ] Dashboard analytics (trend penjualan, cicilan, piutang)
- [ ] Multi-branch / multi-unit support
- [ ] Barcode/QR code untuk pengambilan barang

---

## 21. Timeline & Effort

| Phase   | Tasks                                         | Estimated Time |
| ------- | --------------------------------------------- | -------------- |
| Phase 1 | Database migrations + models + seeders       | 2 jam          |
| Phase 2 | CustomerLevel CRUD (Filament)                | 1.5 jam        |
| Phase 3 | InstallmentPlan CRUD (Filament)               | 1 jam          |
| Phase 4 | CreditLimitService + InstallmentService       | 3 jam          |
| Phase 5 | Checkout modification (cicilan + credit check)| 3 jam          |
| Phase 6 | Installment Filament Resource + Widgets       | 2.5 jam        |
| Phase 7 | PayrollExportService + Filament Page          | 2 jam          |
| Phase 8 | Frontend: Partial private access              | 2 jam          |
| Phase 9 | Frontend: Installment calculator & UI          | 3 jam          |
| Phase 10| Frontend: Installment pages + schedule         | 2.5 jam        |
| Phase 11| Frontend: Order history modifications          | 1.5 jam        |
| Phase 12| Scheduled commands (overdue)                     | 1 jam          |
| Phase 13| Testing & bug fixes                              | 3 jam          |

**Total Estimated: ~25 jam**

---

## 22. Timeline per Fase

| Fase     | Waktu | Dependent On     |
| -------- | ----- | ---------------- |
| Phase 1  | 2 jam  | -               |
| Phase 2  | 1.5 jam| Phase 1         |
| Phase 3  | 1 jam  | Phase 1         |
| Phase 4  | 3 jam  | Phase 1, 2      |
| Phase 5  | 3 jam  | Phase 4         |
| Phase 6  | 2.5 jam| Phase 4         |
| Phase 7  | 2 jam  | Phase 4         |
| Phase 8  | 2 jam  | Phase 2         |
| Phase 9  | 3 jam  | Phase 4, 5      |
| Phase 10 | 2.5 jam| Phase 5, 9      |
| Phase 11 | 1.5 jam| Phase 10        |
| Phase 12 | 1 jam  | Phase 4         |
| Phase 13 | 3 jam  | Phase 1-12      |

---

## 23. Revision History

| Version | Date     | Author   | Changes                |
| ------- | -------- | -------- | ----------------------- |
| 1.0     | May 2026 | Dev Team | Initial PRD creation   |

---

**Document End**

_This PRD is a living document and subject to change based on user feedback and market conditions._
