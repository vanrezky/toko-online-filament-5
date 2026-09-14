# Toko Online

Repositori ini adalah aplikasi e-commerce berbasis Laravel dengan:

- storefront customer berbasis Inertia + Vue
- panel admin berbasis Filament
- pembayaran multi-gateway
- voucher, wishlist, newsletter, dan cicilan/installment

README ini ditulis ulang sebagai referensi operasional repo, bukan template framework, supaya konteks penting tidak hilang saat handoff atau debugging di masa depan.

## Ringkasan Stack

- Backend: Laravel 11, PHP 8.3+
- Admin panel: Filament v5
- Frontend app: Inertia.js + Vue 3
- Build tool: Vite
- Styling: Tailwind CSS
- Auth/API support: Laravel Sanctum
- Media handling: Spatie Media Library
- App settings: Spatie Laravel Settings
- Export/import: Maatwebsite Excel
- Payment gateway yang sudah terhubung di codebase: Midtrans, Stripe, Xendit

## Entry Point Penting

- Frontend JS bootstrap: `resources/js/frontend.js`
- Frontend app bootstrap: `resources/js/frontend/main.js`
- Vite inputs:
  - `resources/css/app.css`
  - `resources/css/filament/admin/theme.css`
  - `resources/js/frontend.js`
- Route web utama: `routes/web.php`
- Payment webhook controller: `app/Http/Controllers/PaymentWebhookController.php`
- Payment gateway service selector: `app/Services/PaymentGatewayService.php`
- Admin pengaturan gateway: `app/Filament/Pages/ManagePaymentGateway.php`
- Source of truth schema: `database/schema/mysql-schema.sql`

## Cakupan Fitur yang Sudah Ada

- katalog produk dan halaman detail produk
- cart dan checkout
- order history dan detail order
- pembayaran via gateway aktif
- voucher customer
- wishlist
- newsletter subscription
- installment/cicilan customer
- admin management lewat Filament

## Peta Struktur Repo

Direktori yang paling sering relevan saat development:

- `app/Http/Controllers/Frontend/`: alur storefront seperti checkout, order, account, auth
- `app/Http/Controllers/Api/`: endpoint AJAX/API frontend seperti voucher dan installment
- `app/Services/`: logic domain dan integrasi, termasuk pembayaran
- `app/Services/Gateways/`: implementasi tiap payment gateway
- `app/Filament/`: admin panel dan halaman konfigurasi
- `resources/js/frontend/`: page, component, composable, service frontend
- `resources/css/`: asset CSS utama dan tema Filament
- `routes/web.php`: route web/frontend/webhook
- `database/schema/mysql-schema.sql`: referensi field dan tabel aktual
- `database/settings/`: migration untuk application settings

## Setup Lokal

### Prasyarat

- PHP 8.3+
- Composer
- Node.js dan npm
- Docker Desktop atau engine yang kompatibel untuk Laravel Sail
- MySQL yang sesuai dengan konfigurasi lokal jika tidak memakai Sail

### Bootstrapping yang direkomendasikan

Gunakan target Makefile yang sudah disediakan:

```bash
make start
make setup
```

Arti target penting:

- `make start`: menjalankan service lokal via Sail
- `make setup`: bootstrap dari kondisi awal, termasuk migrate fresh, seed, storage link, dan optimize
- `make fresh`: rebuild penuh dari nol

Jika butuh lifecycle dasar:

```bash
make stop
make destroy
```

## Command Harian

### Backend

```bash
./vendor/bin/sail artisan test
./vendor/bin/sail artisan test --filter TestName
```

### Frontend

```bash
npm run build
npm run test
npm run dev
```

### Development processes

Untuk menjalankan Sail, Vite, Horizon, dan scheduler sekaligus:

```bash
make dev
```

Jika container Sail sudah berjalan dan hanya proses development yang perlu
dijalankan ulang:

```bash
make restart-dev
```

Perintah `make restart-dev` menjalankan Vite, Horizon, dan scheduler tanpa
menjalankan ulang container Sail.

## Optional Laravel Octane + FrankenPHP

Gunakan mode ini ketika ingin mempelajari atau menguji runtime long-running yang
lebih dekat dengan production. Octane melakukan boot aplikasi sekali lalu
memakai worker yang sama untuk banyak request; FrankenPHP adalah application
server yang menjalankan worker tersebut. Karena state proses bertahan lebih
lama, mode ini membantu menemukan singleton, static state, atau konfigurasi
request yang tidak dibersihkan dengan benar.

Mode ini opt-in dan tidak menggantikan Sail default:

```bash
make frankenphp-build
make frankenphp-start
make frankenphp-ps
make frankenphp-logs
```

Secara default aplikasi tersedia di `http://localhost:8081`. Untuk menghentikan
mode ini:

```bash
make frankenphp-stop
```

Set `OCTANE_FRANKENPHP_PORT` untuk memakai port lain. FrankenPHP sudah menjadi
HTTP server, jadi mode ini tidak memerlukan Nginx. Vite tetap berjalan sebagai
proses host; jika port runtime atau konfigurasi environment diubah, restart
Vite agar konfigurasi HMR dibaca ulang.

Untuk benchmark atau runtime production-like, matikan file watcher:

```bash
OCTANE_WATCH=0 make frankenphp-start
```

Mode development tetap memakai `OCTANE_WATCH=1` secara default. Setelah kode PHP
berubah pada mode tanpa watcher, restart service FrankenPHP agar worker memuat
kode terbaru.

## Catatan Environment yang Mudah Terlewat

- `phpunit.xml` mengarah ke database test di `127.0.0.1:3307` dengan nama `toko_online_testing`.
- Test backend bisa gagal di luar Sail kalau DB test itu tidak tersedia.
- `opencode.json` juga mengasumsikan DB port `3307`.
- Vite/local app umumnya memakai `http://localhost:81`.
- `.env.example` belum tentu mencerminkan seluruh nilai lokal yang dipakai test automation atau tool internal; verifikasi juga ke `phpunit.xml`, `opencode.json`, dan `docker-compose.yml`.

## Routing dan Gotcha Penting

- Group route frontend memakai name prefix `frontend.` di `routes/web.php`.
- Endpoint webhook pembayaran adalah:

```text
POST /webhooks/payment/{gateway}
```

- Route wildcard produk `Route::get('{product}', ...)` harus tetap menjadi route terakhir di group `frontend.`. Kalau dipindah ke atas, route frontend lain akan gampang ketabrak.
- CSRF dikecualikan untuk `webhooks/payment/*`, jadi perubahan di area webhook harus hati-hati dan selalu mempertimbangkan validasi signature dari gateway.

## Arsitektur Pembayaran

Pembayaran dipusatkan lewat `app/Services/PaymentGatewayService.php`.

Service ini memilih gateway aktif dari settings dan saat ini sudah menghubungkan:

- Midtrans
- Stripe
- Xendit

Implementasi gateway berada di:

- `app/Services/Gateways/MidtransGateway.php`
- `app/Services/Gateways/StripeGateway.php`
- `app/Services/Gateways/XenditGateway.php`

Pengaturan gateway disimpan lewat settings, bukan hardcoded config biasa. Lokasi penting:

- `app/Settings/PaymentGatewaySettings.php`
- `app/Filament/Pages/ManagePaymentGateway.php`
- `database/settings/2026_03_26_053000_add_channels_to_payment_gateway_settings.php`

Hal yang perlu diingat:

- Gateway aktif ditentukan dari settings aplikasi.
- Redirect success/failure order dikontrol di implementasi gateway.
- Midtrans memakai validasi signature berbasis `SHA512(order_id + status_code + gross_amount + server_key)`.
- Webhook diproses lewat `PaymentWebhookController`, yang juga memicu update status transaksi dan job notifikasi saat relevan.

## Frontend Storefront

Frontend customer bukan SPA terpisah; ia dibootstrap dari Laravel lewat Inertia.

Fakta penting:

- bootstrap dimulai dari `resources/js/frontend.js`
- app dibuat di `resources/js/frontend/main.js`
- page frontend berada di `resources/js/frontend/pages/`
- service client/frontend helper berada di `resources/js/frontend/services/`
- Vue app memakai `@inertiajs/vue3` dan `vue-i18n`

Jika ada perubahan flow customer, cek minimal area berikut:

- controller frontend terkait di `app/Http/Controllers/Frontend/`
- page Vue terkait di `resources/js/frontend/pages/`
- endpoint AJAX/API terkait di `app/Http/Controllers/Api/`

## Area Domain yang Sering Terkait

- Checkout: `app/Http/Controllers/Frontend/CheckoutController.php`
- Order: `app/Http/Controllers/Frontend/OrderController.php`
- Voucher API: `app/Http/Controllers/Api/VoucherController.php`
- Voucher frontend: `app/Http/Controllers/Frontend/VoucherController.php`
- Wishlist: `app/Http/Controllers/Frontend/WishlistController.php`
- Newsletter: `app/Http/Controllers/Frontend/NewsletterController.php`
- Installment API/customer flow: controller `Installment` di area `Api` dan `Frontend`, plus `app/Services/InstallmentService.php`

## Admin dan Settings

Admin panel memakai Filament. Saat investigasi behavior admin, mulai dari:

- `app/Filament/`
- `app/Settings/`
- `database/settings/`

Untuk perubahan konfigurasi pembayaran, `ManagePaymentGateway` adalah entrypoint yang lebih relevan daripada langsung mengubah file config statis.

## Deploy dan CI

Fakta deploy yang harus dianggap source of truth:

- workflow deploy berjalan saat push ke branch `main`
- runtime CI memakai PHP 8.3 dan Node 22
- proses build/install di CI meliputi:
  - `composer install --no-dev`
  - `npm ci`
  - `npm run build`

Kalau bug hanya muncul di CI atau production:

- cek kompatibilitas PHP 8.3, bukan hanya local PHP
- pastikan asset Vite benar-benar lolos `npm run build`
- pastikan tidak ada asumsi environment lokal yang hanya hidup di Sail

## Cara Investigasi yang Disarankan Sebelum Mengubah Fitur

Urutan yang aman untuk feature/domain-heavy change:

1. cek field/tabel aktual di `database/schema/mysql-schema.sql`
2. cek route/controller yang benar di `routes/web.php`
3. cek service/domain logic di `app/Services/`
4. cek coupling frontend di `resources/js/frontend/`
5. baru implement perubahan

Ini penting supaya perubahan tidak mengada-ada field, route, atau contract yang sebenarnya tidak ada.

## Konvensi Kerja Repo

- Untuk agent/Codex di repo ini, ikuti instruksi `AGENTS.md`.
- Saat menjalankan shell command dari agent, gunakan prefix `rtk` sesuai instruksi repo.
- Hindari menganggap README lama Laravel sebagai dokumentasi proyek; source of truth ada di code path yang disebut di atas.
- Kalau menambah fitur baru yang menyentuh domain inti, update README ini bila ada entrypoint, gotcha, atau alur baru yang perlu diketahui contributor berikutnya.

## Checklist Saat Menyentuh Area Sensitif

### Checkout atau Payment

- cek `routes/web.php`
- cek `CheckoutController`
- cek `PaymentGatewayService`
- cek gateway implementation terkait
- cek webhook behavior
- cek status transaction dan notification side effect

### Frontend Storefront

- cek controller Laravel yang me-render page Inertia
- cek page Vue yang dipakai
- cek API pendukung jika ada
- verifikasi `npm run build`

### Schema atau Data

- cek `database/schema/mysql-schema.sql`
- cek migration atau settings migration terkait
- jangan menebak nama field

## Dokumen Ini Perlu Dijaga

README ini dimaksudkan sebagai ringkasan teknis yang tahan handoff. Jika ada perubahan besar pada:

- stack
- entrypoint
- route kritikal
- payment integration
- command bootstrap
- workflow deploy

maka README ini juga perlu diperbarui dalam commit yang sama.
