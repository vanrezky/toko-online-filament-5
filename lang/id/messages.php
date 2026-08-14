<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Flash / Response Messages
    |--------------------------------------------------------------------------
    */

    'success' => [
        'saved' => 'Data berhasil disimpan.',
        'updated' => 'Data berhasil diperbarui.',
        'deleted' => 'Data berhasil dihapus.',
        'subscribed' => 'Berhasil berlangganan newsletter! Cek email Anda untuk konfirmasi.',
        'resubscribed' => 'Berhasil berlangganan kembali! Selamat datang kembali.',
        'test_newsletter_sent' => 'Email test newsletter telah dikirim ke :email.',
        'contact_sent' => 'Pesan berhasil dikirim! Kami akan menghubungi Anda segera.',
        'message_marked_read' => 'Pesan ditandai sebagai sudah dibaca.',
        'message_deleted' => 'Pesan berhasil dihapus.',
        'order_placed' => 'Pesanan berhasil dibuat!',
        'item_added_to_cart' => 'Item berhasil ditambahkan ke keranjang.',
        'item_added_to_bag' => 'Item berhasil ditambahkan ke tas.',
        'cart_updated' => 'Keranjang berhasil diperbarui.',
        'item_removed' => 'Item berhasil dihapus.',
        'item_removed_from_bag' => 'Item berhasil dihapus dari tas.',
        'voucher_applied' => 'Voucher ":code" berhasil dipilih!',
        'voucher_removed' => 'Voucher berhasil dihapus.',
        'voucher_cleared' => 'Voucher telah dihapus.',
        'profile_updated' => 'Profil berhasil diperbarui.',
        'password_updated' => 'Kata sandi berhasil diperbarui.',
        'address_added' => 'Alamat berhasil ditambahkan.',
        'address_updated' => 'Alamat berhasil diperbarui.',
        'address_deleted' => 'Alamat berhasil dihapus.',
        'password_reset' => 'Kata sandi berhasil direset.',
    ],

    'error' => [
        'generic' => 'Terjadi kesalahan. Silakan coba lagi.',
        'not_found' => 'Data tidak ditemukan.',
        'already_subscribed' => 'Email Anda sudah terdaftar dalam newsletter.',
        'newsletter_template_not_found' => 'Template newsletter tidak ditemukan.',
        'cart_empty' => 'Keranjang Anda kosong.',
        'address_no_village' => 'Alamat yang dipilih tidak memiliki informasi kelurahan/desa.',
        'order_already_paid' => 'Pesanan sudah dibayar atau dibatalkan.',
        'payment_initiation_failed' => 'Gagal memulai pembayaran: :message',
        'payment_start_failed' => 'Gagal memulai pembayaran. Silakan coba lagi.',
        'cart_not_found' => 'Keranjang tidak ditemukan.',
        'login_required' => 'Silakan login terlebih dahulu.',
        'login_required_to_add_cart' => 'Silakan login untuk menambahkan item ke keranjang.',
        'login_required_voucher' => 'Silakan login untuk menggunakan voucher.',
        'login_required_remove_voucher' => 'Silakan login untuk menghapus voucher.',
        'variant_required' => 'Silakan pilih varian produk.',
        'checkout_failed' => 'Gagal memproses pesanan. Silakan coba lagi.',
        'voucher_invalid' => 'Voucher tidak valid.',
        'voucher_invalid_code' => 'Kode voucher tidak valid.',
        'voucher_inactive' => 'Voucher sudah tidak aktif.',
        'voucher_not_public' => 'Voucher tidak tersedia.',
        'voucher_not_started' => 'Voucher belum dimulai.',
        'voucher_expired' => 'Voucher sudah berakhir.',
        'voucher_max_usage_global' => 'Voucher sudah mencapai batas penggunaan.',
        'voucher_max_usage_user' => 'Anda sudah mencapai batas penggunaan voucher ini.',
        'voucher_min_purchase' => 'Minimal belanja :amount untuk menggunakan voucher ini.',
        'no_shipping_options' => 'Tidak ada opsi pengiriman untuk alamat ini.',
        'select_address_first' => 'Silakan pilih alamat pengiriman terlebih dahulu.',
        'address_cannot_delete' => 'Alamat tidak dapat dihapus.',
        'registration_disabled' => 'Pendaftaran dinonaktifkan.',
        'provinces_fetch_failed' => 'Gagal mengambil data provinsi dari server.',
    ],

    'info' => [
        'already_subscribed' => 'Email Anda sudah terdaftar dalam newsletter.',
        'shipping_calculated_at_checkout' => 'Dihitung saat checkout.',
    ],

];
