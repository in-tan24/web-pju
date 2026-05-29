<?php
function ensure_column($conn, $table, $column, $definition) {
    $table = mysqli_real_escape_string($conn, $table);
    $column = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");

    if ($result && mysqli_num_rows($result) === 0) {
        mysqli_query($conn, "ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}

function ensure_teknisi_schema($conn) {
    ensure_column($conn, 'laporan', 'assigned_at', 'datetime NULL');
    ensure_column($conn, 'laporan', 'estimasi_selesai', 'datetime NULL');
    ensure_column($conn, 'tracking_teknisi', 'latitude', 'varchar(50) NULL');
    ensure_column($conn, 'tracking_teknisi', 'longitude', 'varchar(50) NULL');
    ensure_column($conn, 'tracking_teknisi', 'foto_sebelum', 'varchar(255) NULL');
    ensure_column($conn, 'tracking_teknisi', 'foto_sesudah', 'varchar(255) NULL');
    ensure_column($conn, 'tracking_teknisi', 'catatan', 'text NULL');
    ensure_column($conn, 'tracking_teknisi', 'estimasi_menit', 'int(11) NULL');
}
?>
