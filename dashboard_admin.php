<?php
include 'koneksi.php';
include 'schema_teknisi.php';

ensure_teknisi_schema($conn);

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

function count_laporan($conn, $where = '') {
    $sql = "SELECT COUNT(*) AS total FROM laporan" . ($where ? " WHERE $where" : "");
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        return 0;
    }

    $row = mysqli_fetch_assoc($result);
    return (int) ($row['total'] ?? 0);
}

$columns = table_columns($conn, 'laporan');
$hasStatus = has_column($columns, 'status');
$hasTeknisiId = has_column($columns, 'teknisi_id');

if (isset($_POST['proses_laporan'])) {
    $laporanId = (int) ($_POST['laporan_id'] ?? 0);
    $statusBaru = $_POST['status'] ?? '';
    $teknisiInput = trim($_POST['teknisi_id'] ?? '');
    $tracking = trim($_POST['tracking'] ?? '');
    $estimasiMenit = (int) ($_POST['estimasi_menit'] ?? 0);
    $statusValid = ['menunggu', 'diproses', 'selesai'];

    if ($laporanId <= 0 || !in_array($statusBaru, $statusValid, true)) {
        $error = 'Data pemrosesan laporan tidak valid.';
    } else {
        $estimasiSelesai = $estimasiMenit > 0 ? date('Y-m-d H:i:s', time() + ($estimasiMenit * 60)) : null;

        if ($hasTeknisiId && $teknisiInput !== '') {
            $teknisiId = (int) $teknisiInput;
            $stmt = mysqli_prepare($conn, "UPDATE laporan SET status=?, teknisi_id=?, assigned_at=NOW(), estimasi_selesai=? WHERE id=?");

            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'sisi', $statusBaru, $teknisiId, $estimasiSelesai, $laporanId);
                mysqli_stmt_execute($stmt);
            }
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE laporan SET status=? WHERE id=?");

            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'si', $statusBaru, $laporanId);
                mysqli_stmt_execute($stmt);
            }
        }

        $trackingText = $tracking !== '' ? $tracking : 'Status laporan diubah menjadi ' . $statusBaru;

        if ($teknisiInput !== '') {
            $teknisiId = (int) $teknisiInput;
            $stmtTracking = mysqli_prepare($conn, "INSERT INTO tracking_teknisi (laporan_id, teknisi_id, status_tracking, catatan, estimasi_menit) VALUES (?, ?, ?, ?, ?)");

            if ($stmtTracking) {
                mysqli_stmt_bind_param($stmtTracking, 'iissi', $laporanId, $teknisiId, $statusBaru, $trackingText, $estimasiMenit);
                mysqli_stmt_execute($stmtTracking);
            }
        } else {
            $stmtTracking = mysqli_prepare($conn, "INSERT INTO tracking_teknisi (laporan_id, status_tracking, catatan, estimasi_menit) VALUES (?, ?, ?, ?)");

            if ($stmtTracking) {
                mysqli_stmt_bind_param($stmtTracking, 'issi', $laporanId, $statusBaru, $trackingText, $estimasiMenit);
                mysqli_stmt_execute($stmtTracking);
            }
        }

        $pesan = 'Pemrosesan laporan dan tracking teknisi berhasil diperbarui.';
    }
}

if (isset($_POST['tambah_teknisi'])) {
    $namaTeknisi = trim($_POST['nama_teknisi'] ?? '');
    $emailTeknisi = trim($_POST['email_teknisi'] ?? '');
    $passwordTeknisi = $_POST['password_teknisi'] ?? '';

    if ($namaTeknisi === '' || $emailTeknisi === '' || $passwordTeknisi === '') {
        $error = 'Nama, email, dan password teknisi wajib diisi.';
    } else {
        $passwordHash = md5($passwordTeknisi);
        $stmt = mysqli_prepare($conn, "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, 'teknisi')");

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'sss', $namaTeknisi, $emailTeknisi, $passwordHash);

            if (mysqli_stmt_execute($stmt)) {
                $pesan = 'Akun teknisi berhasil dibuat.';
            } else {
                $error = 'Akun teknisi belum dapat dibuat. Email mungkin sudah digunakan.';
            }
        }
    }
}

$total = count_laporan($conn);
$menunggu = $hasStatus ? count_laporan($conn, "status='menunggu'") : 0;
$diproses = $hasStatus ? count_laporan($conn, "status='diproses'") : 0;
$selesai = $hasStatus ? count_laporan($conn, "status='selesai'") : 0;
$teknisi = mysqli_query($conn, "SELECT id, nama FROM users WHERE role='teknisi' ORDER BY nama");
$teknisiOptions = [];

if ($teknisi) {
    while ($row = mysqli_fetch_assoc($teknisi)) {
        $teknisiOptions[] = $row;
    }
}
$data = mysqli_query(
    $conn,
    "SELECT laporan.*, users.nama AS nama_teknisi, tracking.status_tracking AS tracking_terakhir, tracking.catatan AS tracking_catatan, tracking.updated_at AS tracking_waktu, tracking.teknisi_id AS tracking_teknisi_id, tracking.latitude AS teknisi_latitude, tracking.longitude AS teknisi_longitude
     FROM laporan
     LEFT JOIN users ON users.id = laporan.teknisi_id
     LEFT JOIN (
        SELECT t1.*
        FROM tracking_teknisi t1
        INNER JOIN (
            SELECT laporan_id, MAX(id) AS id
            FROM tracking_teknisi
            GROUP BY laporan_id
        ) t2 ON t1.id = t2.id
     ) tracking ON tracking.laporan_id = laporan.id
     ORDER BY laporan.id DESC"
);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center gap-3">
        <h2 class="mb-0">Dashboard Admin</h2>
        <div class="d-flex gap-2">
            <a href="teknisi_dashboard.php" class="btn btn-outline-success">Dashboard Teknisi</a>
            <a href="tracking.php" class="btn btn-outline-secondary">Tracking Teknisi</a>
            <a href="login.php" class="btn btn-outline-primary">Halaman Pengaduan</a>
        </div>
    </div>

    <?php if ($pesan): ?>
        <div class="alert alert-success mt-4"><?= e($pesan) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger mt-4"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="row mt-4 g-3">
        <div class="col-md-3">
            <div class="card p-3 bg-primary text-white h-100">
                <h5>Total Laporan</h5>
                <h2><?= $total ?></h2>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card p-3 bg-warning text-white h-100">
                <h5>Menunggu</h5>
                <h2><?= $menunggu ?></h2>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card p-3 bg-info text-white h-100">
                <h5>Diproses</h5>
                <h2><?= $diproses ?></h2>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card p-3 bg-success text-white h-100">
                <h5>Selesai</h5>
                <h2><?= $selesai ?></h2>
            </div>
        </div>
    </div>

    <?php if (!$hasStatus): ?>
        <div class="alert alert-warning mt-4">
            Kolom <strong>status</strong> belum ada di tabel laporan, jadi jumlah status ditampilkan 0.
        </div>
    <?php endif; ?>

    <div class="card mt-4">
        <div class="card-header bg-white">
            <strong>Tambah Akun Teknisi</strong>
        </div>
        <div class="card-body">
            <form method="POST" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Nama Teknisi</label>
                    <input type="text" name="nama_teknisi" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email_teknisi" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password_teknisi" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <button type="submit" name="tambah_teknisi" class="btn btn-success w-100">Buat Teknisi</button>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive mt-4">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-dark">
                <tr>
                    <th class="table-col-no">No</th>
                    <th>Pelapor / Judul</th>
                    <th>Laporan</th>
                    <th class="table-col-photo">Foto</th>
                    <th>Status</th>
                    <th>Tracking Teknisi</th>
                    <th>Lokasi / Kontak</th>
                    <th class="table-col-action">Pemrosesan</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($data && mysqli_num_rows($data) > 0): ?>
                    <?php $no = 1; ?>
                    <?php while ($d = mysqli_fetch_assoc($data)): ?>
                        <?php
                            $pelapor = $d['nama'] ?? ($d['judul'] ?? '-');
                            $laporan = $d['deskripsi'] ?? '-';
                            $status = $d['status'] ?? 'menunggu';
                            $kontak = $d['telepon'] ?? ($d['alamat'] ?? '-');
                            $foto = $d['foto'] ?? '';
                            $fotoPath = $foto !== '' ? 'upload/' . $foto : '';
                            $trackingTerakhir = $d['tracking_terakhir'] ?? 'Belum ada tracking';
                            $trackingCatatan = $d['tracking_catatan'] ?? '';
                            $teknisiId = $d['teknisi_id'] ?? ($d['tracking_teknisi_id'] ?? '');
                            $namaTeknisi = $d['nama_teknisi'] ?? '';
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= e($pelapor) ?></td>
                            <td><?= e($laporan) ?></td>
                            <td>
                                <?php if ($fotoPath && file_exists($fotoPath)): ?>
                                    <img src="<?= e($fotoPath) ?>" alt="Foto laporan" class="img-thumbnail report-thumb">
                                <?php else: ?>
                                    <span class="text-muted">Tidak ada foto</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-secondary"><?= e($status) ?></span></td>
                            <td>
                                <div><?= e($trackingTerakhir) ?></div>
                                <?php if ($trackingCatatan): ?>
                                    <small class="d-block text-muted"><?= e($trackingCatatan) ?></small>
                                <?php endif; ?>
                                <?php if ($namaTeknisi): ?>
                                    <small class="d-block">Teknisi: <?= e($namaTeknisi) ?></small>
                                <?php endif; ?>
                                <?php if (!empty($d['teknisi_latitude']) && !empty($d['teknisi_longitude'])): ?>
                                    <a class="small" target="_blank" href="https://www.google.com/maps?q=<?= e($d['teknisi_latitude']) ?>,<?= e($d['teknisi_longitude']) ?>">Posisi teknisi</a>
                                <?php endif; ?>
                                <?php if (!empty($d['tracking_waktu'])): ?>
                                    <small class="d-block text-muted"><?= e($d['tracking_waktu']) ?></small>
                                <?php endif; ?>
                                <?php if (!empty($d['estimasi_selesai'])): ?>
                                    <small class="d-block text-muted">Estimasi: <?= e($d['estimasi_selesai']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= e($kontak) ?></td>
                            <td>
                                <form method="POST" class="d-grid gap-2">
                                    <input type="hidden" name="laporan_id" value="<?= e($d['id'] ?? '') ?>">

                                    <select name="status" class="form-select form-select-sm">
                                        <option value="menunggu" <?= $status === 'menunggu' ? 'selected' : '' ?>>Menunggu</option>
                                        <option value="diproses" <?= $status === 'diproses' ? 'selected' : '' ?>>Diproses</option>
                                        <option value="selesai" <?= $status === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                    </select>

                                    <?php if ($teknisiOptions): ?>
                                        <select name="teknisi_id" class="form-select form-select-sm">
                                            <option value="">Pilih teknisi</option>
                                            <?php foreach ($teknisiOptions as $option): ?>
                                                <option value="<?= e($option['id']) ?>" <?= (string) $teknisiId === (string) $option['id'] ? 'selected' : '' ?>>
                                                    <?= e($option['nama']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <input type="number" name="teknisi_id" class="form-control form-control-sm" placeholder="ID teknisi">
                                    <?php endif; ?>

                                    <input type="number" name="estimasi_menit" min="0" class="form-control form-control-sm" placeholder="Estimasi menit">

                                    <textarea name="tracking" class="form-control form-control-sm" rows="2" placeholder="Komentar admin / catatan tugas"></textarea>

                                    <button type="submit" name="proses_laporan" class="btn btn-primary btn-sm">
                                        Simpan Pemrosesan
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Belum ada laporan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
