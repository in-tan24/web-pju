-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 29 Bulan Mei 2026 pada 08.54
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sistem_pju`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `laporan`
--

CREATE TABLE `laporan` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `judul` varchar(150) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `latitude` varchar(50) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `status` enum('menunggu','diproses','selesai') DEFAULT 'menunggu',
  `teknisi_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assigned_at` datetime DEFAULT NULL,
  `estimasi_selesai` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `laporan`
--

INSERT INTO `laporan` (`id`, `user_id`, `judul`, `deskripsi`, `latitude`, `longitude`, `alamat`, `foto`, `status`, `teknisi_id`, `created_at`, `assigned_at`, `estimasi_selesai`) VALUES
(1, NULL, 'intan', 'lampu mati di jalan perjuangan', '-6.805967108841366', '108.6181917909352', '08907667', '', 'diproses', 2, '2026-05-29 03:39:33', '2026-05-29 11:00:45', '2026-05-29 07:00:45');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tracking_teknisi`
--

CREATE TABLE `tracking_teknisi` (
  `id` int(11) NOT NULL,
  `laporan_id` int(11) DEFAULT NULL,
  `teknisi_id` int(11) DEFAULT NULL,
  `status_tracking` varchar(100) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `latitude` varchar(50) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL,
  `foto_sebelum` varchar(255) DEFAULT NULL,
  `foto_sesudah` varchar(255) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `estimasi_menit` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tracking_teknisi`
--

INSERT INTO `tracking_teknisi` (`id`, `laporan_id`, `teknisi_id`, `status_tracking`, `updated_at`, `latitude`, `longitude`, `foto_sebelum`, `foto_sesudah`, `catatan`, `estimasi_menit`) VALUES
(1, 1, 2, 'diproses', '2026-05-29 04:00:45', NULL, NULL, NULL, NULL, 'Status laporan diubah menjadi diproses', 60),
(2, 1, 2, 'lokasi realtime', '2026-05-29 04:02:09', '-6.805931564629936', '108.61834192508633', NULL, NULL, 'Update lokasi otomatis teknisi', NULL),
(3, 1, 2, 'lokasi realtime', '2026-05-29 04:02:16', '-6.805909074127016', '108.61842654992853', NULL, NULL, 'Update lokasi otomatis teknisi', NULL),
(4, 1, 2, 'lokasi realtime', '2026-05-29 04:02:45', '-6.805909074127016', '108.61842654992853', NULL, NULL, 'Update lokasi otomatis teknisi', NULL),
(5, 1, 2, 'lokasi realtime', '2026-05-29 06:40:46', '-6.805944637151336', '108.61829273721068', NULL, NULL, 'Update lokasi otomatis teknisi', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','teknisi','pelapor') DEFAULT 'pelapor',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Admin', 'admin@gmail.com', '0192023a7bbd73250516f069df18b500', 'admin', '2026-05-29 02:50:01'),
(2, 'sule', 'sule@prikitiw.com', 'e10adc3949ba59abbe56e057f20f883e', 'teknisi', '2026-05-29 03:59:31'),
(3, 'makmur', 'makmur@semeleketep.com', 'e194dd6667a49e77deb3169f08ba617b', 'teknisi', '2026-05-29 04:00:08');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `laporan`
--
ALTER TABLE `laporan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `teknisi_id` (`teknisi_id`);

--
-- Indeks untuk tabel `tracking_teknisi`
--
ALTER TABLE `tracking_teknisi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `laporan_id` (`laporan_id`),
  ADD KEY `teknisi_id` (`teknisi_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `laporan`
--
ALTER TABLE `laporan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `tracking_teknisi`
--
ALTER TABLE `tracking_teknisi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `laporan`
--
ALTER TABLE `laporan`
  ADD CONSTRAINT `laporan_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `laporan_ibfk_2` FOREIGN KEY (`teknisi_id`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `tracking_teknisi`
--
ALTER TABLE `tracking_teknisi`
  ADD CONSTRAINT `tracking_teknisi_ibfk_1` FOREIGN KEY (`laporan_id`) REFERENCES `laporan` (`id`),
  ADD CONSTRAINT `tracking_teknisi_ibfk_2` FOREIGN KEY (`teknisi_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
