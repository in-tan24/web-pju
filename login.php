<?php
include 'koneksi.php';

$pesan = '';
$error = '';

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function table_columns($conn, $table) {
    $columns = [];
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$table`");

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $columns[] = $row['Field'];
        }
    }

    return $columns;
}

function has_column($columns, $column) {
    return in_array($column, $columns, true);
}

$columns = table_columns($conn, 'laporan');

if (isset($_POST['kirim'])) {
    $nama = trim($_POST['nama'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
    $laporan = trim($_POST['laporan'] ?? '');
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');
    $foto = '';

    if ($nama === '' || $telepon === '' || $laporan === '') {
        $error = 'Nama, nomor telepon, dan laporan wajib diisi.';
    } else {
        if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $extension = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($extension, $allowed, true)) {
                if (!is_dir('upload')) {
                    mkdir('upload', 0777, true);
                }

                $foto = date('YmdHis') . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $_FILES['foto']['name']);
                move_uploaded_file($_FILES['foto']['tmp_name'], 'upload/' . $foto);
            } else {
                $error = 'Lampiran harus berupa gambar JPG, PNG, GIF, atau WEBP.';
            }
        }

        if ($error === '') {
            $insert = [];

            if (has_column($columns, 'nama')) {
                $insert['nama'] = $nama;
            }

            if (has_column($columns, 'telepon')) {
                $insert['telepon'] = $telepon;
            }

            if (has_column($columns, 'judul')) {
                $insert['judul'] = $nama;
            }

            if (has_column($columns, 'deskripsi')) {
                $insert['deskripsi'] = $laporan;
            }

            if (has_column($columns, 'alamat')) {
                $insert['alamat'] = $telepon;
            }

            if (has_column($columns, 'latitude')) {
                $insert['latitude'] = $latitude;
            }

            if (has_column($columns, 'longitude')) {
                $insert['longitude'] = $longitude;
            }

            if (has_column($columns, 'foto')) {
                $insert['foto'] = $foto;
            }

            $fieldSql = implode(', ', array_map(fn($field) => "`$field`", array_keys($insert)));
            $valueSql = implode(', ', array_fill(0, count($insert), '?'));
            $types = str_repeat('s', count($insert));
            $stmt = mysqli_prepare($conn, "INSERT INTO laporan ($fieldSql) VALUES ($valueSql)");

            if ($stmt) {
                mysqli_stmt_bind_param($stmt, $types, ...array_values($insert));
                mysqli_stmt_execute($stmt);
                $pesan = 'Laporan berhasil dikirim. Terima kasih sudah melapor.';
                $_POST = [];
            } else {
                $error = 'Laporan belum dapat disimpan. Periksa tabel laporan pada database.';
            }
        }
    }
}

$page = $_GET['page'] ?? (isset($_GET['input']) ? 'pengaduan' : 'beranda');
$allowedPages = ['beranda', 'data', 'pengaduan', 'kontak'];

if (!in_array($page, $allowedPages, true)) {
    $page = 'beranda';
}

$showInput = $page === 'pengaduan';
$showData = $page === 'data';
$showContact = $page === 'kontak';
$laporanData = mysqli_query($conn, "SELECT * FROM laporan ORDER BY id DESC LIMIT 6");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lampu Cirebon Kabupaten Cirebon</title>
    <link rel="stylesheet" href="assets/css/public.css">
</head>
<body>
    <main class="page">
        <section class="panel">
            <header class="topbar">
                <a class="brand" href="login.php">
                    <span class="brand-mark" aria-hidden="true">
                        <svg viewBox="0 0 64 64" role="img">
                            <path d="M22 54h20v5H22z" fill="#0b9a42"/>
                            <path d="M24 45h16v8H24z" fill="#f0c419"/>
                            <path d="M18 24c0-8 6-15 14-15s14 7 14 15c0 7-4 11-7 15H25c-3-4-7-8-7-15z" fill="#fff"/>
                            <path d="M25 39h14" stroke="#168cc1" stroke-width="4" stroke-linecap="round"/>
                            <path d="M32 4v6M15 12l5 5M49 12l-5 5" stroke="#fff" stroke-width="4" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <span>
                        <span class="brand-title">Lampu Cirebon</span>
                        <span class="brand-subtitle">Kabupaten Cirebon</span>
                    </span>
                </a>

                <nav class="nav" aria-label="Navigasi utama">
                    <a class="<?= $page === 'beranda' ? 'active' : '' ?>" href="login.php">BERANDA</a>
                    <a class="<?= $showData ? 'active' : '' ?>" href="login.php?page=data">DATA PERLENGKAPAN JALAN</a>
                    <a class="<?= $showInput ? 'active' : '' ?>" href="login.php?page=pengaduan">PENGADUAN</a>
                    <a class="<?= $showContact ? 'active' : '' ?>" href="login.php?page=kontak">KONTAK</a>
                </nav>
            </header>

            <?php if ($showInput): ?>
                <div class="section-title">
                    <h2>Formulir Pengaduan Lampu PJU</h2>
                    <div class="breadcrumb">Beranda&nbsp;&nbsp;&bull;&nbsp;&nbsp;Pengaduan</div>
                </div>

                <?php if ($pesan): ?>
                    <div class="alert ok"><?= e($pesan) ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert err"><?= e($error) ?></div>
                <?php endif; ?>

                <div class="form-grid">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="field-row">
                            <div class="field">
                                <label for="nama">Nama Lengkap *</label>
                                <input id="nama" name="nama" type="text" placeholder="Contoh : Agus Sutrisno" value="<?= e($_POST['nama'] ?? '') ?>" required>
                            </div>

                            <div class="field">
                                <label for="telepon">Nomor Whatsapp/ Telepon *</label>
                                <input id="telepon" name="telepon" type="text" placeholder="Nomor Whatsapp aktif / telepon" value="<?= e($_POST['telepon'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="field">
                            <label for="laporan">Laporan anda *</label>
                            <textarea id="laporan" name="laporan" placeholder="Contoh : Lampu PJU mati di ruas jalan depan balai desa sampai pertigaan utama Kecamatan Sumber" required><?= e($_POST['laporan'] ?? '') ?></textarea>
                        </div>

                        <div class="field">
                            <label for="foto">Lampiran file/foto</label>
                            <div class="file-control">
                                <span>⌕</span>
                                <input id="foto" name="foto" type="file" accept="image/*">
                            </div>
                            <div class="hint">Jika terdapat file/foto dapat dilampirkan sebagai file pendukung</div>
                        </div>

                        <input type="hidden" id="latitude" name="latitude">
                        <input type="hidden" id="longitude" name="longitude">

                        <button type="submit" name="kirim" class="button submit-wide">KIRIM LAPORAN</button>
                    </form>

                    <aside class="help">
                        <h3>Petunjuk Pengaduan</h3>
                        <ol>
                            <li>Anda diwajibkan untuk mengisikan kolom nama lengkap, nomor telepon, dan laporan pada formulir pengaduan.</li>
                            <li>Pelapor diharapkan untuk mengisikan laporan lokasi lampu PJU yang mati secara lengkap dan jelas guna mempercepat proses perbaikan.</li>
                            <li>Pelapor dimohon untuk mengisi formulir pengaduan dengan lengkap dan benar, data nama lengkap dan nomor telepon pelapor akan disamarkan.</li>
                            <li>Jika terdapat foto/file dapat dilampirkan sebagai file pendukung.</li>
                            <li>Perbaikan lampu PJU Kabupaten Cirebon akan dilaksanakan secepat mungkin setelah laporan kami terima.</li>
                        </ol>
                    </aside>
                </div>
            <?php elseif ($showContact): ?>
                <div class="section-title">
                    <h2>Kontak Kami</h2>
                    <div class="breadcrumb">Beranda&nbsp;&nbsp;&bull;&nbsp;&nbsp;Kontak Kami</div>
                </div>

                <div class="content-grid">
                    <div class="map-frame" aria-label="Peta lokasi otomatis">
                        <iframe
                            id="gpsMap"
                            title="Peta lokasi otomatis"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            src="https://www.openstreetmap.org/export/embed.html?bbox=108.4280%2C-6.7950%2C108.5580%2C-6.6850&amp;layer=mapnik&amp;marker=-6.7600%2C108.4850">
                        </iframe>
                        <div class="map-controls" aria-hidden="true">
                            <span class="map-button">+</span>
                            <span class="map-button">-</span>
                            <span class="map-button">&#9650;</span>
                        </div>
                        <div class="map-search" aria-hidden="true">
                            <span class="map-button">&#128269;</span>
                        </div>
                        <div id="mapStatus" class="map-status">Mengambil lokasi GPS otomatis...</div>
                    </div>

                    <aside class="contact-info">
                        <div class="contact-block">
                            <h3>Tim PJU Dinas Perhubungan Kabupaten Cirebon.</h3>
                        </div>

                        <div class="contact-block">
                            <strong>Alamat:</strong> Jl. Sunan Drajat No. 15, Sumber, Kabupaten Cirebon, Jawa Barat<br>
                            <strong>Telepon/Fax:</strong> <a href="tel:02318801234">(0231) 8801234</a><br>
                            <strong>WhatsApp:</strong> <a href="https://wa.me/6281234567890">0812-3456-7890</a><br>
                            <strong>Email:</strong> <a href="mailto:lampucirebon@gmail.com">lampucirebon@gmail.com</a>
                        </div>

                        <div class="contact-block">
                            <h4>Jam kerja</h4>
                            <strong>Senin - Kamis:</strong> 08.00 - 16.00<br>
                            <strong>Jumat:</strong> 08.00 - 11.00
                        </div>

                        <div class="socials" aria-label="Sosial media">
                            <a href="#" aria-label="Facebook">f</a>
                            <a href="#" aria-label="Twitter">t</a>
                            <a href="#" aria-label="Google Plus">g+</a>
                            <a href="#" aria-label="LinkedIn">in</a>
                            <a href="#" aria-label="Pinterest">p</a>
                        </div>
                    </aside>
                </div>
            <?php elseif ($showData): ?>
                <div class="section-title">
                    <h2>Data Perlengkapan Jalan</h2>
                    <div class="breadcrumb">Beranda&nbsp;&nbsp;&bull;&nbsp;&nbsp;Data Perlengkapan Jalan</div>
                </div>

                <div class="data-content">
                    <div class="data-cards">
                        <div class="data-card">
                            <h3>Total Titik PJU</h3>
                            <strong>1.248</strong>
                        </div>
                        <div class="data-card">
                            <h3>Unit Berfungsi</h3>
                            <strong>1.096</strong>
                        </div>
                        <div class="data-card">
                            <h3>Perlu Perbaikan</h3>
                            <strong>152</strong>
                        </div>
                    </div>

                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Ruas Jalan</th>
                                <th>Kecamatan</th>
                                <th>Jumlah PJU</th>
                                <th>Kondisi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Jalan Sunan Drajat</td>
                                <td>Sumber</td>
                                <td>86 titik</td>
                                <td>Baik</td>
                            </tr>
                            <tr>
                                <td>Jalan Tuparev</td>
                                <td>Kedawung</td>
                                <td>124 titik</td>
                                <td>Perawatan berkala</td>
                            </tr>
                            <tr>
                                <td>Jalan Fatahillah</td>
                                <td>Weru</td>
                                <td>98 titik</td>
                                <td>Baik</td>
                            </tr>
                            <tr>
                                <td>Jalan Pantura Cirebon</td>
                                <td>Arjawinangun</td>
                                <td>156 titik</td>
                                <td>Perlu pengecekan</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="home-hero">
                    <div class="home-content">
                        <div class="home-badges">
                            <span class="badge-shield">CBN</span>
                            <span class="badge-bulb" aria-hidden="true">
                                <svg viewBox="0 0 64 64" role="img">
                                    <path d="M22 54h20v5H22z" fill="#0b9a42"/>
                                    <path d="M24 45h16v8H24z" fill="#f0c419"/>
                                    <path d="M18 24c0-8 6-15 14-15s14 7 14 15c0 7-4 11-7 15H25c-3-4-7-8-7-15z" fill="#fff"/>
                                    <path d="M25 39h14" stroke="#168cc1" stroke-width="4" stroke-linecap="round"/>
                                </svg>
                            </span>
                        </div>
                        <h1>Lampu Cirebon</h1>
                        <p>
                            Lampu Cirebon adalah sistem informasi yang memfasilitasi masyarakat untuk menyampaikan aspirasi dan pengaduan terkait fasilitas penerangan jalan umum (PJU) agar perbaikan dan pelayanan dapat lebih cepat dan tepat.
                        </p>
                    </div>
                </div>

                <div class="home-actions">
                    <a class="home-action" href="login.php?page=data">
                        <span class="action-circle">||</span>
                        <h2>Data Perlengkapan Jalan</h2>
                        <p>Database perlengkapan jalan Kabupaten Cirebon</p>
                    </a>

                    <a class="home-action" href="login.php?page=pengaduan">
                        <span class="action-circle">!</span>
                        <h2>Pengaduan</h2>
                        <p>Pengaduan lampu PJU bermasalah / mati</p>
                    </a>
                </div>
            <?php endif; ?>
        </section>

        <?php if (false && !$showInput): ?>
            <section class="cards" aria-label="Daftar pengaduan terbaru">
                <?php if ($laporanData && mysqli_num_rows($laporanData) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($laporanData)): ?>
                        <?php
                            $nama = $row['nama'] ?? ($row['judul'] ?? 'Pelapor');
                            $tanggal = $row['created_at'] ?? ($row['tanggal'] ?? ($row['tgl_laporan'] ?? ''));
                            $deskripsi = $row['deskripsi'] ?? '';
                            $foto = $row['foto'] ?? '';
                            $fotoPath = $foto !== '' ? 'upload/' . $foto : '';
                        ?>
                        <article class="report-card">
                            <div class="report-head">
                                <span>♟ <?= e($nama) ?></span>
                                <span>▣ <?= e($tanggal ?: date('d-m-Y')) ?></span>
                            </div>

                            <?php if ($fotoPath && file_exists($fotoPath)): ?>
                                <img class="report-image" src="<?= e($fotoPath) ?>" alt="Foto laporan <?= e($nama) ?>">
                            <?php else: ?>
                                <div class="report-image"></div>
                            <?php endif; ?>

                            <p class="report-text"><?= e(mb_strimwidth($deskripsi, 0, 145, '...')) ?></p>
                        </article>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty">Belum ada laporan yang ditampilkan.</div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>

    <script>
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                var latitude = document.getElementById('latitude');
                var longitude = document.getElementById('longitude');
                var gpsMap = document.getElementById('gpsMap');
                var mapStatus = document.getElementById('mapStatus');
                var lat = position.coords.latitude;
                var lon = position.coords.longitude;

                if (latitude && longitude) {
                    latitude.value = lat;
                    longitude.value = lon;
                }

                if (gpsMap) {
                    var offset = 0.012;
                    var bbox = [
                        lon - offset,
                        lat - offset,
                        lon + offset,
                        lat + offset
                    ].join('%2C');

                    gpsMap.src = 'https://www.openstreetmap.org/export/embed.html?bbox=' + bbox + '&layer=mapnik&marker=' + lat + '%2C' + lon;
                }

                if (mapStatus) {
                    mapStatus.textContent = 'Peta sudah terhubung ke GPS otomatis.';
                }

            }, function() {
                var mapStatus = document.getElementById('mapStatus');

                if (mapStatus) {
                    mapStatus.textContent = 'Izin lokasi belum aktif. Peta menampilkan area Kabupaten Cirebon.';
                }
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            });
        } else {
            var mapStatus = document.getElementById('mapStatus');

            if (mapStatus) {
                mapStatus.textContent = 'Browser belum mendukung GPS otomatis.';
            }
        }
    </script>
</body>
</html>

