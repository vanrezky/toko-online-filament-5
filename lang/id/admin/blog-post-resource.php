<?php

return [

    'navigation_label' => 'Postingan',
    'navigation_group' => 'Blog',

    'model_label' => 'Postingan',
    'plural_model_label' => 'Postingan',

    'pages' => [
        'list' => [
            'title' => 'Daftar Postingan',
        ],
        'create' => [
            'title' => 'Buat Postingan',
        ],
        'view' => [
            'title' => 'Detail Postingan',
        ],
        'edit' => [
            'title' => 'Edit Postingan',
        ],
    ],

    'tabs' => [
        'title_and_content' => 'Judul & Konten',
        'seo' => 'SEO',
        'tags' => 'Tag',
        'visibility' => 'Visibilitas',
        'image' => 'Gambar',
    ],

    'fields' => [
        'title' => 'Judul',
        'slug' => 'Slug',
        'content' => 'Konten',
        'category_id' => 'Kategori',
        'featured_image' => 'Gambar Unggulan',
        'tags' => 'Tag',
        'status' => 'Status',
        'published_at' => 'Tanggal Publikasi',
    ],

    'columns' => [
        'title' => 'Judul',
        'category' => 'Kategori',
        'author' => 'Penulis',
        'published_at' => 'Dipublikasi',
        'updated_at' => 'Diperbarui',
        'status' => 'Status',
    ],

    'status' => [
        'draft' => 'Draf',
        'published' => 'Dipublikasi',
    ],

    'placeholders' => [
        'post_title' => 'Judul postingan',
        'post_content' => 'Konten postingan',
        'tags_placeholder' => 'cth: elektronik, telepon, laptop',
    ],

    'helpers' => [
        'publish_draft' => 'Publikasi postingan atau simpan sebagai draf.',
        'published_date' => 'Jika dipublikasi, postingan akan terlihat pada tanggal ini.',
    ],

];