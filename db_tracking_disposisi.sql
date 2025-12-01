-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 01, 2025 at 09:25 AM
-- Server version: 5.7.39
-- PHP Version: 8.2.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_tracking_disposisi`
--

-- --------------------------------------------------------

--
-- Table structure for table `bagian`
--

CREATE TABLE `bagian` (
  `id` int(10) UNSIGNED NOT NULL,
  `nama_bagian` varchar(100) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `bagian`
--

INSERT INTO `bagian` (`id`, `nama_bagian`, `keterangan`) VALUES
(1, 'TCC (Technology & Communication Center)', 'Bagian teknologi dan komunikasi'),
(2, 'Umum', 'Bagian umum dan administrasi'),
(3, 'Keuangan', 'Bagian keuangan'),
(4, 'SDM', 'Sumber Daya Manusia');

-- --------------------------------------------------------

--
-- Table structure for table `disposisi`
--

CREATE TABLE `disposisi` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_surat` int(10) UNSIGNED NOT NULL,
  `dari_user_id` int(10) UNSIGNED NOT NULL,
  `ke_user_id` int(10) UNSIGNED NOT NULL,
  `status_disposisi` enum('dikirim','diterima','diproses','selesai','ditolak') NOT NULL DEFAULT 'dikirim',
  `catatan` text,
  `tanggal_disposisi` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tanggal_respon` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `disposisi`
--

INSERT INTO `disposisi` (`id`, `id_surat`, `dari_user_id`, `ke_user_id`, `status_disposisi`, `catatan`, `tanggal_disposisi`, `tanggal_respon`) VALUES
(1, 1, 7, 3, 'selesai', 'Mohon direview untuk kelayakan kerjasama', '2025-11-16 09:30:00', '2025-11-16 14:20:00'),
(2, 1, 3, 1, 'selesai', 'Sudah direview, menunggu persetujuan kepala bagian', '2025-11-16 15:00:00', '2025-11-17 10:00:00'),
(3, 3, 8, 4, 'diproses', 'Tolong cek kelengkapan dokumen pemohon', '2025-11-21 08:00:00', '2025-11-21 10:30:00'),
(4, 6, 9, 5, 'diterima', 'Proposal sponsorship untuk kegiatan sosial bulan depan', '2025-11-11 13:00:00', '2025-11-11 15:45:00'),
(5, 6, 5, 1, 'dikirim', 'Menunggu keputusan kepala bagian untuk approval budget', '2025-11-11 16:00:00', NULL),
(7, 9, 1, 4, 'dikirim', 'posissiiii biss', '2025-11-29 13:07:01', NULL),
(8, 9, 1, 5, 'dikirim', 'testttttt', '2025-11-29 13:09:59', NULL),
(12, 9, 1, 9, 'dikirim', 'xa', '2025-11-29 13:39:40', NULL),
(13, 9, 1, 8, 'dikirim', 't', '2025-11-29 14:09:21', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `jenis_surat`
--

CREATE TABLE `jenis_surat` (
  `id` int(10) UNSIGNED NOT NULL,
  `nama_jenis` varchar(50) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `jenis_surat`
--

INSERT INTO `jenis_surat` (`id`, `nama_jenis`, `keterangan`) VALUES
(1, 'Surat Masuk', 'Surat yang diterima dari luar'),
(2, 'Surat Keluar', 'Surat yang dikirim ke luar'),
(3, 'Proposal', 'Proposal yang diajukan ke/oleh instansi'),
(5, 'etd', 's');

-- --------------------------------------------------------

--
-- Table structure for table `log_aktivitas`
--

CREATE TABLE `log_aktivitas` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `aktivitas` varchar(100) NOT NULL,
  `keterangan` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `log_aktivitas`
--

INSERT INTO `log_aktivitas` (`id`, `user_id`, `aktivitas`, `keterangan`, `created_at`) VALUES
(1, 1, 'login', 'User login ke sistem', '2025-11-29 11:37:22'),
(2, 7, 'login', 'User login ke sistem', '2025-11-29 11:37:22'),
(3, 7, 'tambah_surat', 'Menambah surat: SM/001/11/2025', '2025-11-29 11:37:22'),
(4, 7, 'disposisi_surat', 'Mendisposisi surat SM/001/11/2025 ke Ahmad Fauzi', '2025-11-29 11:37:22'),
(5, 3, 'login', 'User login ke sistem', '2025-11-29 11:37:22'),
(6, 3, 'update_disposisi', 'Mengubah status disposisi ID 1 menjadi diterima', '2025-11-29 11:37:22'),
(7, 3, 'disposisi_surat', 'Mendisposisi surat SM/001/11/2025 ke Budi Santoso', '2025-11-29 11:37:22'),
(8, 1, 'update_disposisi', 'Mengubah status disposisi ID 2 menjadi selesai', '2025-11-29 11:37:22'),
(9, 1, 'update_status_surat', 'Mengubah status surat ID 1 menjadi disetujui', '2025-11-29 11:37:22'),
(10, 1, 'login', 'User login ke sistem', '2025-11-29 11:37:54'),
(11, 1, 'logout', 'User logout dari sistem', '2025-11-29 11:48:40'),
(12, 3, 'login', 'User login ke sistem', '2025-11-29 11:48:52'),
(13, 3, 'logout', 'User logout dari sistem', '2025-11-29 11:49:19'),
(14, 7, 'login', 'User login ke sistem', '2025-11-29 11:49:28'),
(15, 7, 'logout', 'User logout dari sistem', '2025-11-29 11:49:41'),
(16, 1, 'login', 'User login ke sistem', '2025-11-29 11:49:51'),
(17, 3, 'login', 'User login ke sistem', '2025-11-29 11:50:35'),
(18, 1, 'tambah_surat', 'Menambah surat: SM/004/11/2025', '2025-11-29 12:38:23'),
(19, 1, 'tambah_surat', 'Menambah surat: SK/003/11/2025', '2025-11-29 12:39:08'),
(20, 1, 'disposisi_surat', 'Mendisposisi surat SK/003/11/2025 ke Siti Nurhaliza', '2025-11-29 12:48:23'),
(21, 1, 'disposisi_surat', 'Mendisposisi surat SM/004/11/2025 ke Dewi Lestari', '2025-11-29 13:07:01'),
(22, 1, 'disposisi_surat', 'Mendisposisi surat SM/004/11/2025 ke Rizki Pratama', '2025-11-29 13:09:59'),
(23, 1, 'disposisi_surat', 'Mendisposisi surat SK/003/11/2025 ke Dewi Lestari', '2025-11-29 13:11:36'),
(24, 1, 'disposisi_surat', 'Mendisposisi surat SK/003/11/2025 ke Dewi Lestari', '2025-11-29 13:20:18'),
(25, 1, 'logout', 'User logout dari sistem', '2025-11-29 13:32:10'),
(26, 1, 'login', 'User login ke sistem', '2025-11-29 13:32:12'),
(27, 1, 'disposisi_surat', 'Mendisposisi surat SK/003/11/2025 ke Rizki Pratama', '2025-11-29 13:39:21'),
(28, 1, 'disposisi_surat', 'Mendisposisi surat SM/004/11/2025 ke Eko Prasetyo', '2025-11-29 13:39:40'),
(29, 1, 'edit_surat', 'Mengedit surat ID: 10', '2025-11-29 13:45:07'),
(30, 1, 'hapus_surat', 'Menghapus surat ID: 10', '2025-11-29 13:45:14'),
(31, 1, 'tambah_jenis_surat', 'Menambah jenis surat: test tambah jjenis', '2025-11-29 14:05:34'),
(32, 1, 'edit_jenis_surat', 'Mengedit jenis surat ID: 4', '2025-11-29 14:07:52'),
(33, 1, 'hapus_jenis_surat', 'Menghapus jenis surat ID: 4', '2025-11-29 14:07:56'),
(34, 1, 'tambah_jenis_surat', 'Menambah jenis surat: etd', '2025-11-29 14:08:02'),
(35, 1, 'arsip_surat', 'Mengarsipkan surat ID: 9', '2025-11-29 14:08:29'),
(36, 1, 'disposisi_surat', 'Mendisposisi surat SM/004/11/2025 ke Putri Ayu', '2025-11-29 14:09:21'),
(37, 1, 'update_profil', 'Mengupdate profil', '2025-11-29 14:12:51'),
(38, 1, 'logout', 'User logout dari sistem', '2025-11-29 14:13:24'),
(39, 1, 'login', 'User login ke sistem', '2025-11-29 14:13:31'),
(40, 1, 'ganti_password', 'Mengganti password', '2025-11-29 14:13:46'),
(41, 1, 'logout', 'User logout dari sistem', '2025-11-29 14:13:53'),
(42, 1, 'login', 'User login ke sistem', '2025-11-29 14:13:58'),
(43, 1, 'ganti_password', 'Mengganti password', '2025-11-29 14:14:07'),
(44, 1, 'logout', 'User logout dari sistem', '2025-11-29 14:14:19'),
(45, 1, 'login', 'User login ke sistem', '2025-11-29 14:14:21'),
(46, 1, 'logout', 'User logout dari sistem', '2025-11-29 14:14:24'),
(47, 3, 'login', 'User login ke sistem', '2025-11-29 14:14:31'),
(48, 3, 'logout', 'User logout dari sistem', '2025-11-29 14:15:15'),
(49, 7, 'login', 'User login ke sistem', '2025-11-29 14:15:24'),
(50, 7, 'logout', 'User logout dari sistem', '2025-11-29 14:16:54'),
(51, 9, 'login', 'User login ke sistem', '2025-11-29 14:17:24'),
(52, 9, 'logout', 'User logout dari sistem', '2025-11-29 14:17:32'),
(53, 8, 'login', 'User login ke sistem', '2025-11-29 14:17:41'),
(54, 8, 'logout', 'User logout dari sistem', '2025-11-29 14:17:50'),
(55, 7, 'login', 'User login ke sistem', '2025-11-29 14:17:52'),
(56, 7, 'logout', 'User logout dari sistem', '2025-11-29 14:19:10'),
(57, 12, 'login', 'User login ke sistem', '2025-11-29 14:19:14'),
(58, 12, 'logout', 'User logout dari sistem', '2025-12-01 16:18:32'),
(59, 12, 'login', 'User login ke sistem', '2025-12-01 16:18:35'),
(60, 12, 'logout', 'User logout dari sistem', '2025-12-01 16:18:37');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `nama_role` varchar(50) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `nama_role`, `keterangan`) VALUES
(1, 'superadmin', 'Kepala bagian / TCC'),
(2, 'admin', 'Karyawan / staf'),
(3, 'user', 'Anak magang / input awal');

-- --------------------------------------------------------

--
-- Table structure for table `surat`
--

CREATE TABLE `surat` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_jenis` int(10) UNSIGNED NOT NULL,
  `nomor_agenda` varchar(50) NOT NULL,
  `nomor_surat` varchar(100) NOT NULL,
  `tanggal_surat` date NOT NULL,
  `tanggal_diterima` date DEFAULT NULL,
  `dari_instansi` varchar(150) DEFAULT NULL,
  `ke_instansi` varchar(150) DEFAULT NULL,
  `alamat_surat` text NOT NULL,
  `perihal` varchar(150) NOT NULL,
  `lampiran_file` varchar(150) DEFAULT NULL,
  `status_surat` enum('baru','proses','ditolak','disetujui','arsip') NOT NULL DEFAULT 'baru',
  `dibuat_oleh` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `surat`
--

INSERT INTO `surat` (`id`, `id_jenis`, `nomor_agenda`, `nomor_surat`, `tanggal_surat`, `tanggal_diterima`, `dari_instansi`, `ke_instansi`, `alamat_surat`, `perihal`, `lampiran_file`, `status_surat`, `dibuat_oleh`, `created_at`, `updated_at`) VALUES
(1, 1, 'SM/001/11/2025', '123/PEM/XI/2025', '2025-11-15', '2025-11-16', 'PT Maju Jaya', NULL, 'Jl. Sudirman No. 123, Banjarmasin', 'Permohonan Kerjasama Pembukaan Rekening Giro', NULL, 'proses', 7, '2025-11-29 11:37:22', '2025-11-29 11:37:22'),
(2, 1, 'SM/002/11/2025', '456/DIR/XI/2025', '2025-11-18', '2025-11-18', 'Dinas Pendidikan Kalimantan Selatan', NULL, 'Jl. A. Yani KM 5, Banjarmasin', 'Undangan Rapat Koordinasi Program Beasiswa', NULL, 'baru', 7, '2025-11-29 11:37:22', '2025-11-29 11:37:22'),
(3, 1, 'SM/003/11/2025', '789/KEU/XI/2025', '2025-11-20', '2025-11-21', 'CV Berkah Sejahtera', NULL, 'Jl. Lambung Mangkurat No. 88, Banjarmasin', 'Pengajuan Kredit Usaha Mikro', NULL, 'proses', 8, '2025-11-29 11:37:22', '2025-11-29 11:37:22'),
(4, 2, 'SK/001/11/2025', '001/BKAL/DIR/XI/2025', '2025-11-17', NULL, NULL, NULL, 'PT Maju Jaya, Jl. Sudirman No. 123, Banjarmasin', 'Balasan Permohonan Kerjasama Pembukaan Rekening', NULL, 'disetujui', 4, '2025-11-29 11:37:22', '2025-11-29 11:37:22'),
(5, 2, 'SK/002/11/2025', '002/BKAL/HRD/XI/2025', '2025-11-22', NULL, NULL, NULL, 'Universitas Lambung Mangkurat, Jl. Brigjen H. Hasan Basry, Banjarmasin', 'Pemberitahuan Rekrutmen Pegawai', NULL, 'proses', 5, '2025-11-29 11:37:22', '2025-11-29 11:37:22'),
(6, 3, 'PR/001/11/2025', 'PROP/001/XI/2025', '2025-11-10', '2025-11-11', 'Yayasan Peduli Pendidikan', NULL, 'Jl. Veteran No. 45, Banjarmasin', 'Proposal Sponsorship Kegiatan Bakti Sosial', NULL, 'proses', 9, '2025-11-29 11:37:22', '2025-11-29 11:37:22'),
(7, 3, 'PR/002/11/2025', 'PROP/002/XI/2025', '2025-11-19', '2025-11-20', 'Komunitas Kreatif Banjarmasin', NULL, 'Jl. Pangeran Antasari No. 67, Banjarmasin', 'Proposal Kerjasama Event Festival Budaya', NULL, 'baru', 7, '2025-11-29 11:37:22', '2025-11-29 11:37:22'),
(9, 1, 'SM/004/11/2025', '421/115/DISDIK/XI/2025', '2025-11-25', '2025-11-29', 'test instansi', 'test ke instansi', 'dsvsdvdsv', 'vdsvdsv', '692a86cfa067e_1764394703.pdf', 'arsip', 1, '2025-11-29 12:38:23', '2025-11-29 14:08:29');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `id_role` int(10) UNSIGNED NOT NULL,
  `status_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id_bagian` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama_lengkap`, `email`, `password`, `id_role`, `status_aktif`, `created_at`, `updated_at`, `id_bagian`) VALUES
(1, 'Budi Santosoo', 'superadmin@bankkalsel.com', 'admin123', 1, 1, '2025-11-29 11:37:21', '2025-11-29 14:14:07', 1),
(2, 'Siti Nurhaliza', 'kepala.tcc@bankkalsel.com', 'admin123', 1, 1, '2025-11-29 11:37:21', '2025-11-29 11:37:21', 1),
(3, 'Ahmad Fauzi', 'karyawan@bankkalsel.com', 'admin123', 2, 1, '2025-11-29 11:37:21', '2025-11-29 11:37:21', 1),
(4, 'Dewi Lestari', 'admin1@bankkalsel.com', 'admin123', 2, 1, '2025-11-29 11:37:21', '2025-11-29 11:37:21', 2),
(5, 'Rizki Pratama', 'admin2@bankkalsel.com', 'admin123', 2, 1, '2025-11-29 11:37:21', '2025-11-29 11:37:21', 3),
(6, 'Linda Wijaya', 'karyawan.umum@bankkalsel.com', 'admin123', 2, 1, '2025-11-29 11:37:21', '2025-11-29 11:37:21', 2),
(7, 'Andi Setiawan', 'magang@bankkalsel.com', 'admin123', 3, 1, '2025-11-29 11:37:21', '2025-11-29 11:37:21', 1),
(8, 'Putri Ayu', 'magang1@bankkalsel.com', 'admin123', 3, 1, '2025-11-29 11:37:21', '2025-11-29 11:37:21', 2),
(9, 'Eko Prasetyo', 'magang2@bankkalsel.com', 'admin123', 3, 1, '2025-11-29 11:37:21', '2025-11-29 11:37:21', 1),
(10, 'Admin Baru', 'admin.baru@bankkalsel.com', 'admin123', 1, 1, '2025-11-29 14:19:00', '2025-11-29 14:19:00', 1),
(11, 'Karyawan Baru', 'karyawan.baru@bankkalsel.com', 'admin123', 2, 1, '2025-11-29 14:19:00', '2025-11-29 14:19:00', 1),
(12, 'Magang Baru', 'magang.baru@bankkalsel.com', 'admin123', 3, 1, '2025-11-29 14:19:00', '2025-11-29 14:19:00', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bagian`
--
ALTER TABLE `bagian`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `disposisi`
--
ALTER TABLE `disposisi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_disposisi_surat` (`id_surat`),
  ADD KEY `fk_disposisi_dari_user` (`dari_user_id`),
  ADD KEY `fk_disposisi_ke_user` (`ke_user_id`);

--
-- Indexes for table `jenis_surat`
--
ALTER TABLE `jenis_surat`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_log_user` (`user_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `surat`
--
ALTER TABLE `surat`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nomor_agenda` (`nomor_agenda`),
  ADD KEY `fk_surat_jenis` (`id_jenis`),
  ADD KEY `fk_surat_user` (`dibuat_oleh`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_users_roles` (`id_role`),
  ADD KEY `fk_users_bagian` (`id_bagian`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bagian`
--
ALTER TABLE `bagian`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `disposisi`
--
ALTER TABLE `disposisi`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `jenis_surat`
--
ALTER TABLE `jenis_surat`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `surat`
--
ALTER TABLE `surat`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `disposisi`
--
ALTER TABLE `disposisi`
  ADD CONSTRAINT `fk_disposisi_dari_user` FOREIGN KEY (`dari_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_disposisi_ke_user` FOREIGN KEY (`ke_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_disposisi_surat` FOREIGN KEY (`id_surat`) REFERENCES `surat` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `surat`
--
ALTER TABLE `surat`
  ADD CONSTRAINT `fk_surat_jenis` FOREIGN KEY (`id_jenis`) REFERENCES `jenis_surat` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_surat_user` FOREIGN KEY (`dibuat_oleh`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_bagian` FOREIGN KEY (`id_bagian`) REFERENCES `bagian` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_users_roles` FOREIGN KEY (`id_role`) REFERENCES `roles` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
