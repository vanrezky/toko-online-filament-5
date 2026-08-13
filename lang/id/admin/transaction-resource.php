<?php

return [

    'navigation_label' => 'Transaksi',
    'navigation_group' => 'Operasional',

    'model_label' => 'Transaksi',
    'plural_model_label' => 'Transaksi',

    'pages' => [
        'list' => [
            'title' => 'Daftar Transaksi',
        ],
        'view' => [
            'title' => 'Detail Transaksi',
        ],
        'edit' => [
            'title' => 'Edit Transaksi',
        ],
    ],

    'fields' => [
        'order_information' => 'Informasi Pesanan',
        'order_id' => 'ID Pesanan',
        'status' => 'Status',
        'payment_type' => 'Jenis Pembayaran',
        'receipt_code' => 'Kode Resi',
        'timelimit' => 'Batas Waktu',
        'delivery_date' => 'Tanggal Kirim',
        'complete_date' => 'Tanggal Selesai',
        'customer_notes' => 'Catatan Pelanggan',
        'notes' => 'Catatan',
    ],

    'columns' => [
        'order' => 'Pesanan',
        'customer' => 'Pelanggan',
        'total' => 'Total',
        'items' => 'Item',
        'shipping_method' => 'Metode Pengiriman',
        'shipping_address' => 'Alamat Pengiriman',
        'pickup_only' => 'Pickup',
        'pickup_no_address' => 'Pickup - alamat tidak diperlukan',
        'cod' => 'COD',
    ],

    'status' => [
        'packed' => 'Dikemas',
        'in_transit' => 'Dalam Pengiriman',
        'shipped' => 'Dikirim',
        'delivered' => 'Diterima',
        'picked_up' => 'Sudah Diambil',
        'cancelled' => 'Dibatalkan',
        'completed' => 'Selesai',
    ],

    'billing_status' => [
        'not_applicable' => 'Tidak Berlaku',
        'pending' => 'Menunggu',
        'submitted' => 'Diajukan',
        'paid' => 'Lunas',
        'failed' => 'Gagal',
        'cancelled' => 'Dibatalkan',
    ],

    'payment_types' => [
        'full' => 'Bayar Penuh dari Limit Kredit',
        'installment' => 'Cicilan',
        'balance' => 'Saldo Toko',
    ],

    'filters' => [
        'billing_status' => 'Status Penagihan',
        'created_at' => 'Tanggal Transaksi',
        'created_from' => 'Dari Tanggal',
        'created_until' => 'Sampai Tanggal',
    ],

    'sections' => [
        'order_information' => 'Informasi Pesanan',
        'order_summary' => 'Ringkasan Pesanan',
        'customer_information' => 'Informasi Pelanggan',
        'products' => 'Produk',
        'shipping_information' => 'Informasi Pengiriman',
        'vouchers_applied' => 'Voucher Digunakan',
        'digital_products' => 'Produk Digital',
        'additional_information' => 'Informasi Tambahan',
    ],

    'entries' => [
        'order_id' => 'ID Pesanan',
        'status' => 'Status',
        'order_date' => 'Tanggal Pesanan',
        'payment_deadline' => 'Batas Waktu Pembayaran',
        'subtotal' => 'Subtotal',
        'total_discount' => 'Total Diskon',
        'shipping_cost' => 'Biaya Kirim',
        'cod_fee' => 'Biaya COD',
        'total_amount' => 'Total Bayar',
        'name' => 'Nama',
        'email' => 'Email',
        'phone' => 'Telepon',
        'shipping_address' => 'Alamat Pengiriman',
        'product' => 'Produk',
        'qty' => 'Jml',
        'price' => 'Harga',
        'discount' => 'Diskon',
        'digital' => 'Digital',
        'courier' => 'Kurir',
        'warehouse' => 'Gudang',
        'code' => 'Kode',
        'estimation' => 'Estimasi',
        'receipt_code' => 'Kode Resi',
        'weight' => 'Berat',
        'voucher' => 'Voucher',
        'type' => 'Tipe',
        'payment_method' => 'Metode Pembayaran',
        'payment_gateway' => 'Gateway Pembayaran',
        'cancellation_request' => 'Permintaan Pembatalan',
        'notes' => 'Catatan',
        'delivery_date' => 'Tanggal Kirim',
        'completed_date' => 'Tanggal Selesai',
        'this_order_contains_digital_products' => 'Pesanan ini mengandung produk digital',
        'not_set' => 'Belum diatur',
        'no_notes' => 'Tidak ada catatan',
        'not_delivered_yet' => 'Belum dikirim',
        'not_completed_yet' => 'Belum selesai',
    ],

    'actions' => [
        'mark_as_shipped' => 'Tandai Dikirim',
        'mark_as_picked_up' => 'Tandai Sudah Diambil',
        'mark_as_rejected' => 'Tandai Ditolak',
        'mark_as_delivered' => 'Tandai Diterima',
        'mark_as_completed' => 'Tandai Selesai',
    ],

    'notifications' => [
        'status_updated' => 'Status Diperbarui',
        'status_changed_to' => 'Status pesanan diubah menjadi',
        'update_failed' => 'Gagal Memperbarui',
    ],

    'copy' => [
        'copied' => 'Disalin!',
    ],

];
