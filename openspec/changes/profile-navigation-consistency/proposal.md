## Why

Halaman profil pelanggan mencampurkan navigasi section lokal, rute pesanan terpisah, dan tautan wishlist yang terlepas dari menu utama. Card serta susunan mobile juga tidak konsisten, sehingga pelanggan harus menebak lokasi dan perilaku setiap tujuan akun.

## What Changes

- Menyatukan tujuan akun dan belanja dalam satu navigasi berbasis URL.
- Menyediakan navigasi akun yang ringkas dan mudah dijangkau pada mobile.
- Menyeragamkan hierarki card, header section, dan posisi logout.
- Memastikan kontrol foto profil tetap mudah digunakan pada perangkat sentuh.
- Menambahkan penggantian kata sandi pelanggan yang memverifikasi kata sandi saat ini dan mengikuti kebijakan kata sandi toko.
- Memecah section akun ke komponen terfokus dan menggunakan shell navigasi akun yang sama di halaman Wishlist mandiri.
- Mempertahankan perilaku profil, alamat, pesanan, wishlist, dan logout yang sudah ada.

## Capabilities

### New Capabilities

- `customer-account-navigation`: Navigasi akun pelanggan yang konsisten, URL-backed, dan responsif.
- `customer-account-layout`: Hierarki card dan layout halaman akun yang seragam pada desktop serta mobile.
- `customer-password-management`: Penggantian kata sandi mandiri oleh pelanggan terautentikasi.
- `customer-account-shell`: Shell navigasi yang dapat digunakan kembali oleh halaman akun dan wishlist.

### Modified Capabilities

- Tidak ada.

## Impact

- `resources/js/frontend/pages/Account/Profile.vue`, komponen account reusable, dan `resources/js/frontend/pages/Wishlist/Index.vue`.
- Locale frontend untuk label navigasi atau penjelasan tambahan bila diperlukan.
- Tidak ada perubahan schema atau kontrak pembayaran.
