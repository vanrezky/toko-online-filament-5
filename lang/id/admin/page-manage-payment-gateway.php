<?php

return [
    'navigation_label' => 'Gateway Pembayaran',
    'title' => 'Gateway Pembayaran',
    'tabs' => [
        'midtrans' => 'Midtrans',
        'stripe' => 'Stripe',
        'xendit' => 'Xendit',
    ],
    'sections' => [
        'credentials' => 'Kredensial',
        'configuration' => 'Konfigurasi',
        'default_settings' => 'Pengaturan Default',
    ],
    'fields' => [
        'set_active' => 'Jadikan gateway aktif',
        'set_active_helper' => 'Hanya satu gateway yang dapat aktif dalam satu waktu.',
        'server_key' => 'Server Key',
        'server_key_helper' => 'Dapatkan Server Key dari <a href="https://dashboard.midtrans.com/" target="_blank" class="text-primary-600 underline">Dashboard Midtrans</a>.',
        'client_key' => 'Client Key',
        'merchant_id' => 'ID Merchant',
        'mode' => 'Mode',
        'sandbox' => 'Sandbox (Pengujian)',
        'production' => 'Produksi (Live)',
        'test' => 'Mode Pengujian',
        'live' => 'Mode Live',
        'supported_currencies' => 'Mata Uang yang Didukung',
        'api_key' => 'API Key',
        'api_key_helper' => 'Dapatkan API Key dari <a href="https://dashboard.stripe.com/apikeys" target="_blank" class="text-primary-600 underline">Dashboard Stripe</a>.',
        'xendit_api_key_helper' => 'Dapatkan API Key dari <a href="https://dashboard.xendit.co/settings/developers" target="_blank" class="text-primary-600 underline">Dashboard Xendit</a>.',
        'webhook_secret' => 'Webhook Secret',
        'webhook_secret_helper' => 'Digunakan untuk memverifikasi signature webhook.',
        'secret_key' => 'Secret Key',
        'default_currency' => 'Mata Uang Default',
    ],
    'notifications' => [
        'saved' => 'Pengaturan gateway pembayaran berhasil disimpan.',
    ],
];
