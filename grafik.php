<?php
include 'koneksi.php';

$status = [];
$total = [];
$error = '';

$query = mysqli_query(
    $conn,
    "SELECT status, COUNT(*) AS total FROM laporan GROUP BY status ORDER BY status"
);

if ($query) {
    while ($d = mysqli_fetch_assoc($query)) {
        $status[] = $d['status'] ?: 'tanpa status';
        $total[] = (int) $d['total'];
    }
} else {
    $error = mysqli_error($conn);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grafik Pelaporan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
        <h3 class="mb-0">Grafik Pelaporan</h3>
        <a href="dashboard_admin.php" class="btn btn-outline-primary">Dashboard Admin</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">Data grafik belum dapat dimuat: <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php elseif (!$status): ?>
        <div class="alert alert-info">Belum ada data laporan untuk ditampilkan.</div>
    <?php else: ?>
        <div class="card p-4">
            <canvas id="myChart" height="120"></canvas>
        </div>
    <?php endif; ?>
</div>

<?php if ($status): ?>
<script>
const ctx = document.getElementById('myChart');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($status) ?>,
        datasets: [{
            label: 'Jumlah Laporan',
            data: <?= json_encode($total) ?>,
            backgroundColor: '#198ac0',
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                }
            }
        }
    }
});
</script>
<?php endif; ?>
</body>
</html>
