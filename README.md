# SISGIZI

SISGIZI adalah Sistem Informasi Status Gizi Siswa SD Patemon 02. Aplikasi ini digunakan untuk mengelola data siswa, mencatat pengukuran antropometri, melihat status gizi, membandingkan kondisi antar kelas, dan membuat laporan.

## Fitur

- Login administrator berbasis session.
- Dashboard statistik siswa dan status gizi.
- Data siswa agregat per kelas.
- Daftar siswa individual.
- Tambah, edit, dan hapus data siswa.
- Pencatatan berat badan, tinggi badan, IMT, dan status gizi.
- Analisis distribusi status gizi.
- Perbandingan status gizi antar kelas.
- Laporan berdasarkan periode tanggal.
- Halaman cetak laporan yang dapat disimpan sebagai PDF melalui browser.
- Halaman pengaturan sekolah, periode pengukuran, dan akun pengguna.

## Kebutuhan Sistem

- Windows dengan XAMPP.
- Apache.
- MySQL atau MariaDB.
- PHP 8.0 atau lebih baru.
- Browser modern seperti Chrome, Edge, atau Firefox.

## Instalasi

1. Salin folder proyek ke:

   ```text
   C:\xampp\htdocs\SKM
   ```

2. Jalankan Apache dan MySQL dari XAMPP Control Panel.

3. Buka phpMyAdmin melalui:

   ```text
   http://localhost/phpmyadmin
   ```

4. Import file berikut:

   ```text
   config/db.sql
   ```

   File tersebut akan membuat database `sisgizi`, tabel aplikasi, kelas I-VI, dan akun admin awal.

5. Pastikan konfigurasi database pada `config/db.php` sesuai dengan XAMPP:

   ```php
   $host = "localhost";
   $user = "root";
   $password = "";
   $database = "sisgizi";
   ```

6. Buka aplikasi melalui:

   ```text
   http://localhost:8080/SKM/
   ```

   Jika Apache menggunakan port 80, gunakan:

   ```text
   http://localhost/SKM/
   ```

## Akun Demo

```text
Username: admin
Password: admin123
```

Password pada database menggunakan hash bcrypt dan diverifikasi dengan `password_verify()`.

## Halaman Utama

| Halaman | URL |
| --- | --- |
| Login | `/SKM/login.php` |
| Dashboard | `/SKM/dashboard.php` |
| Ringkasan data siswa | `/SKM/DataSiswa.php` |
| Daftar siswa individual | `/SKM/siswa/index.php` |
| Analisis status gizi | `/SKM/analisis/index.php` |
| Perbandingan antar kelas | `/SKM/analisis/index.php?tab=comparison` |
| Laporan | `/SKM/laporan/index.php` |
| Pengaturan | `/SKM/pengaturan/index.php` |

## Struktur Direktori

```text
SKM/
├── analisis/          Halaman analisis status gizi
├── assets/
│   ├── css/           Stylesheet setiap halaman
│   └── image/         Logo sekolah dan aset ilustrasi
├── config/
│   ├── db.php         Koneksi database
│   └── db.sql         Struktur dan data awal database
├── laporan/           Halaman laporan dan cetak laporan
├── pengaturan/        Halaman pengaturan aplikasi
├── proses/            Proses autentikasi
├── siswa/             Modul data siswa dan CRUD
├── dashboard.php      Dashboard administrator
├── DataSiswa.php      Ringkasan siswa per kelas
├── index.php          Pengarah halaman awal
├── login.php          Halaman login
└── logout.php         Proses keluar dari aplikasi
```

## Data Dummy

Untuk kebutuhan demo, database dapat diisi dengan 40 siswa per kelas menggunakan data dummy. Seeder sebaiknya dijalankan melalui CLI dan tidak diekspos sebagai halaman web.

Data dummy yang digunakan pada lingkungan pengembangan berisi:

- 240 siswa.
- 40 siswa untuk setiap kelas I-VI.
- 240 pengukuran.
- Status gizi Gizi Baik, Berisiko, dan Masalah Gizi.

## Catatan Pengembangan

- Modul menggunakan PHP native dan MySQL/MariaDB.
- CSS halaman dipisahkan ke folder `assets/css`.
- Semua halaman internal memerlukan session administrator.
- Tombol Download PDF membuka halaman cetak browser. Pilih printer `Save as PDF` untuk menyimpan file PDF.
- Perubahan data siswa menggunakan transaksi database.
- Penghapusan siswa menghapus pengukuran terkait melalui foreign key `ON DELETE CASCADE`.

## Troubleshooting

### Halaman kosong

Pastikan URL mengarah ke folder proyek yang benar dan `index.php` tersedia. Coba akses:

```text
http://localhost:8080/SKM/
```

### Login gagal

Pastikan database `sisgizi` sudah diimport dan akun `admin` tersedia. Periksa juga konfigurasi pada `config/db.php`.

### CSS tidak berubah

Muat ulang halaman dengan `Ctrl + F5` untuk menghapus cache stylesheet browser.

### Koneksi database gagal

Pastikan MySQL/MariaDB aktif di XAMPP dan nama database pada `config/db.php` adalah `sisgizi`.

## Status Proyek

Proyek ini merupakan aplikasi administrasi dan analisis status gizi siswa yang masih dikembangkan. Fitur pengaturan sekolah, periode, dan akun saat ini sudah memiliki tampilan dasar dan dapat dikembangkan lebih lanjut menjadi form yang sepenuhnya dapat diedit.
