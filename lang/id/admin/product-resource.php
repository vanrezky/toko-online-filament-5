<?php

return [

    'navigation_label' => 'Produk',
    'navigation_group' => 'Produk',

    'model_label' => 'Produk',
    'plural_model_label' => 'Produk',

    'pages' => [
        'list' => [
            'title' => 'Daftar Produk',
        ],
        'create' => [
            'title' => 'Buat Produk',
        ],
        'edit' => [
            'title' => 'Edit Produk',
        ],
    ],

    'tabs' => [
        'code_and_category' => 'Kode & Kategori',
        'name_and_description' => 'Nama & Deskripsi',
        'price' => 'Harga',
        'inventory' => 'Inventori',
        'images' => 'Gambar',
        'faqs' => 'FAQ',
        'seo' => 'SEO',
        'wholesales' => 'Grosir',
        'wholesales_price' => 'Harga Grosir'
    ],

    'fields' => [
        'code' => 'Kode Produk',
        'code_helper' => 'Masukkan kode unik untuk produk dan pastikan tidak sama dengan produk lain.',
        'category_id' => 'Kategori Produk',
        'category_id_helper' => 'Pilih kategori yang sesuai dengan produk.',
        'digital' => 'Tipe Produk',
        'digital_helper' => 'Tentukan tipe produk apakah produk fisik atau digital.',
        'physical_product' => 'Produk Fisik',
        'digital_product' => 'Produk Digital',
        'digital_url' => 'URL Produk Digital',
        'digital_url_placeholder' => 'https://urlweb.com/path/to/file/download.zip',
        'digital_url_helper' => 'Masukkan alamat URL untuk akses produk bagi pembeli, seperti URL unduhan file jika produk berupa file dan lainnya.',
        'name' => 'Nama Produk',
        'name_helper' => 'Nama produk minimal 15 huruf/karakter.',
        'description' => 'Deskripsi Produk',
        'description_helper' => 'Tambahkan deskripsi produk untuk memudahkan pembeli memahami produk yang dijual.',
        'min_order' => 'Minimal Pesanan',
        'min_order_helper' => 'Hanya masukkan angka. Contoh: 50',
        'sale_price' => 'Harga Sebelum Diskon',
        'sale_price_helper' => 'Harga normal sebelum diskon. Hanya masukkan angka saja. Contoh: 100000',
        'price' => 'Harga Normal',
        'price_helper' => 'Harga normal setelah diskon. Hanya masukkan angka saja. Contoh: 100000',
        'price_per_item' => 'Harga per item',
        'afiliate_price' => 'Komisi Penjualan',
        'afiliate_price_helper' => 'Komisi penjualan yang diberikan kepada afiliator. Hanya masukkan angka. Contoh: 10000',
        'weight' => 'Berat Produk (Gram)',
        'weight_helper' => 'Hanya masukkan angka. Contoh: 1000',
        'warehouse_id' => 'Gudang Pengiriman',
        'warehouse_id_helper' => 'Pilih gudang tempat produk akan dikirim dari, untuk mengubah gudang dapat dilihat di menu **<a href="/admin/setting/warehouse" target="_blank">Lokasi Gudang</a>**',
        'stock' => 'Stok',
        'stock_helper' => 'Hanya masukkan angka. Contoh: 50',
        'security_stock' => 'Stok Pengaman',
        'security_stock_helper' => 'Stok pengaman adalah batas stok untuk produk Anda yang akan memberi tahu Anda jika stok produk akan segera habis.',
        'tags' => 'Tag Produk',
        'tags_placeholder' => 'Contoh: elektronik, telepon, laptop',
        'other_settings' => 'Pengaturan Lainnya',
        'is_active' => 'Status Produk',
        'is_active_helper' => 'Publikasikan produk atau simpan sebagai draf.',
        'question' => 'Pertanyaan',
        'answer' => 'Jawaban',
        'min_qty' => 'Minimal Jumlah',
        'sku' => 'SKU',
        'variant' => 'Varian',
        'sub_variant' => 'Sub Varian',
        'reseller_id' => 'Level Reseller',
        'wholesale' => 'Grosir',
        'back' => 'Kembali'
    ],

    'columns' => [
        'name' => 'Nama',
        'category_name' => 'Kategori',
        'code' => 'Kode Produk',
        'stock' => 'Stok',
        'weight' => 'Berat',
        'price' => 'Harga',
        'sale_price' => 'Harga Diskon',
        'afiliate_price' => 'Komisi',
        'min_order' => 'Min Pesan',
        'variation' => 'Varian',
        'subvariation' => 'Sub Varian',
        'is_featured' => 'Dipublikasi',
        'published' => 'Dipublikasi',
        'created_at' => 'Dibuat',
        'updated_at' => 'Diperbarui',
        'created_from' => 'Dibuat pada',
        'created_until' => 'Sampai',
        'products_prefix' => 'Produk: ',
    ],

    'notifications' => [
        'published_updated' => 'Status publikasi berhasil diperbarui',
        'wholesale_submited' => 'Harga grosir berhasil diajukan',
        'wholesale_failed' => 'Gagal membuat harga grosir',
        'sale_price_error' => 'Harga sebelum diskon harus lebih besar dari harga normal.',
        'generate_code' => 'Generate Kode',
    ],

    'back' => 'Kembali',

    'status' => [
        'draft' => 'Draf',
        'published' => 'Dipublikasi',
    ],

    'tabs_list' => [
        'all' => 'Semua Produk',
        'low_stock' => 'Stok Rendah',
        'out_of_stock' => 'Habis',
    ],
    'product-variant' => [
        'label' => 'Varian Produk',
        'navigation_label' => 'Varian Produk',
        'fields' => [
            'variant' => 'Varian',
            'attribute' => 'Atribut',
            'options' => 'Opsi',
            'option' => 'Opsi',
            'price' => 'Harga',
            'stock' => 'Stok',
            'image' => 'Gambar',
            'image_helper' => 'Rasio 1:1. Ukuran maksimal 1MB',
        ],
        'columns' => [
            'variant' => 'Varian',
        ],
        'notifications' => [
            'option_exists' => 'Opsi ini sudah dibuat.',
            'attribute_exists' => 'Atribut ini sudah dibuat.',
        ],
    ]
];
