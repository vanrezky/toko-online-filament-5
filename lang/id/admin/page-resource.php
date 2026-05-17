<?php

return [

    'navigation_label' => 'Halaman',
    'navigation_group' => 'Blog',

    'model_label' => 'Halaman',
    'plural_model_label' => 'Halaman',

    'pages' => [
        'list' => [
            'title' => 'Daftar Halaman',
        ],
        'create' => [
            'title' => 'Buat Halaman',
        ],
        'edit' => [
            'title' => 'Edit Halaman',
        ],
    ],

    'tabs' => [
        'title_and_content' => 'Judul & Konten',
        'seo' => 'SEO',
        'visibility' => 'Visibilitas',
        'image' => 'Gambar',
    ],

    'fields' => [
        'title' => 'Judul',
        'slug' => 'Slug',
        'content' => 'Konten',
        'parent_page' => 'Halaman Induk',
        'order' => 'Urutan',
        'status' => 'Status',
        'show_in_menu' => 'Tampilkan di Menu',
        'menu_location' => 'Lokasi Menu',
        'published_at' => 'Tanggal Publikasi',
        'featured_image' => 'Gambar Unggulan',
    ],

    'columns' => [
        'title' => 'Judul',
        'status' => 'Status',
        'published_at' => 'Dipublikasi',
        'created_at' => 'Dibuat',
        'updated_at' => 'Diperbarui',
    ],

    'status' => [
        'draft' => 'Draf',
        'published' => 'Dipublikasi',
    ],

    'menu_location' => [
        'header' => 'Header',
        'footer' => 'Footer',
        'both' => 'Keduanya',
    ],

    'placeholders' => [
        'page_title' => 'Judul halaman',
        'page_content' => 'Konten halaman',
    ],

    'helpers' => [
        'publish_draft' => 'Publikasi halaman atau simpan sebagai draf.',
        'published_date' => 'Jika dipublikasi, halaman akan terlihat pada tanggal ini.',
    ],

];