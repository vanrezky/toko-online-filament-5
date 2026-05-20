<?php

return [

    'navigation_label' => 'Lokasi Gudang',
    'navigation_group' => 'Master',

    'model_label' => 'Gudang',
    'plural_model_label' => 'Gudang',

    'pages' => [
        'list' => [
            'title' => 'Daftar Gudang',
        ],
        'create' => [
            'title' => 'Buat Gudang',
        ],
        'edit' => [
            'title' => 'Edit Gudang',
        ],
    ],

    'fields' => [
        'location' => 'Lokasi Gudang',
        'warehouse_info' => 'Informasi Gudang',
        'name' => 'Nama Gudang',
        'address' => 'Alamat',
        'contact_name' => 'Nama Kontak',
        'contact_phone' => 'Telepon Kontak',
        'courier' => 'Kurir',
        'description' => 'Deskripsi',
        'province_id' => 'Provinsi',
        'district_id' => 'Kabupaten/Kota',
        'sub_district_id' => 'Kecamatan',
        'village_id' => 'Kelurahan/Desa',
        'postal_code' => 'Kode Pos',
        'is_active' => 'Aktif',
    ],

    'columns' => [
        'name' => 'Nama',
        'contact_name' => 'Kontak',
        'contact_phone' => 'Telepon',
        'courier' => 'Kurir',
        'description' => 'Deskripsi',
        'is_active' => 'Aktif',
    ],

    'notifications' => [
        'activation_updated' => 'Status aktif berhasil diperbarui',
    ],

];