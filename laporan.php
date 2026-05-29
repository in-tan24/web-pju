<?php
include 'koneksi.php';

$pesan = '';
$error = '';

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

if (isset($_POST['kirim'])) {
    $judul = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $foto = '';

    if ($judul === '' || $deskripsi === '' || $alamat === '') {
        $error = 'Judul, deskripsi, dan alamat lokasi wajib diisi.';
    } else {
        if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $extension = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (!in_array($extension, $allowed, true)) {
                $error = 'Foto harus berupa JPG, PNG, GIF, atau WEBP.';
            } else {
                if (!is_dir('upload')) {
                    mkdir('upload', 0777, true);
                }

                $foto = date('YmdHis') . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $_FILES['foto']['name']);
                move_uploaded_file($_FILES['foto']['tmp_name'], 'upload/' . $foto);
            }
        }

        if ($error === '') {
            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO laporan (judul, deskripsi, latitude, longitude, alamat, foto) VALUES (?, ?, ?, ?, ?, ?)"
            );

            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'ssssss', $judul, $deskripsi, $latitude, $longitude, $alamat, $foto);
                mysqli_stmt_execute($stmt);
                $pesan = 'Laporan berhasil dikirim.';
                $_POST = [];
            } else {
                $error = 'Laporan belum dapat disimpan: ' . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan PJU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5 mb-5">
    <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
            <h3 class="mb-0">Pelaporan Lampu Jalan Mati</h3>
            <a href="login.php" class="btn btn-outline-primary">Halaman Utama</a>
        </div>

        <?php if ($pesan): ?>
            <div class="alert alert-success"><?= e($pesan) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="judul" class="form-control mb-3" placeholder="Judul Laporan" value="<?= e($_POST['judul'] ?? '') ?>" required>

            <textarea name="deskripsi" class="form-control mb-3" placeholder="Deskripsi" required><?= e($_POST['deskripsi'] ?? '') ?></textarea>

            <input type="hidden" id="latitude" name="latitude" value="<?= e($_POST['latitude'] ?? '') ?>">
            <input type="hidden" id="longitude" name="longitude" value="<?= e($_POST['longitude'] ?? '') ?>">

            <input type="text" name="alamat" class="form-control mb-3" placeholder="Alamat Lokasi" value="<?= e($_POST['alamat'] ?? '') ?>" required>

            <input type="file" name="foto" class="form-control mb-3" accept="image/*">

            <button type="submit" name="kirim" class="btn btn-success">
                Kirim Laporan
            </button>
        </form>
    </div>
</div>

<script>
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function(position) {
        document.getElementById('latitude').value = position.coords.latitude;
        document.getElementById('longitude').value = position.coords.longitude;
    });
}
</script>
</body>
</html>
