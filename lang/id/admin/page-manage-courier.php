<?php

return [
    'navigation_label' => 'Kelola Kurir',
    'navigation_group' => 'Sistem',

    'tabs' => [
        'rajaongkir' => 'RajaOngkir',
        'apicoid' => 'ApiCoId',
        'kurir_toko' => 'Kurir Toko',
    ],

    'fields' => [
        'rajaongkir_api_key' => 'API Key (Free/Basic/Starter)',
        'rajaongkir_api_type' => 'Tipe API',
        'rajaongkir_base_url' => 'Base URL',
        'rajaongkir_api_key_pro' => 'API Key (Pro)',
        'rajaongkir_base_url_pro' => 'Base URL (Pro)',
        'apicoid_api_key' => 'API Key',
        'apicoid_base_url' => 'Base URL',
        'kurir_toko_price' => 'Tarif Pengiriman Kurir Toko',
        'default_courier' => 'Kurir Default',
        'general' => 'Umum',
    ],

    'notifications' => [
        'settings_saved' => 'Pengaturan berhasil disimpan',
        'status_updated' => 'Status berhasil diperbarui',
    ],

    'links' => [
        'rajaongkir_get_key' => 'Dapatkan API key di <a href="https://rajaongkir.com/" target="_blank" class="text-primary-600 underline">rajaongkir.com</a>',
        'apicoid_get_key' => 'Dapatkan API key di <a href="https://api.co.id/" target="_blank" class="text-primary-600 underline">api.co.id</a>',
    ],

    'view' => [
        'save_settings' => 'Simpan Pengaturan',
        'available_couriers' => 'Kurir Tersedia',
        'toggle_status_description' => 'Ubah status untuk mengaktifkan atau menonaktifkan kurir saat checkout.',
        'activate_courier' => 'Aktifkan :courier',
        'deactivate_courier' => 'Nonaktifkan :courier',
    ],
];
