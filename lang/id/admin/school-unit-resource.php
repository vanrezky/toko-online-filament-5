<?php

return [
    'navigation_label' => 'Unit Sekolah',
    'navigation_group' => 'Katalog',

    'model_label' => 'Unit Sekolah',
    'plural_model_label' => 'Unit Sekolah',

    'pages' => [
        'list' => [
            'title' => 'Daftar Unit Sekolah',
        ],
        'create' => [
            'title' => 'Buat Unit Sekolah',
        ],
        'edit' => [
            'title' => 'Edit Unit Sekolah',
        ],
    ],

    'fields' => [
        'name' => 'Nama Unit Sekolah',
        'phone' => 'No. Telepon',
        'province_id' => 'Provinsi',
        'district_id' => 'Kabupaten/Kota',
        'sub_district_id' => 'Kecamatan',
        'village_id' => 'Desa/Kelurahan',
        'address' => 'Alamat',
        'postal_code' => 'Kode Pos',
    ],

    'columns' => [
        'name' => 'Nama Unit',
        'phone' => 'No. Telepon',
        'district' => 'Kab/Kota',
        'sub_district' => 'Kecamatan',
        'postal_code' => 'Kode Pos',
        'customers_count' => 'Total Customer',
    ],
];
