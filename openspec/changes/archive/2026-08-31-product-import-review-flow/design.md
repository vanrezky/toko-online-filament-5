## Context

Issue #78 menambahkan alur import produk multi-langkah pada panel Filament. Saat ini `ProductResource` adalah resource produk yang sudah memiliki aksi create dan service terpisah untuk import gambar URL. Dependency Laravel Excel sudah tersedia, sedangkan model produk sudah memiliki field untuk data minimal yang diminta. Tidak diperlukan perubahan schema database.

## Goals / Non-Goals

**Goals:**

- Menjaga `ProductResource` sebagai adapter UI yang tipis.
- Menyediakan module baru untuk export template, import, validasi, preview, cache, dan submit.
- Menjaga data hasil validasi tetap server-side hingga submit.
- Menjamin token hanya dapat diproses sekali, termasuk saat request bersamaan.
- Menyediakan kontrak service yang dapat diuji tanpa browser.

**Non-Goals:**

- Mengubah model atau schema produk.
- Menambahkan import varian, metadata lanjutan, atau update produk existing.
- Memproses import melalui queue/background job.

## Decisions

### Module boundary

Gunakan namespace/module baru `App\Modules\ProductImport` dengan pemisahan `Services`, `Imports`, `Exports`, `Data` atau value objects, dan exception yang diperlukan. `ProductResource`/halaman list hanya memanggil service dan mengelola state UI. Alternatif menaruh semuanya di `app/Services` atau resource ditolak karena tidak mengikuti arah modular monorepo dan akan mencampur parsing dengan presentasi.

### Spreadsheet format

Gunakan Laravel Excel yang sudah terpasang. Export menghasilkan satu workbook dengan sheet `Produk` dan `Kategori dan Gudang`; referensi kategori dan gudang ditulis sebagai dua bagian bernomor pada sheet kedua. Import mengabaikan key hasil parsing header dan menggunakan pemetaan positional A1:K1 sebagai header, lalu membaca data mulai baris 2. Normalisasi whitespace/case hanya digunakan untuk pencocokan nama kategori/gudang. Import mengembalikan error terstruktur per baris/kolom. Alternatif CSV ditolak karena tidak mendukung sheet referensi yang diminta.

### Validation and mapping

Validasi dilakukan sebelum transaksi pembuatan produk. Nama kategori dan gudang dipetakan ke ID internal hanya di server. Tipe produk dipetakan ke nilai status produk yang sudah digunakan aplikasi. Nama, kategori, tipe produk, harga, dan stok wajib; deskripsi, kode, harga diskon, dan gambar URL opsional. Berat dan gudang wajib hanya untuk produk fisik, sehingga nilai digital dapat disimpan sebagai null. Kode kosong diisi melalui generator kode produk yang sama dengan resource. Gambar URL diteruskan ke service importer gambar yang sudah ada setelah produk dibuat; kegagalan gambar harus mengikuti perilaku service tersebut tanpa mengubah kontrak produk.

### Preview cache and token

Simpan payload tervalidasi, user ID pembuat, dan metadata waktu pada key cache yang namespaced dan bertanda UUID dengan TTL 1 jam. Submit memverifikasi token dan user pemiliknya, memperoleh lock berdasarkan token, membuat produk dalam transaksi, lalu menghapus payload hanya setelah transaksi berhasil. Lock mencegah submit bersamaan; kegagalan transaksi mempertahankan preview yang masih valid untuk retry.

### Filament integration

Tambahkan aksi pada halaman daftar produk dan alur UI Filament untuk download, upload/validasi, review, serta submit token. Notifikasi dan teks user-facing mengikuti sistem translation admin yang sudah ada, termasuk informasi TTL. Tidak ada endpoint publik atau payload produk baru yang dibutuhkan.

## Risks / Trade-offs

- **[Risk]** Cache backend tidak mendukung lock secara konsisten → gunakan cache lock resmi aplikasi dan dokumentasikan kebutuhan store cache yang mendukung lock.
- **[Risk]** Nama kategori/gudang ambigu atau berubah setelah template diunduh → validasi hanya menerima kecocokan nama yang unik dan gagal dengan error jelas bila tidak ditemukan/ambigu.
- **[Risk]** Import banyak baris menghabiskan memori pada request → terapkan batas jumlah baris file yang wajar dan validasi ukuran/format upload sebelum parsing.
- **[Risk]** Produk berhasil dibuat tetapi import gambar URL gagal → produk tetap mengikuti transaksi produk, sementara error gambar dilaporkan menggunakan mekanisme importer yang sudah ada dan diuji terpisah.

## Migration Plan

Tidak ada migration database. Deploy code dan translation secara atomik; cache preview lama tidak perlu dimigrasikan. Rollback cukup menghapus aksi UI/module baru, sedangkan produk yang sudah berhasil dibuat tetap merupakan data bisnis yang tidak dihapus otomatis.
