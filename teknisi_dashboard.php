<?php
include 'koneksi.php';
include 'schema_teknisi.php';

ensure_teknisi_schema($conn);

$pesan = '';
$error = '';

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function upload_teknisi_file($field) {
    if (empty($_FILES[$field]['name']) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return '';
    }

    $extension = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($extension, $allowed, true)) {
        return '';
    }

    if (!is_dir('upload')) {
        mkdir('upload', 0777, true);
    }

    $name = date('YmdHis') . '_' . $field . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $_FILES[$field]['name']);
    move_uploaded_file($_FILES[$field]['tmp_name'], 'upload/' . $name);
    return $name;
}

$teknisiList = [];
$teknisiResult = mysqli_query($conn, "SELECT id, nama FROM users WHERE role='teknisi' ORDER BY nama");

if ($teknisiResult) {
    while ($row = mysqli_fetch_assoc($teknisiResult)) {
        $teknisiList[] = $row;
    }
}

$teknisiId = (int) ($_GET['teknisi_id'] ?? ($_POST['teknisi_id_aktif'] ?? 0));

if ($teknisiId === 0 && $teknisiList) {
    $teknisiId = (int) $teknisiList[0]['id'];
}

$teknisi = null;

foreach ($teknisiList as $item) {
    if ((int) $item['id'] === $teknisiId) {
        $teknisi = $item;
        break;
    }
}

if (isset($_POST['live_location'])) {
    header('Content-Type: application/json');

    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');
    $laporanId = (int) ($_POST['laporan_id'] ?? 0);

    if ($teknisiId > 0 && $latitude !== '' && $longitude !== '' && $laporanId > 0) {
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO tracking_teknisi (laporan_id, teknisi_id, status_tracking, latitude, longitude, catatan)
             VALUES (?, ?, 'lokasi realtime', ?, ?, 'Update lokasi otomatis teknisi')"
        );

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'iiss', $laporanId, $teknisiId, $latitude, $longitude);
            mysqli_stmt_execute($stmt);
        }
    }

    echo json_encode(['ok' => true]);
    exit;
}

if (isset($_POST['aksi_teknisi'])) {
    $laporanId = (int) ($_POST['laporan_id'] ?? 0);
    $aksi = $_POST['aksi'] ?? '';
    $catatan = trim($_POST['catatan'] ?? '');
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');
    $estimasiMenit = (int) ($_POST['estimasi_menit'] ?? 0);
    $valid = ['terima', 'tolak', 'menuju lokasi', 'dikerjakan', 'selesai'];

    if ($teknisiId <= 0) {
        $error = 'Data teknisi belum tersedia. Tambahkan data teknisi dari dashboard admin.';
    } elseif ($laporanId <= 0 || !in_array($aksi, $valid, true)) {
        $error = 'Aksi tugas tidak valid.';
    } else {
        $fotoSebelum = upload_teknisi_file('foto_sebelum');
        $fotoSesudah = upload_teknisi_file('foto_sesudah');

        if ($aksi === 'tolak') {
            $stmt = mysqli_prepare($conn, "UPDATE laporan SET status='menunggu', teknisi_id=NULL WHERE id=? AND teknisi_id=?");
            mysqli_stmt_bind_param($stmt, 'ii', $laporanId, $teknisiId);
            mysqli_stmt_execute($stmt);
        } elseif ($aksi === 'selesai') {
            $stmt = mysqli_prepare($conn, "UPDATE laporan SET status='selesai' WHERE id=? AND teknisi_id=?");
            mysqli_stmt_bind_param($stmt, 'ii', $laporanId, $teknisiId);
            mysqli_stmt_execute($stmt);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE laporan SET status='diproses' WHERE id=? AND teknisi_id=?");
            mysqli_stmt_bind_param($stmt, 'ii', $laporanId, $teknisiId);
            mysqli_stmt_execute($stmt);
        }

        $stmtTracking = mysqli_prepare(
            $conn,
            "INSERT INTO tracking_teknisi (laporan_id, teknisi_id, status_tracking, latitude, longitude, foto_sebelum, foto_sesudah, catatan, estimasi_menit)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if ($stmtTracking) {
            mysqli_stmt_bind_param(
                $stmtTracking,
                'iissssssi',
                $laporanId,
                $teknisiId,
                $aksi,
                $latitude,
                $longitude,
                $fotoSebelum,
                $fotoSesudah,
                $catatan,
                $estimasiMenit
            );
            mysqli_stmt_execute($stmtTracking);
        }

        $pesan = 'Progres tugas berhasil diperbarui.';
    }
}

$tugas = false;
$riwayat = false;
$totalTugas = 0;
$selesai = 0;
$aktif = 0;

if ($teknisiId > 0) {
    $tugas = mysqli_query(
        $conn,
        "SELECT laporan.*,
                latest.status_tracking AS tracking_terakhir,
                latest.catatan AS catatan_terakhir,
                latest.updated_at AS waktu_tracking
         FROM laporan
         LEFT JOIN (
            SELECT t1.*
            FROM tracking_teknisi t1
            INNER JOIN (
                SELECT laporan_id, MAX(id) AS id
                FROM tracking_teknisi
                GROUP BY laporan_id
            ) t2 ON t1.id = t2.id
         ) latest ON latest.laporan_id = laporan.id
         WHERE laporan.teknisi_id=$teknisiId AND laporan.status <> 'selesai'
         ORDER BY laporan.assigned_at DESC, laporan.id DESC"
    );

    $riwayat = mysqli_query(
        $conn,
        "SELECT laporan.*, MAX(tracking_teknisi.updated_at) AS selesai_waktu
         FROM laporan
         LEFT JOIN tracking_teknisi ON tracking_teknisi.laporan_id = laporan.id
         WHERE laporan.teknisi_id=$teknisiId AND laporan.status='selesai'
         GROUP BY laporan.id
         ORDER BY selesai_waktu DESC"
    );

    $totalTugas = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM laporan WHERE teknisi_id=$teknisiId"));
    $selesai = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM laporan WHERE teknisi_id=$teknisiId AND status='selesai'"));
    $aktif = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM laporan WHERE teknisi_id=$teknisiId AND status<>'selesai'"));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Teknisi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="mb-0">Dashboard Teknisi</h2>
            <div class="text-muted"><?= e($teknisi['nama'] ?? 'Belum ada teknisi') ?></div>
        </div>
        <a href="dashboard_admin.php" class="btn btn-outline-primary">Dashboard Admin</a>
    </div>

    <?php if ($teknisiList): ?>
        <form method="GET" class="card card-body mb-4">
            <label class="form-label">Pilih Teknisi</label>
            <div class="d-flex gap-2">
                <select name="teknisi_id" class="form-select">
                    <?php foreach ($teknisiList as $item): ?>
                        <option value="<?= e($item['id']) ?>" <?= (int) $item['id'] === $teknisiId ? 'selected' : '' ?>>
                            <?= e($item['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-primary">Tampilkan</button>
            </div>
        </form>
    <?php else: ?>
        <div class="alert alert-warning">
            Belum ada data teknisi. Tambahkan teknisi dari Dashboard Admin terlebih dahulu.
        </div>
    <?php endif; ?>

    <?php if ($pesan): ?>
        <div class="alert alert-success"><?= e($pesan) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="card p-3"><div class="text-muted">Total Tugas</div><h2><?= $totalTugas ?></h2></div></div>
        <div class="col-md-4"><div class="card p-3"><div class="text-muted">Sedang Berjalan</div><h2><?= $aktif ?></h2></div></div>
        <div class="col-md-4"><div class="card p-3"><div class="text-muted">Selesai</div><h2><?= $selesai ?></h2></div></div>
    </div>

    <h4>Daftar Tugas Perbaikan</h4>
    <div class="row g-3 mb-5">
        <?php if ($tugas && mysqli_num_rows($tugas) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($tugas)): ?>
                <?php
                    $lat = $row['latitude'] ?? '';
                    $lon = $row['longitude'] ?? '';
                    $maps = ($lat && $lon) ? "https://www.google.com/maps?q=$lat,$lon" : '#';
                    $nav = ($lat && $lon) ? "https://www.google.com/maps/dir/?api=1&destination=$lat,$lon" : '#';
                ?>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between gap-2">
                                <h5><?= e($row['judul']) ?></h5>
                                <span class="badge bg-info"><?= e($row['status']) ?></span>
                            </div>
                            <p><?= e($row['deskripsi']) ?></p>
                            <div class="small text-muted mb-2"><?= e($row['alamat']) ?></div>
                            <div class="small mb-3">
                                Tracking terakhir: <?= e($row['tracking_terakhir'] ?? 'Tugas baru') ?>
                                <?php if (!empty($row['catatan_terakhir'])): ?>
                                    <br>Komentar admin: <?= e($row['catatan_terakhir']) ?>
                                <?php endif; ?>
                                <?php if (!empty($row['estimasi_selesai'])): ?>
                                    <br>Estimasi selesai: <?= e($row['estimasi_selesai']) ?>
                                <?php endif; ?>
                            </div>

                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <a class="btn btn-outline-primary btn-sm <?= $maps === '#' ? 'disabled' : '' ?>" target="_blank" href="<?= e($maps) ?>">Lihat Maps</a>
                                <a class="btn btn-outline-success btn-sm <?= $nav === '#' ? 'disabled' : '' ?>" target="_blank" href="<?= e($nav) ?>">Navigasi</a>
                            </div>

                                <form method="POST" enctype="multipart/form-data" class="d-grid gap-2 teknisi-form" data-laporan-id="<?= e($row['id']) ?>">
                                <input type="hidden" name="laporan_id" value="<?= e($row['id']) ?>">
                                <input type="hidden" name="teknisi_id_aktif" value="<?= e($teknisiId) ?>">
                                <input type="hidden" name="latitude" class="latitude">
                                <input type="hidden" name="longitude" class="longitude">

                                <select name="aksi" class="form-select form-select-sm">
                                    <option value="terima">Terima tugas</option>
                                    <option value="tolak">Tolak tugas</option>
                                    <option value="menuju lokasi">Menuju lokasi</option>
                                    <option value="dikerjakan">Update sedang dikerjakan</option>
                                    <option value="selesai">Tandai selesai</option>
                                </select>

                                <input type="number" name="estimasi_menit" class="form-control form-control-sm" min="0" placeholder="Estimasi pengerjaan dalam menit">
                                <input type="file" name="foto_sebelum" class="form-control form-control-sm" accept="image/*">
                                <input type="file" name="foto_sesudah" class="form-control form-control-sm" accept="image/*">
                                <textarea name="catatan" class="form-control form-control-sm" rows="2" placeholder="Catatan hasil perbaikan / komentar ke admin"></textarea>

                                <button type="submit" name="aksi_teknisi" class="btn btn-primary btn-sm">Kirim Update</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12"><div class="alert alert-info">Belum ada tugas baru untuk teknisi ini.</div></div>
        <?php endif; ?>
    </div>

    <h4>Riwayat Pekerjaan Teknisi</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Laporan</th>
                    <th>Lokasi</th>
                    <th>Selesai</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($riwayat && mysqli_num_rows($riwayat) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($riwayat)): ?>
                        <tr>
                            <td><?= e($row['judul']) ?></td>
                            <td><?= e($row['alamat']) ?></td>
                            <td><?= e($row['selesai_waktu'] ?? '-') ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="3" class="text-center text-muted">Belum ada pekerjaan selesai.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
if (navigator.geolocation) {
    navigator.geolocation.watchPosition(function(position) {
        var firstLaporan = document.querySelector('.teknisi-form');

        document.querySelectorAll('.teknisi-form').forEach(function(form) {
            form.querySelector('.latitude').value = position.coords.latitude;
            form.querySelector('.longitude').value = position.coords.longitude;
        });

        if (firstLaporan) {
            var body = new URLSearchParams();
            body.append('live_location', '1');
            body.append('laporan_id', firstLaporan.dataset.laporanId);
            body.append('teknisi_id_aktif', '<?= e($teknisiId) ?>');
            body.append('latitude', position.coords.latitude);
            body.append('longitude', position.coords.longitude);

            fetch('teknisi_dashboard.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: body.toString()
            });
        }
    }, null, {
        enableHighAccuracy: true,
        maximumAge: 15000,
        timeout: 10000
    });
}
</script>
</body>
</html>
