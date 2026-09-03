## Why

Admin belum dapat menemukan Produk dan Pelanggan dengan cepat dari Global Search Filament. Mengaktifkan pencarian untuk dua resource utama akan mempercepat akses ke record tanpa harus membuka daftar resource terlebih dahulu.

## What Changes

- Mengaktifkan Global Search untuk resource Produk.
- Mengaktifkan Global Search untuk resource Pelanggan.
- Menentukan atribut pencarian, judul hasil, detail pendukung, dan URL hasil pada masing-masing resource.
- Menambahkan regression test untuk konfigurasi dan hasil Global Search.
- Menambahkan verifikasi Playwright dari panel admin.

## Capabilities

### New Capabilities

- `admin-global-search`: Pencarian global untuk record Produk dan Pelanggan.

### Modified Capabilities

Tidak ada.

## Impact

- `app/Filament/Resources/Products/ProductResource.php`
- `app/Filament/Resources/Customers/CustomerResource.php`
- Test unit/feature resource dan test Playwright admin.
- Tidak ada perubahan database, API publik, atau dependency baru.
