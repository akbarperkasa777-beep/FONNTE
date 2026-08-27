-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 27 Agu 2026 pada 06.19
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
-- Database: `project_api_whatsapp`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `log_whatsapp`
--

CREATE TABLE `log_whatsapp` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `no_tujuan` varchar(20) NOT NULL,
  `pesan` text NOT NULL,
  `status` enum('pending','success','failed') NOT NULL DEFAULT 'pending',
  `response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `log_whatsapp`
--

INSERT INTO `log_whatsapp` (`id`, `user_id`, `no_tujuan`, `pesan`, `status`, `response`, `created_at`) VALUES
(8, 8, '62881027499131', '🎉 REGISTRASI BERHASIL\n\nHalo, mikail iskandar banyu bening!\nSelamat, akun Anda telah berhasil dibuat.\n\n📧 Email: akbarperkasa777@gmail.com\n📱 WhatsApp: 62881027499131\n\nTerima kasih telah melakukan registrasi.', 'success', '{\"detail\":\"success! message in queue\",\"id\":[175431040],\"process\":\"pending\",\"quota\":{\"089519104607\":{\"details\":\"deduced from total quota\",\"quota\":997,\"remaining\":996,\"used\":1}},\"requestid\":673575293,\"status\":true,\"target\":[\"62881027499131\"]}', '2026-08-27 04:13:06');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `no_hp` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `no_hp`, `password`, `created_at`) VALUES
(8, 'mikail iskandar banyu bening', 'akbarperkasa777@gmail.com', '62881027499131', '$2y$10$eUEEVQB1b6UsKk1bYvi1buWKP5wCFCaWkRo8H5rv8UVn4AIKwN.Qu', '2026-08-27 04:13:05');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `log_whatsapp`
--
ALTER TABLE `log_whatsapp`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_log_whatsapp_user` (`user_id`),
  ADD KEY `idx_log_whatsapp_status` (`status`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `no_hp` (`no_hp`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `log_whatsapp`
--
ALTER TABLE `log_whatsapp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `log_whatsapp`
--
ALTER TABLE `log_whatsapp`
  ADD CONSTRAINT `fk_log_whatsapp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
