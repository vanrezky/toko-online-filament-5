## Why

Admin membutuhkan cara yang lebih terkontrol untuk membuat produk secara massal. Import berbasis template dengan validasi dan review sebelum submit mengurangi kesalahan data, sementara penyimpanan hasil validasi sementara mencegah frontend mengubah payload setelah validasi.

GitHub Issue: #78

## What Changes

- Menambahkan download template Excel produk dengan sheet produk dan sheet referensi kategori/gudang.
- Menambahkan alur upload, validasi per baris/kolom, preview hasil validasi, dan submit terpisah.
- Mencocokkan kategori dan gudang berdasarkan nama, serta membuat kode produk otomatis jika kosong.
- Menyimpan hasil validasi pada cache selama satu jam menggunakan UUID/token sekali pakai.
- Menghapus token dan data cache setelah submit berhasil atau token kedaluwarsa.
- Menempatkan logika template, import, validasi, preview, dan submit pada module/service baru di `app/Modules`; `ProductResource` hanya menjadi adapter UI.

## Capabilities

### New Capabilities

- `product-import-review`: Import produk minimal berbasis Excel dengan validasi, preview, token cache satu kali, dan submit.

### Modified Capabilities

Tidak ada.

## Impact

- `app/Filament/Resources/Products/ProductResource.php` dan halaman daftar produk untuk aksi admin.
- Module/service baru di `app/Modules` untuk export template, import, validasi, cache, dan submit.
- Test feature/unit untuk template, validasi, pencocokan referensi, token, dan pembuatan produk.
- Menggunakan dependency Laravel Excel yang sudah tersedia; tidak membutuhkan perubahan schema database.
