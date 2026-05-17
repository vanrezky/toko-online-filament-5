<?php

return [

    'navigation_label' => 'Kategori Postingan',
    'navigation_group' => 'Blog',

    'model_label' => 'Kategori',
    'plural_model_label' => 'Kategori',

    'pages' => [
        'list' => [
            'title' => 'Daftar Kategori',
        ],
        'create' => [
            'title' => 'Buat Kategori',
        ],
        'edit' => [
            'title' => 'Edit Kategori',
        ],
    ],

    'tabs' => [
        'main' => 'Utama',
        'seo' => 'SEO',
    ],

    'fields' => [
        'name' => 'Nama',
        'description' => 'Deskripsi',
        'slug' => 'Slug',
        'is_visible' => 'Visibilitas',
        'visible_to_customers' => 'Visible ke pelanggan',
    ],

    'columns' => [
        'name' => 'Nama',
        'slug' => 'Slug',
        'is_visible' => 'Visibilitas',
        'updated_at' => 'Diperbarui',
    ],

    'notifications' => [
        'visibility_updated' => 'Status visibilitas berhasil diperbarui',
        'cannot_delete' => 'Ada kategori yang tidak dapat dihapus',
        'deleted' => 'Kategori berhasil dihapus',
    ],

];