# Filament v3 -> v4 Upgrade Checklist

Source: https://filamentphp.com/docs/4.x/upgrade-guide

## 1) Prasyarat & baseline

- [x] PHP >= 8.2
- [x] Laravel >= 11.28
- [x] Filament core dan plugin resmi naik ke v4 (`filament/*`)
- [ ] Tailwind CSS v4.1+ untuk **custom Filament theme** (jika theme custom dipakai)
- [x] Evaluasi plugin pihak ketiga yang tidak kompatibel v4

## 2) Langkah upgrade utama

- [x] Install tool upgrade: `filament/upgrade:^4.0`
- [x] Jalankan automated upgrade script: `vendor/bin/filament-v4`
- [x] Update dependency dengan `composer update`
- [x] Remove tool upgrade setelah selesai final: `composer remove filament/upgrade --dev`

## 3) Plugin compatibility

- [x] `bezhansalleh/filament-shield` di-upgrade ke v4
- [x] Plugin v3-only dihapus sementara agar dependency resolve:
  - `aymanalhattami/filament-page-with-sidebar`
  - `bezhansalleh/filament-exceptions`
  - `njxqlus/filament-progressbar`
  - `ariaieboy/filament-currency`
  - `joshembling/image-optimizer`

## 4) Konfigurasi & kode yang wajib dicek manual

- [x] `config/filament-shield.php` disesuaikan untuk v4 (`auth_provider_model`)
- [x] Referensi class plugin yang sudah dihapus dibersihkan dari provider/handler
- [x] Publish/cek `config/filament.php` untuk opsi kompatibilitas (disk default, file_generation)
- [x] Tentukan apakah ingin migrate directory structure resource/cluster v4
  - Rekomendasi cek dulu: `php artisan filament:upgrade-directory-structure-to-v4 --dry-run`
  - Status: dry-run dan apply sudah dijalankan, struktur baru sudah aktif

## 5) High-impact behavior changes (harus divalidasi)

- [ ] File visibility non-local disk default jadi `private` (S3, dsb)
- [x] Filter table sekarang deferred by default (`deferFilters()`)
- [x] `Grid/Section/Fieldset` tidak span full otomatis
- [x] `unique()` sekarang ignore current record by default
- [x] Opsi pagination `'all'` tidak aktif default
- [x] URL query parameter Filament resource berubah (`activeTab` -> `tab`, dst)
- [x] Tenancy scoping otomatis lebih ketat di v4

## 6) Theme / styling Filament

- [x] Build frontend utama (`npm run build`) sudah sukses
- [x] Jika tetap pakai custom Filament theme CSS:
  - upgrade Tailwind ke v4
  - ubah `@config` ke `@source` sesuai guide
  - verifikasi seluruh class utility custom
  - Status: sementara custom Filament theme dinonaktifkan dari Vite/AdminPanel agar build stabil

## 7) Verifikasi pasca-upgrade

- [x] `php artisan package:discover` sukses
- [x] `npm run build` sukses
- [x] PHPUnit hijau tanpa skip test (`43 tests, 160 assertions`)
- [x] Aktifkan kembali dan benahi test feature yang di-skip (newsletter/contact)
- [ ] Smoke test admin panel end-to-end (login, CRUD resource utama, upload/media, permission shield)

## 8) Checkpoint status saat ini

- [x] Upgrade dependency inti v3 -> v4 selesai
- [x] Aplikasi bisa booting
- [x] Build frontend lolos
- [ ] Final hardening belum selesai (manual smoke test admin panel + validasi file visibility non-local)
