<?php

return [
    'title' => 'Tester Cloudflare R2',
    'navigation_label' => 'Tester R2',
    'actions' => [
        'test' => 'Tes Koneksi R2',
    ],
    'section' => [
        'heading' => 'Tes Cloudflare R2',
        'description' => 'Tes akan menulis, membaca, memverifikasi, dan menghapus object sementara. Credential R2 dimuat dari environment aplikasi dan tidak pernah ditampilkan di sini.',
    ],
    'notifications' => [
        'success' => 'Cloudflare R2 terhubung dan berjalan dengan baik.',
        'failed' => 'Tes koneksi Cloudflare R2 gagal.',
        'failed_body' => 'Periksa environment variable R2, bucket, endpoint, dan permission aksesnya.',
    ],
];
