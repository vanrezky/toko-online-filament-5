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
        'total_full_bills' => 'Total Tagihan Full',
    ],

    'table_headers' => [
        'id' => 'ID',
        'name' => 'Nama',
        'level' => 'Level',
        'total_deduction' => 'Total Potongan',
        'active_installments' => 'Cicilan Aktif',
        'references' => 'Referensi',
        'bill_items' => 'Rincian Tagihan',
    ],

    'actions' => [
        'preview' => 'Preview',
        'export_excel' => 'Export Excel',
        'export_draft_excel' => 'Export Draft (Excel)',
        'export_final_submit' => 'Export Final & Submit Payroll',
    ],

    'confirmations' => [
        'final_heading' => 'Submit Payroll Final',
        'final_description' => 'Anda akan mengekspor payroll final periode :month :year dengan total :customers anggota. Item full billing: :full_bills, item cicilan: :installments, total potongan: Rp :total. Aksi ini akan menandai item eligible sebagai diajukan payroll (submitted).',
    ],

    'notifications' => [
        'no_eligible_items_title' => 'Tidak ada item eligible',
        'no_eligible_items_body' => 'Semua item pada periode ini sudah submitted/paid atau tidak memenuhi syarat submit final.',
        'final_export_success_title' => 'Export final berhasil',
        'final_export_success_body' => 'Batch :batch berhasil disubmit dengan :total item.',
    ],

];
