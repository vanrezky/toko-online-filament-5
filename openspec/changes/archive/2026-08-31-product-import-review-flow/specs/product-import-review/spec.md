## Purpose

Memungkinkan admin membuat produk secara massal melalui template Excel yang divalidasi dan direview sebelum data disubmit secara aman.

## ADDED Requirements

### Requirement: Downloadable import template

Sistem SHALL menyediakan template Excel produk yang memiliki sheet `Produk` dan sheet `Kategori dan Gudang`. Sheet `Produk` SHALL memuat kolom nama produk, deskripsi, kode produk, kategori, tipe produk, harga, harga diskon, stok, berat, gudang pengiriman, dan gambar URL. Kolom nama produk, kategori, tipe produk, harga, dan stok SHALL ditandai sebagai wajib. Deskripsi, kode produk, harga diskon, dan gambar URL SHALL bersifat opsional. Berat dan gudang pengiriman SHALL wajib diisi hanya untuk produk fisik.

#### Scenario: Admin downloads the template
- **WHEN** admin memilih aksi download template import produk
- **THEN** sistem mengunduh file Excel dengan kedua sheet dan kolom yang ditentukan

#### Scenario: Reference sheet contains names only
- **WHEN** template dibuat berdasarkan kategori dan gudang yang tersedia
- **THEN** sheet `Kategori dan Gudang` menampilkan nomor berurutan dan nama kategori/gudang tanpa ID database

### Requirement: Validate uploaded product data

Sistem SHALL memvalidasi setiap baris file import sebelum membuat produk. Kategori dan gudang SHALL dicocokkan berdasarkan nama. Kode produk boleh kosong dan SHALL diberi kode otomatis. Harga diskon, deskripsi, dan gambar URL SHALL bersifat opsional; jika harga diskon diisi, nilainya harus lebih kecil dari harga normal, dan jika gambar URL diisi, nilainya SHALL berupa URL yang valid. Berat dan gudang pengiriman SHALL wajib diisi untuk produk fisik dan boleh kosong untuk produk digital.

#### Scenario: Valid rows are prepared for review
- **WHEN** file berisi baris dengan seluruh data wajib sesuai tipe produk dan nama kategori/gudang cocok
- **THEN** sistem menyiapkan baris tersebut sebagai data tervalidasi untuk tahap review

#### Scenario: Invalid row is rejected with location
- **WHEN** file memiliki data wajib kosong, angka tidak valid, tipe produk tidak didukung, atau nama kategori/gudang tidak cocok
- **THEN** sistem menampilkan error yang mengidentifikasi baris dan kolom bermasalah

#### Scenario: Optional values are accepted
- **WHEN** deskripsi, kode produk, harga diskon, atau gambar URL dikosongkan; atau berat dan gudang dikosongkan pada produk digital
- **THEN** baris tetap dapat lolos validasi selama nama, kategori, tipe produk, harga, dan stok valid

#### Scenario: Physical product requires logistics data
- **WHEN** produk bertipe fisik tidak memiliki berat atau gudang pengiriman
- **THEN** sistem menolak baris tersebut dengan error pada kolom yang kosong

### Requirement: Review uses server-held validated data

Setelah validasi berhasil, sistem SHALL membuat UUID/token unik dan menyimpan data tervalidasi di server-side cache selama satu jam. Tahap review SHALL menampilkan informasi masa berlaku tersebut. Submit SHALL menggunakan token dan tidak SHALL menerima ulang payload produk sebagai sumber data.

#### Scenario: Validation creates an expiring preview
- **WHEN** admin menyelesaikan validasi file
- **THEN** sistem membuat token unik, menyimpan hasil tervalidasi selama satu jam, dan menampilkan hasil review beserta informasi kedaluwarsa

#### Scenario: Expired preview cannot be submitted
- **WHEN** admin mencoba submit setelah cache satu jam kedaluwarsa
- **THEN** sistem menolak submit dan meminta admin melakukan validasi ulang

### Requirement: Submit preview exactly once

Sistem SHALL membuat produk hanya dari data tervalidasi yang terkait dengan token. Token SHALL hanya dapat berhasil digunakan satu kali dan SHALL terikat pada admin yang membuatnya.

#### Scenario: Valid token submits products
- **WHEN** admin men-submit token yang valid sebelum kedaluwarsa
- **THEN** sistem membuat seluruh produk dari data server-side yang tervalidasi dan menghapus token beserta cache setelah berhasil

#### Scenario: Reused token is rejected
- **WHEN** admin men-submit token yang sudah berhasil digunakan
- **THEN** sistem menolak submit dan tidak membuat produk tambahan

#### Scenario: Concurrent submissions are serialized
- **WHEN** dua request submit menggunakan token yang sama secara bersamaan
- **THEN** paling banyak satu request membuat produk dan request lainnya ditolak sebagai token yang sudah digunakan atau tidak tersedia

#### Scenario: Failed submission preserves retryable preview
- **WHEN** pembuatan produk gagal sebelum transaksi berhasil
- **THEN** sistem tidak menganggap token berhasil digunakan dan tidak menghapus preview yang masih berada dalam masa berlaku
