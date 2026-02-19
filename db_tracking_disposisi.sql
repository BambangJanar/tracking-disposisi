-- ============================================================================
-- DATABASE: db_tracking_disposisi
-- Tracking Disposisi Surat - Database Schema
-- Generated from PHP codebase analysis
-- ============================================================================
CREATE DATABASE IF NOT EXISTS `db_tracking_disposisi` DEFAULT CHARACTER SET utf8mb4 DEFAULT COLLATE utf8mb4_general_ci;
USE `db_tracking_disposisi`;
-- ============================================================================
-- 1. TABEL ROLES
-- ============================================================================
CREATE TABLE `roles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_role` VARCHAR(50) NOT NULL,
    `keterangan` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- Data default roles
INSERT INTO `roles` (`id`, `nama_role`, `keterangan`)
VALUES (
        1,
        'superadmin',
        'Kepala Bagian - Akses penuh ke sistem'
    ),
    (
        2,
        'admin',
        'Karyawan - Mengelola surat dan disposisi'
    ),
    (3, 'user', 'Anak Magang - Akses terbatas');
-- ============================================================================
-- 2. TABEL BAGIAN
-- ============================================================================
CREATE TABLE `bagian` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_bagian` VARCHAR(100) NOT NULL,
    `keterangan` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- ============================================================================
-- 3. TABEL USERS
-- ============================================================================
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_lengkap` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `id_role` INT NOT NULL DEFAULT 3,
    `id_bagian` INT DEFAULT NULL,
    `nama_bagian_custom` VARCHAR(100) DEFAULT NULL,
    `status` ENUM('pending', 'active', 'inactive') DEFAULT 'pending',
    `status_aktif` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_role`) REFERENCES `roles`(`id`) ON UPDATE CASCADE,
    FOREIGN KEY (`id_bagian`) REFERENCES `bagian`(`id`) ON UPDATE
    SET NULL ON DELETE
    SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- Data default users (Password: admin123)
INSERT INTO `users` (
        `id`,
        `nama_lengkap`,
        `email`,
        `password`,
        `id_role`,
        `status`,
        `status_aktif`
    )
VALUES (
        1,
        'Super Admin',
        'superadmin@mail.com',
        'admin123',
        1,
        'active',
        1
    ),
    (
        2,
        'Karyawan',
        'karyawan@mail.com',
        'admin123',
        2,
        'active',
        1
    ),
    (
        3,
        'Magang',
        'magang@mail.com',
        'admin123',
        3,
        'active',
        1
    );
-- ============================================================================
-- 4. TABEL JENIS SURAT
-- ============================================================================
CREATE TABLE `jenis_surat` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_jenis` VARCHAR(100) NOT NULL,
    `keterangan` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- Data default jenis surat
INSERT INTO `jenis_surat` (`nama_jenis`, `keterangan`)
VALUES (
        'Surat Masuk',
        'Surat yang diterima dari pihak luar'
    ),
    (
        'Surat Keluar',
        'Surat yang dikirim ke pihak luar'
    ),
    (
        'Surat Proposal',
        'Surat proposal kegiatan atau kerjasama'
    );
-- ============================================================================
-- 5. TABEL SURAT
-- ============================================================================
CREATE TABLE `surat` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `id_jenis` INT NOT NULL,
    `nomor_surat` VARCHAR(100) DEFAULT NULL,
    `nomor_agenda` VARCHAR(50) DEFAULT NULL,
    `tanggal_surat` DATE DEFAULT NULL,
    `tanggal_diterima` DATE DEFAULT NULL,
    `dari_instansi` VARCHAR(200) DEFAULT NULL,
    `ke_instansi` VARCHAR(200) DEFAULT NULL,
    `alamat_surat` TEXT DEFAULT NULL,
    `perihal` TEXT NOT NULL,
    `lampiran_file` VARCHAR(255) DEFAULT NULL,
    `status_surat` ENUM(
        'baru',
        'proses',
        'disetujui',
        'ditolak',
        'arsip'
    ) DEFAULT 'baru',
    `status_sebelum_arsip` VARCHAR(20) DEFAULT NULL,
    `dibuat_oleh` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_jenis`) REFERENCES `jenis_surat`(`id`) ON UPDATE CASCADE,
    FOREIGN KEY (`dibuat_oleh`) REFERENCES `users`(`id`) ON DELETE
    SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- ============================================================================
-- 6. TABEL SURAT STAKEHOLDERS
-- ============================================================================
CREATE TABLE `surat_stakeholders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `surat_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `role_type` ENUM('pembuat', 'penerima_utama', 'penerima_delegasi') NOT NULL DEFAULT 'penerima_utama',
    `assigned_by` INT DEFAULT NULL,
    `assigned_at` DATETIME DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`surat_id`) REFERENCES `surat`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`assigned_by`) REFERENCES `users`(`id`) ON DELETE
    SET NULL,
        INDEX `idx_stakeholder_surat` (`surat_id`, `user_id`),
        INDEX `idx_stakeholder_active` (`user_id`, `is_active`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- ============================================================================
-- 7. TABEL DISPOSISI
-- ============================================================================
CREATE TABLE `disposisi` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `id_surat` INT NOT NULL,
    `dari_user_id` INT NOT NULL,
    `ke_user_id` INT NOT NULL,
    `status_disposisi` ENUM(
        'dikirim',
        'diterima',
        'diproses',
        'selesai',
        'ditolak'
    ) DEFAULT 'dikirim',
    `catatan` TEXT DEFAULT NULL,
    `tanggal_disposisi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `tanggal_respon` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_surat`) REFERENCES `surat`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`dari_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`ke_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_disposisi_surat` (`id_surat`),
    INDEX `idx_disposisi_dari` (`dari_user_id`),
    INDEX `idx_disposisi_ke` (`ke_user_id`),
    INDEX `idx_disposisi_status` (`status_disposisi`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- ============================================================================
-- 8. TABEL NOTIFICATIONS
-- ============================================================================
CREATE TABLE `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT DEFAULT NULL,
    `surat_id` INT DEFAULT NULL,
    `disposisi_id` INT DEFAULT NULL,
    `url` VARCHAR(255) DEFAULT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `read_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`surat_id`) REFERENCES `surat`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`disposisi_id`) REFERENCES `disposisi`(`id`) ON DELETE CASCADE,
    INDEX `idx_notif_user` (`user_id`, `is_read`),
    INDEX `idx_notif_surat` (`surat_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- ============================================================================
-- 9. TABEL LOG AKTIVITAS
-- ============================================================================
CREATE TABLE `log_aktivitas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `aktivitas` VARCHAR(100) NOT NULL,
    `keterangan` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_log_user` (`user_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- ============================================================================
-- 10. TABEL SETTINGS
-- ============================================================================
CREATE TABLE `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `app_name` VARCHAR(100) DEFAULT 'Tracking Disposisi',
    `app_description` VARCHAR(255) DEFAULT 'Aplikasi Manajemen Surat',
    `app_logo` VARCHAR(255) DEFAULT NULL,
    `app_favicon` VARCHAR(255) DEFAULT NULL,
    `theme_color` VARCHAR(20) DEFAULT 'blue',
    `instansi_nama` VARCHAR(200) DEFAULT 'DINAS KOMUNIKASI DAN INFORMATIKA',
    `instansi_alamat` TEXT DEFAULT NULL,
    `instansi_telepon` VARCHAR(20) DEFAULT NULL,
    `instansi_email` VARCHAR(100) DEFAULT NULL,
    `instansi_logo` VARCHAR(255) DEFAULT NULL,
    `ttd_nama_penandatangan` VARCHAR(255) DEFAULT NULL,
    `ttd_nip` VARCHAR(50) DEFAULT NULL,
    `ttd_jabatan` VARCHAR(100) DEFAULT 'Kepala Dinas',
    `ttd_kota` VARCHAR(50) DEFAULT 'Banjarmasin',
    `ttd_image` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- Data default settings
INSERT INTO `settings` (
        `id`,
        `app_name`,
        `app_description`,
        `theme_color`,
        `instansi_nama`,
        `ttd_jabatan`,
        `ttd_kota`
    )
VALUES (
        1,
        'Tracking Disposisi Surat',
        'Aplikasi Pelacakan Surat dan Disposisi',
        'blue',
        'DINAS KOMUNIKASI DAN INFORMATIKA',
        'Kepala Dinas',
        'Banjarmasin'
    );