<?php
include 'koneksi.php';
include 'schema_teknisi.php';

ensure_teknisi_schema($conn);

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function count_data($conn, $sql) {
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        return 0;
    }

    $row = mysqli_fetch_row($result);
    return (int) ($row[0] ?? 0);
}

$totalTugas = count_data($conn, "SELECT COUNT(*) FROM laporan WHERE teknisi_id IS NOT NULL");
$sedangJalan = count_data($conn, "SELECT COUNT(*) FROM laporan WHERE teknisi_id IS NOT NULL AND status='diproses'");
$selesai = count_data($conn, "SELECT COUNT(*) FROM laporan WHERE teknisi_id IS NOT NULL AND status='selesai'");
$lokasiAktif = count_data($conn, "SELECT COUNT(*) FROM tracking_teknisi WHERE latitude IS NOT NULL AND latitude <> '' AND longitude IS NOT NULL AND longitude <> ''");

$data = mysqli_query(
    $conn,
    "SELECT laporan.id,
            laporan.judul,
            laporan.deskripsi,
            laporan.alamat,
            laporan.latitude AS laporan_latitude,
            laporan.longitude AS laporan_longitude,
            laporan.status,
            laporan.assigned_at,
            laporan.estimasi_selesai,
            users.nama AS nama_teknisi,
            latest.status_tracking,
            latest.latitude AS teknisi_latitude,
            latest.longitude AS teknisi_longitude,
            latest.foto_sebelum,
            latest.foto_sesudah,
            latest.catatan,
            latest.estimasi_menit,
            latest.updated_at
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
     ) latest ON latest.laporan_id = laporan.id
     WHERE laporan.teknisi_id IS NOT NULL
     ORDER BY laporan.status <> 'diproses', latest.updated_at DESC, laporan.id DESC"
);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracking Teknisi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="mb-0">Tracking Teknisi</h2>
            <div class="text-muted">Monitoring progres, lokasi, foto, dan riwayat pekerjaan teknisi.</div>
        </div>
        <div class="d-flex gap-2">
            <a href="dashboard_admin.php" class="btn btn-outline-primary">Dashboard Admin</a>
            <a href="teknisi_dashboard.php" class="btn btn-outline-success">Dashboard Teknisi</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card p-3 h-100">
                <div class="text-muted">Total Tugas Teknisi</div>
                <h2><?= $totalTugas ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 h-100">
                <div class="text-muted">Sedang Diproses</div>
                <h2><?= $sedangJalan ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 h-100">
                <div class="text-muted">Selesai</div>
                <h2><?= $selesai ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 h-100">
                <div class="text-muted">Update Lokasi</div>
                <h2><?= $lokasiAktif ?></h2>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle bg-white">
            <thead class="table-dark">
                <tr>
                    <th class="table-col-id">ID</th>
                    <th>Laporan</th>
                    <th>Teknisi</th>
                    <th>Status</th>
                    <th>Tracking Terakhir</th>
                    <th>Lokasi</th>
                    <th>Foto</th>
                    <th>Estimasi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($data && mysqli_num_rows($data) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($data)): ?>
                        <?php
                            $laporanMaps = (!empty($row['laporan_latitude']) && !empty($row['laporan_longitude']))
                                ? 'https://www.google.com/maps?q=' . $row['laporan_latitude'] . ',' . $row['laporan_longitude']
                                : '';
                            $teknisiMaps = (!empty($row['teknisi_latitude']) && !empty($row['teknisi_longitude']))
                                ? 'https://www.google.com/maps?q=' . $row['teknisi_latitude'] . ',' . $row['teknisi_longitude']
                                : '';
                            $statusClass = $row['status'] === 'selesai' ? 'bg-success' : ($row['status'] === 'diproses' ? 'bg-info' : 'bg-secondary');
                        ?>
                        <tr>
                            <td><?= e($row['id']) ?></td>
                            <td>
                                <strong><?= e($row['judul']) ?></strong>
                                <div class="small text-muted"><?= e($row['deskripsi']) ?></div>
                                <div class="small"><?= e($row['alamat']) ?></div>
                            </td>
                            <td><?= e($row['nama_teknisi'] ?? 'Belum ada nama') ?></td>
                            <td><span class="badge <?= $statusClass ?>"><?= e($row['status']) ?></span></td>
                            <td>
                                <div><?= e($row['status_tracking'] ?? 'Belum ada update') ?></div>
                                <?php if (!empty($row['catatan'])): ?>
                                    <small class="d-block text-muted"><?= e($row['catatan']) ?></small>
                                <?php endif; ?>
                                <?php if (!empty($row['updated_at'])): ?>
                                    <small class="d-block text-muted"><?= e($row['updated_at']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($laporanMaps): ?>
                                    <a class="btn btn-sm btn-outline-primary mb-1" target="_blank" href="<?= e($laporanMaps) ?>">Lokasi Lampu</a>
                                <?php endif; ?>

                                <?php if ($teknisiMaps): ?>
                                    <a class="btn btn-sm btn-outline-success mb-1" target="_blank" href="<?= e($teknisiMaps) ?>">Posisi Teknisi</a>
                                    <div class="small text-muted"><?= e($row['teknisi_latitude']) ?>, <?= e($row['teknisi_longitude']) ?></div>
                                <?php else: ?>
                                    <span class="text-muted">Belum ada lokasi teknisi</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($row['foto_sebelum']) && file_exists('upload/' . $row['foto_sebelum'])): ?>
                                    <a target="_blank" href="upload/<?= e($row['foto_sebelum']) ?>">Sebelum</a><br>
                                <?php endif; ?>
                                <?php if (!empty($row['foto_sesudah']) && file_exists('upload/' . $row['foto_sesudah'])): ?>
                                    <a target="_blank" href="upload/<?= e($row['foto_sesudah']) ?>">Sesudah</a>
                                <?php endif; ?>
                                <?php if (empty($row['foto_sebelum']) && empty($row['foto_sesudah'])): ?>
                                    <span class="text-muted">Belum ada foto</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($row['estimasi_selesai'])): ?>
                                    <?= e($row['estimasi_selesai']) ?>
                                <?php elseif (!empty($row['estimasi_menit'])): ?>
                                    <?= e($row['estimasi_menit']) ?> menit
                                <?php else: ?>
                                    <span class="text-muted">Belum diisi</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            Belum ada laporan yang ditugaskan ke teknisi.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
