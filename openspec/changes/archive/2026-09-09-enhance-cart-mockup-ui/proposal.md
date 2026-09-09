## Why

Halaman `/cart` belum mengikuti mockup Bristol Shop untuk desktop, tablet, dan mobile. Penyelarasan tampilan akan memperjelas item dan ringkasan belanja, dengan data tambahan statis di frontend sesuai draft kebutuhan terbaru.

## Issue and Planning Status

- Sumber kebutuhan: [draft Issue yang direvisi](../../../.docs/issue-drafts/cart-mockup-enhancement.md).
- GitHub Issue: [#114](https://github.com/vanrezky/toko-online-filament3/issues/114).
- Klasifikasi: `feature`, `P2`, `STANDARD` dengan lightweight OpenSpec.
- Draft dan artefak disetujui pengguna pada 2026-09-09. Implementasi berlangsung pada branch `feat/114-cart-mockup-ui`.

## What Changes

- Cocokkan hierarki, warna, tipografi, spacing, border, gambar, dan susunan `/cart` dengan `.docs/design-references/carts/dekstop.png`, `tablet.png`, dan `mobile.png`.
- Desktop/tablet menggunakan daftar item di kiri dan ringkasan di kanan; mobile mengikuti urutan referensi dan bar checkout tetap di bawah.
- Tampilkan manfaat belanja sesuai mockup dan rekomendasi produk katalog nyata berdasarkan kategori isi cart. Cache hanya shortlist ID kandidat, lalu hydrate ulang produk agar stok, harga, promo, media, dan harga reseller tetap segar.
- Sesuai feedback pengguna, hapus blok voucher (termasuk placeholder diskon), metode pengiriman/pembayaran, dan informasi keamanan dari cart.
- Selaraskan container, tipografi, warna, tombol, dan kartu dengan Products/Show.vue dan Home/Index.vue melalui token serta komponen storefront yang tersedia.
- Pertahankan data cart yang tersedia, seleksi item, perubahan kuantitas, hapus item, subtotal pilihan, checkout pilihan, dan kondisi kosong.
- Sediakan teks UI Indonesia/Inggris serta verifikasi visual ketiga view, overflow, dan akses konten di atas bar checkout mobile.

## Capabilities

### New Capabilities

- `storefront-cart-experience`: kesesuaian visual keranjang terhadap mockup desktop/tablet/mobile, batas data statis, dan preservasi interaksi cart.

### Modified Capabilities

Tidak ada perubahan kontrak checkout. Perilaku `cart-selected-checkout-action` tetap dipertahankan, termasuk hanya satu aksi checkout utama yang terlihat pada suatu viewport.

## Impact

- Frontend: `resources/js/frontend/pages/Cart/Index.vue`, komponen khusus cart bila diperlukan, locale frontend Indonesia/Inggris, dan pengujian terkait.
- Backend: service rekomendasi cart terisolasi, integrasi `CartController`, reuse `ProductSimpleResource`, managed product-catalog cache, dan pengujian terkait.
- Tidak ada perubahan schema/database, kontrak endpoint, aturan harga, voucher, ongkir, pembayaran, atau checkout backend.
- Tidak mencakup halaman detail produk, redesign halaman lain, frequently-bought-together berbasis histori transaksi, publikasi, merge, atau deployment.
- Referensi mobile menggunakan nama terbaru `mobile.png`; nama file lama tidak digunakan.
