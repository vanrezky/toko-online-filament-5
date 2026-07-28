## Why

Customer pada toko public belum dapat mengelola alamat pengiriman sendiri karena seluruh endpoint alamat storefront menolak mutasi. Sebaliknya, toko private perlu tetap mempertahankan model alamat yang dikendalikan admin.

## What Changes

- Izinkan customer toko public membuat, memperbarui, menghapus, dan memilih alamat pengiriman default miliknya sendiri.
- Tandai alamat yang dibuat customer dengan sumber khusus, lalu lindungi alamat yang dibuat admin maupun yang tersinkron dari school unit dari mutasi storefront.
- Pertahankan penolakan mutasi endpoint dan tampilkan penjelasan pada profil ketika toko private.
- Pastikan admin dapat menambah dan mengelola alamat customer pada kedua mode toko.

## Capabilities

### New Capabilities

- `customer-address-self-management`: Pengelolaan alamat customer berbasis mode toko dan kepemilikan sumber alamat.

### Modified Capabilities

- None.

## Impact

- Backend: `AccountController`, `CustomerAddress`, validasi alamat, dan resource alamat storefront.
- Frontend: tab Alamat pada `Account/Profile.vue` serta locale Indonesia dan Inggris.
- Admin: relation manager alamat customer di Filament.
- Tests: alur public/private, ownership, dan perlindungan alamat admin/school unit.
