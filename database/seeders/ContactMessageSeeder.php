<?php

namespace Database\Seeders;

use App\Models\ContactMessage;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ContactMessageSeeder extends Seeder
{
    public function run(): void
    {
        $messages = [
            [
                'name' => 'Budi Santoso',
                'email' => 'budi.santoso@gmail.com',
                'subject' => 'Pertanyaan tentang pengiriman',
                'message' => 'Halo, saya ingin bertanya berapa lama waktu pengiriman ke Surabaya? Saya sudah melakukan pemesanan kemarin dan belum mendapatkan informasi tracking. Terima kasih.',
                'is_read' => true,
                'read_at' => Carbon::now()->subDays(2)->subHours(3),
            ],
            [
                'name' => 'Dewi Kusuma',
                'email' => 'dewi.kusuma@yahoo.com',
                'subject' => 'Produk tidak sesuai gambar',
                'message' => 'Selamat siang, saya menerima pesanan saya hari ini namun produk yang datang tidak sesuai dengan gambar di website. Apakah bisa dilakukan penukaran? Mohon bantuannya.',
                'is_read' => true,
                'read_at' => Carbon::now()->subDays(1)->subHours(5),
            ],
            [
                'name' => 'Ahmad Fauzi',
                'email' => 'ahmad.fauzi@outlook.co.id',
                'subject' => 'Request produk baru',
                'message' => 'Halo admin, apakah ada rencana untuk menjual produk elektronik seperti power bank dan charger? Saya tertarik untuk membeli dalam jumlah besar untuk keperluan kantor. Mohon informasinya.',
                'is_read' => false,
                'read_at' => null,
            ],
            [
                'name' => 'Siti Aminah',
                'email' => 'siti.aminah@hotmail.com',
                'subject' => 'Kendala saat checkout',
                'message' => 'Saya mengalami masalah saat melakukan checkout. Setelah memilih metode pembayaran Midtrans, halaman tidak merespons. Sudah saya coba beberapa kali tetap sama. Mohon bantuannya.',
                'is_read' => true,
                'read_at' => Carbon::now()->subDays(3)->subHours(1),
            ],
            [
                'name' => 'Rudi Hartono',
                'email' => 'rudi.hartono@gmail.com',
                'subject' => 'Konfirmasi pembayaran',
                'message' => 'Saya sudah melakukan pembayaran via transfer bank BCA sebesar Rp 1.250.000 untuk order #TRX-12345. Mohon konfirmasinya. Saya lampirkan bukti transfer di email ini.',
                'is_read' => false,
                'read_at' => null,
            ],
            [
                'name' => 'Maya Wulandari',
                'email' => 'maya.wulandari@gmail.com',
                'subject' => 'Testimoni dan saran',
                'message' => 'Halo, saya ingin memberikan testimoni bahwa produk yang saya beli sangat bagus dan sesuai ekspektasi. Namun, saya saran agar packaging bisa lebih safety untuk produk fragile. Terima kasih!',
                'is_read' => true,
                'read_at' => Carbon::now()->subDays(5)->subHours(2),
            ],
            [
                'name' => 'Indra Permana',
                'email' => 'indra.permana@yahoo.co.id',
                'subject' => 'Voucher tidak berfungsi',
                'message' => 'Saya memiliki voucher diskon 20% dengan kode DISKON20, namun saat saya coba gunakan di checkout muncul notifikasi voucher tidak valid. Padahal masa berlakunya masih sampai bulan depan. Mohon pencerahannya.',
                'is_read' => false,
                'read_at' => null,
            ],
            [
                'name' => 'Lisa Anggraini',
                'email' => 'lisa.anggraini@gmail.com',
                'subject' => 'Pertanyaan stok produk',
                'message' => 'Apakah produk tas laptop warna hitam masih tersedia? Saya lihat di website masih ada tapi saat mau checkout muncul notifikasi stok habis. Mohon informasi update stoknya. Terima kasih.',
                'is_read' => true,
                'read_at' => Carbon::now()->subDays(1)->subHours(8),
            ],
            [
                'name' => 'Adi Nugroho',
                'email' => 'adi.nugroho@outlook.com',
                'subject' => 'Permintaan invoice',
                'message' => 'Selamat pagi, saya membutuhkan invoice resmi untuk pengajuan reimbursement dari kantor. Order ID saya adalah #TRX-67890. Bisa dikirimkan ke email ini? Terima kasih atas bantuannya.',
                'is_read' => false,
                'read_at' => null,
            ],
            [
                'name' => 'Rina Fitriani',
                'email' => 'rina.fitriani@hotmail.co.id',
                'subject' => 'Pengembalian barang',
                'message' => 'Saya ingin mengajukan pengembalian barang karena ukuran tidak sesuai. Saya sudah baca policy pengembalian 7 hari. Apakah prosesnya bisa dilakukan via kurir atau harus datang langsung ke kantor?',
                'is_read' => true,
                'read_at' => Carbon::now()->subDays(4)->subHours(6),
            ],
            [
                'name' => 'Dedi Kurniawan',
                'email' => 'dedi.kurniawan@gmail.com',
                'subject' => 'Kerjasama reseller',
                'message' => 'Halo, saya memiliki toko offline di Malang dan tertarik untuk menjadi reseller produk-produk Anda. Apakah ada program reseller dengan harga khusus? Mohon informasi lebih lanjut. Terima kasih.',
                'is_read' => false,
                'read_at' => null,
            ],
            [
                'name' => 'Nina Sari',
                'email' => 'nina.sari@yahoo.com',
                'subject' => 'Keluhan pelayanan kurir',
                'message' => 'Saya kecewa dengan pelayanan kurir yang mengantarkan paket saya. Paket ditinggal di depan rumah tanpa konfirmasi dan kondisi packaging sudah rusak. Mohon tindak lanjutnya.',
                'is_read' => true,
                'read_at' => Carbon::now()->subDays(2)->subHours(1),
            ],
            [
                'name' => 'Fajar Pratama',
                'email' => 'fajar.pratama@gmail.com',
                'subject' => 'Update data alamat',
                'message' => 'Saya sudah mengupdate alamat pengiriman di profil saya, namun saat checkout alamat yang muncul masih alamat lama. Apakah perlu verifikasi terlebih dahulu? Mohon bantuannya.',
                'is_read' => false,
                'read_at' => null,
            ],
            [
                'name' => 'Yuli Astuti',
                'email' => 'yuli.astuti@outlook.co.id',
                'subject' => 'Pertanyaan garansi produk',
                'message' => 'Apakah produk elektronik yang dijual disini memiliki garansi resmi? Saya tertarik membeli smartwatch tapi ingin memastikan garansinya. Berapa lama masa garansi yang diberikan?',
                'is_read' => true,
                'read_at' => Carbon::now()->subDays(6)->subHours(4),
            ],
            [
                'name' => 'Hendra Wijaya',
                'email' => 'hendra.wijaya@gmail.com',
                'subject' => 'Masalah login akun',
                'message' => 'Saya tidak bisa login ke akun saya. Sudah mencoba reset password tapi email reset tidak masuk ke inbox saya. Apakah ada masalah dengan sistem? Mohon bantuannya.',
                'is_read' => false,
                'read_at' => null,
            ],
        ];

        foreach ($messages as $index => $message) {
            ContactMessage::create([
                'name' => $message['name'],
                'email' => $message['email'],
                'subject' => $message['subject'],
                'message' => $message['message'],
                'is_read' => $message['is_read'],
                'read_at' => $message['read_at'],
                'created_at' => Carbon::now()->subDays($index + 1)->subHours(rand(1, 12)),
                'updated_at' => Carbon::now()->subDays($index + 1)->subHours(rand(1, 12)),
            ]);
        }
    }
}
