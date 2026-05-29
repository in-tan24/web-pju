
# Sistem Pelaporan Lampu Jalan Mati

Project ini adalah aplikasi web sederhana untuk pelaporan lampu PJU mati, pengelolaan laporan oleh admin, dan tracking pekerjaan teknisi.

## Fitur

- Form pengaduan masyarakat tanpa login.
- Lokasi pelapor via GPS otomatis.
- Upload foto lampu.
- Dashboard admin.
- Assign laporan ke teknisi.
- Tracking teknisi, status pekerjaan, lokasi terakhir, catatan, foto sebelum/sesudah.
- Grafik pelaporan berdasarkan status.
- Halaman data perlengkapan jalan dan kontak.

## Struktur Folder

```text
pju/
├── assets/
│   └── css/
│       ├── app.css
│       └── public.css
├── upload/
├── dashboard_admin.php
├── grafik.php
├── koneksi.php
├── laporan.php
├── login.php
├── schema_teknisi.php
├── teknisi_dashboard.php
└── tracking.php
```

## Halaman Utama

- `login.php`  
  Halaman publik: beranda, data perlengkapan jalan, pengaduan, dan kontak.

- `dashboard_admin.php`  
  Dashboard admin untuk melihat laporan, membuat teknisi, assign teknisi, memberi estimasi, dan memantau tracking.

- `teknisi_dashboard.php`  
  Dashboard teknisi tanpa login untuk update progres, lokasi, foto, dan catatan pekerjaan.

- `tracking.php`  
  Halaman monitoring tracking teknisi.

- `grafik.php`  
  Grafik jumlah laporan berdasarkan status.

- `laporan.php`  
  Form laporan sederhana versi Bootstrap.

## CSS

CSS custom sudah dipisahkan ke folder:

- `assets/css/public.css` untuk halaman publik `login.php`.
- `assets/css/app.css` untuk halaman berbasis Bootstrap seperti admin, teknisi, tracking, grafik, dan laporan.

## Cara Menjalankan

1. Simpan folder project di:

```text
C:\xampp\htdocs\pju
```

2. Jalankan Apache dan MySQL dari XAMPP.

3. Pastikan database di `koneksi.php` sesuai:

```php
$conn = mysqli_connect("localhost","root","","sistem_pju");
```

4. Buka di browser:

```text
http://localhost/pju/login.php
```

## Catatan Database

File `schema_teknisi.php` akan menambahkan kolom pendukung tracking teknisi secara otomatis jika belum ada, seperti:

- `assigned_at`
- `estimasi_selesai`
- `latitude`
- `longitude`
- `foto_sebelum`
- `foto_sesudah`
- `catatan`
- `estimasi_menit`
