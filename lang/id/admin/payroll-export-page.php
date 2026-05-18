<?php

return [

    'title' => 'Export Potongan Gaji',
    'navigation_label' => 'Export Payroll',

    'sections' => [
        'filter' => 'Filter Export',
        'summary' => 'Ringkasan',
        'details' => 'Detail Potongan per Anggota',
    ],

    'fields' => [
        'month' => 'Bulan',
        'year' => 'Tahun',
        'customer_level' => 'Level Anggota',
    ],

    'months' => [
        'january' => 'Januari',
        'february' => 'Februari',
        'march' => 'Maret',
        'april' => 'April',
        'may' => 'Mei',
        'june' => 'Juni',
        'july' => 'Juli',
        'august' => 'Agustus',
        'september' => 'September',
        'october' => 'Oktober',
        'november' => 'November',
        'december' => 'Desember',
    ],

    'summary_labels' => [
        'month' => 'Bulan',
        'total_customers' => 'Total Anggota',
        'total_deduction' => 'Total Potongan',
        'total_installments' => 'Total Cicilan Aktif',
    ],

    'table_headers' => [
        'id' => 'ID',
        'name' => 'Nama',
        'level' => 'Level',
        'total_deduction' => 'Total Potongan',
        'active_installments' => 'Cicilan Aktif',
    ],

    'actions' => [
        'preview' => 'Preview',
        'export_excel' => 'Export Excel',
    ],

];
