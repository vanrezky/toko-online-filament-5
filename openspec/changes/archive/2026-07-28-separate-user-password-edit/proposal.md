## Why

Form edit pengguna mencampurkan perubahan profil dengan penggantian kredensial. Admin membutuhkan alur khusus untuk mengubah password tanpa menampilkan field password pada setiap pengeditan pengguna.

Linked issue: [#10](https://github.com/vanrezky/toko-online-filament3/issues/10)

## What Changes

- Sembunyikan field password dan konfirmasi password dari form edit pengguna, tanpa mengubah form pembuatan pengguna.
- Tambahkan aksi header “Ubah Password” yang membuka modal untuk memasukkan dan mengonfirmasi password baru.
- Simpan password baru hanya setelah validasi aturan password dan konfirmasi berhasil, lalu tampilkan notifikasi berhasil.

## Capabilities

### New Capabilities

- `admin-user-password-management`: Memungkinkan administrator mengubah password pengguna dari modal khusus pada halaman edit pengguna.

### Modified Capabilities

- Tidak ada.

## Impact

- `app/Filament/Resources/Users/UserResource.php`
- `app/Filament/Resources/Users/Pages/EditUser.php`
- `lang/id/admin/user-resource.php`
- Pengujian fitur untuk resource pengguna bila infrastruktur pengujian tersedia.
