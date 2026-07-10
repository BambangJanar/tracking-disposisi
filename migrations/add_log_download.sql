-- ============================================================================
-- MIGRASI: Tambah Tabel log_download
-- Fitur: Log Unduh/Download Arsip untuk Audit Trail
-- Tanggal: 2026-07-11
-- ============================================================================

CREATE TABLE IF NOT EXISTS `log_download` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `surat_id` INT NOT NULL,
    `nama_file` VARCHAR(255) NOT NULL,
    `aksi` ENUM('download', 'view') DEFAULT 'download',
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`surat_id`) REFERENCES `surat`(`id`) ON DELETE CASCADE,
    INDEX `idx_log_download_user` (`user_id`),
    INDEX `idx_log_download_surat` (`surat_id`),
    INDEX `idx_log_download_tanggal` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
