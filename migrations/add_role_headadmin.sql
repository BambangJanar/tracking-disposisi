-- ============================================================================
-- MIGRATION: Tambah Role Head Admin (Kepala Pimpinan Divisi)
-- Level tertinggi di sistem, di atas Kepala Bagian (superadmin)
-- ============================================================================

-- 1. Tambah role baru
INSERT INTO `roles` (`id`, `nama_role`, `keterangan`) 
VALUES (4, 'headadmin', 'Kepala Pimpinan Divisi')
ON DUPLICATE KEY UPDATE `nama_role` = 'headadmin', `keterangan` = 'Kepala Pimpinan Divisi';

-- 2. Buat akun Head Admin
--    Email    : headadmin@mail.com
--    Password : headadmin123
INSERT INTO `users` (`nama_lengkap`, `email`, `password`, `id_role`, `id_bagian`, `status`, `status_aktif`) 
VALUES ('Kepala Pimpinan Divisi', 'headadmin@mail.com', 'headadmin123', 4, NULL, 'active', 1)
ON DUPLICATE KEY UPDATE `id_role` = 4, `status` = 'active', `status_aktif` = 1;
