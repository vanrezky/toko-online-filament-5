<?php

return [
    'navigation_label' => 'Slider Beranda',
    'navigation_group' => 'Promo',
    'model_label' => 'Slider',
    'plural_model_label' => 'Slider',

    'pages' => [
        'list' => [
            'title' => 'Slider Beranda',
        ],
        'create' => [
            'title' => 'Buat Slider',
        ],
        'edit' => [
            'title' => 'Edit Slider',
        ],
    ],

    'sections' => [
        'carousel_content' => 'Konten Carousel',
        'publishing' => 'Penayangan',
    ],

    'descriptions' => [
        'carousel_content' => 'Konten ini ditampilkan di atas gambar pada carousel storefront.',
    ],

    'fields' => [
        'image' => 'Gambar Carousel',
        'image_helper' => 'Gunakan gambar lanskap lebar. Ukuran maksimal 2MB.',
        'eyebrow' => 'Teks Pendukung',
        'title' => 'Judul',
        'description' => 'Deskripsi',
        'button_label' => 'Teks Tombol',
        'target_link' => 'Tautan Tombol',
        'target_link_helper' => 'Gunakan path internal seperti /products, atau URL lengkap.',
        'sort_order' => 'Urutan Tampil',
        'is_active' => 'Aktif',
        'start_at' => 'Tayang Mulai',
        'end_at' => 'Tayang Sampai',
    ],

    'columns' => [
        'image' => 'Gambar',
        'title' => 'Judul',
        'title_placeholder' => 'Slider tanpa judul',
        'target_link' => 'Tautan Tombol',
        'sort_order' => 'Urutan',
        'start_at' => 'Tayang Mulai',
        'end_at' => 'Tayang Sampai',
        'is_active' => 'Aktif',
    ],
];
