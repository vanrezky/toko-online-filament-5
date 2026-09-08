## Why

Social login Google dan GitHub saat ini selalu tersedia pada storefront, sementara admin belum memiliki kontrol untuk mengatur ketersediaannya. Toggle terpusat diperlukan agar admin dapat menonaktifkan social login tanpa mengubah credential OAuth atau source code.

## What Changes

- Menambahkan setting `social_login_enabled` pada `GeneralSettings` dengan default aktif untuk mempertahankan perilaku saat ini.
- Menambahkan toggle Login Sosial pada tab Akses di halaman Settings → Website.
- Menyembunyikan kontrol social login pada halaman login ketika setting nonaktif.
- Menolak akses langsung ke redirect dan callback social login ketika setting nonaktif.
- Menegakkan toggle pendaftaran akun pada endpoint dan halaman pendaftaran, sehingga pendaftaran yang dinonaktifkan tidak dapat dibuka atau diproses.
- Menambahkan pengujian backend, label terjemahan Indonesia/Inggris, dan validasi UI.

## Capabilities

### New Capabilities

- `storefront-social-login-availability`: Mengatur dan menegakkan ketersediaan login sosial Google dan GitHub pada storefront.

### Modified Capabilities

- Tidak ada.

## Impact

- `App\Settings\GeneralSettings` dan settings migration.
- Halaman Filament `ManageWebsite` pada tab Akses.
- Route dan controller social login.
- Props Inertia dan halaman login Vue.
- Controller dan halaman alur pendaftaran pelanggan.
- Translation frontend/admin.
- Feature tests untuk konfigurasi dan enforcement social login serta pendaftaran.
- Tidak ada perubahan credential provider atau schema tabel bisnis.
