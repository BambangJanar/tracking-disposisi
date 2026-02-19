-- ============================================================================
-- SEED DATA: Realistic Dummy Data for Tracking Disposisi Surat
-- 8 Surat + Disposisi + Stakeholders + Notifications + Log Aktivitas
-- Semua tujuan: Bank Kalsel Divisi Sekretaris Perusahaan
-- Instansi di Banjarmasin | Tanggal: 1 Des 2025 - 14 Feb 2026
-- ============================================================================
USE `db_tracking_disposisi`;
-- ============================================================================
-- 1. LOG AKTIVITAS (Login history)
-- ============================================================================
INSERT INTO `log_aktivitas` (
        `user_id`,
        `aktivitas`,
        `keterangan`,
        `created_at`
    )
VALUES (
        3,
        'login',
        'User login ke sistem',
        '2025-12-01 07:30:00'
    ),
    (
        2,
        'login',
        'User login ke sistem',
        '2025-12-01 08:00:00'
    ),
    (
        1,
        'login',
        'User login ke sistem',
        '2025-12-01 08:15:00'
    ),
    (
        3,
        'login',
        'User login ke sistem',
        '2025-12-08 07:45:00'
    ),
    (
        2,
        'login',
        'User login ke sistem',
        '2025-12-08 08:10:00'
    ),
    (
        3,
        'login',
        'User login ke sistem',
        '2025-12-29 07:30:00'
    ),
    (
        2,
        'login',
        'User login ke sistem',
        '2025-12-29 08:05:00'
    ),
    (
        1,
        'login',
        'User login ke sistem',
        '2025-12-30 08:00:00'
    ),
    (
        3,
        'login',
        'User login ke sistem',
        '2026-01-06 07:30:00'
    ),
    (
        3,
        'login',
        'User login ke sistem',
        '2026-01-23 07:30:00'
    ),
    (
        2,
        'login',
        'User login ke sistem',
        '2026-01-23 08:00:00'
    ),
    (
        3,
        'login',
        'User login ke sistem',
        '2026-01-30 07:40:00'
    ),
    (
        3,
        'login',
        'User login ke sistem',
        '2026-02-07 07:30:00'
    ),
    (
        2,
        'login',
        'User login ke sistem',
        '2026-02-07 08:00:00'
    ),
    (
        3,
        'login',
        'User login ke sistem',
        '2026-02-14 07:30:00'
    ),
    (
        2,
        'login',
        'User login ke sistem',
        '2026-02-14 08:00:00'
    ),
    (
        1,
        'login',
        'User login ke sistem',
        '2026-02-15 08:00:00'
    );
-- ============================================================================
-- 2. SURAT (8 surat realistis, semua ke Bank Kalsel)
-- ============================================================================
-- Surat 1: SMA Negeri 2 Banjarmasin → Bank Kalsel (Undangan HUT)
INSERT INTO `surat` (
        `id`,
        `id_jenis`,
        `nomor_surat`,
        `nomor_agenda`,
        `tanggal_surat`,
        `tanggal_diterima`,
        `dari_instansi`,
        `ke_instansi`,
        `alamat_surat`,
        `perihal`,
        `lampiran_file`,
        `status_surat`,
        `dibuat_oleh`,
        `created_at`
    )
VALUES (
        1,
        1,
        '421/198/SMA2/XII/2025',
        'AGD-20251201-001',
        '2025-12-01',
        '2025-12-01',
        'SMA Negeri 2 Banjarmasin',
        'Bank Kalsel Divisi Sekretaris Perusahaan',
        'Jl. Batu Benawa I No. 1, Teluk Dalam, Kec. Banjarmasin Tengah, Kota Banjarmasin, Kalimantan Selatan 70117',
        'Undangan Peringatan HUT Ke-55 SMA Negeri 2 Banjarmasin',
        NULL,
        'proses',
        3,
        '2025-12-01 08:00:00'
    );
-- Surat 2: Universitas Lambung Mangkurat → Bank Kalsel (Proposal Magang)
INSERT INTO `surat` (
        `id`,
        `id_jenis`,
        `nomor_surat`,
        `nomor_agenda`,
        `tanggal_surat`,
        `tanggal_diterima`,
        `dari_instansi`,
        `ke_instansi`,
        `alamat_surat`,
        `perihal`,
        `lampiran_file`,
        `status_surat`,
        `dibuat_oleh`,
        `created_at`
    )
VALUES (
        2,
        3,
        '1023/UN8.1/KM/2025',
        'AGD-20251208-001',
        '2025-12-08',
        '2025-12-08',
        'Universitas Lambung Mangkurat',
        'Bank Kalsel Divisi Sekretaris Perusahaan',
        'Jl. Brigjen H. Hasan Basry No. 1, Kayu Tangi, Kec. Banjarmasin Utara, Kota Banjarmasin, Kalimantan Selatan 70123',
        'Permohonan Kerjasama Program Magang Mahasiswa Semester Genap 2026',
        NULL,
        'baru',
        3,
        '2025-12-08 08:15:00'
    );
-- Surat 4: Kantor Walikota → Bank Kalsel (Data Statistik)
INSERT INTO `surat` (
        `id`,
        `id_jenis`,
        `nomor_surat`,
        `nomor_agenda`,
        `tanggal_surat`,
        `tanggal_diterima`,
        `dari_instansi`,
        `ke_instansi`,
        `alamat_surat`,
        `perihal`,
        `lampiran_file`,
        `status_surat`,
        `dibuat_oleh`,
        `created_at`
    )
VALUES (
        4,
        2,
        '100/452/Pemkot/XII/2025',
        'AGD-20251229-001',
        '2025-12-29',
        '2025-12-29',
        'Kantor Walikota Banjarmasin',
        'Bank Kalsel Divisi Sekretaris Perusahaan',
        'Jl. RE Martadinata No. 1, Kertak Baru Ilir, Kec. Banjarmasin Tengah, Kota Banjarmasin, Kalimantan Selatan 70231',
        'Permohonan Data Statistik Pelayanan Kesehatan Tahun 2025',
        NULL,
        'proses',
        3,
        '2025-12-29 08:00:00'
    );
-- Surat 5: PMI Banjarmasin → Bank Kalsel (Proposal Donor Darah)
INSERT INTO `surat` (
        `id`,
        `id_jenis`,
        `nomor_surat`,
        `nomor_agenda`,
        `tanggal_surat`,
        `tanggal_diterima`,
        `dari_instansi`,
        `ke_instansi`,
        `alamat_surat`,
        `perihal`,
        `lampiran_file`,
        `status_surat`,
        `dibuat_oleh`,
        `created_at`
    )
VALUES (
        5,
        3,
        '075/PMI-BJM/I/2026',
        'AGD-20260106-001',
        '2026-01-06',
        '2026-01-06',
        'Palang Merah Indonesia Kota Banjarmasin',
        'Bank Kalsel Divisi Sekretaris Perusahaan',
        'Jl. Veteran No. 24, Sungai Bilu, Kec. Banjarmasin Timur, Kota Banjarmasin, Kalimantan Selatan 70236',
        'Surat Pengantar Proposal Kegiatan Donor Darah Massal Peringatan HUT PMI',
        NULL,
        'baru',
        3,
        '2026-01-06 08:20:00'
    );
-- Surat 7: RSUD Ulin → Bank Kalsel (Seminar Stunting)
INSERT INTO `surat` (
        `id`,
        `id_jenis`,
        `nomor_surat`,
        `nomor_agenda`,
        `tanggal_surat`,
        `tanggal_diterima`,
        `dari_instansi`,
        `ke_instansi`,
        `alamat_surat`,
        `perihal`,
        `lampiran_file`,
        `status_surat`,
        `dibuat_oleh`,
        `created_at`
    )
VALUES (
        7,
        1,
        '445/089/RSUD-Ulin/I/2026',
        'AGD-20260123-001',
        '2026-01-23',
        '2026-01-23',
        'RSUD Ulin Banjarmasin',
        'Bank Kalsel Divisi Sekretaris Perusahaan',
        'Jl. A. Yani Km 2 No. 43, Sungai Baru, Kec. Banjarmasin Tengah, Kota Banjarmasin, Kalimantan Selatan 70233',
        'Undangan Seminar Kesehatan Masyarakat dan Pencegahan Stunting Tahun 2026',
        NULL,
        'proses',
        3,
        '2026-01-23 08:45:00'
    );
-- Surat 8: Dinkes → Bank Kalsel (Vaksinasi)
INSERT INTO `surat` (
        `id`,
        `id_jenis`,
        `nomor_surat`,
        `nomor_agenda`,
        `tanggal_surat`,
        `tanggal_diterima`,
        `dari_instansi`,
        `ke_instansi`,
        `alamat_surat`,
        `perihal`,
        `lampiran_file`,
        `status_surat`,
        `dibuat_oleh`,
        `created_at`
    )
VALUES (
        8,
        2,
        '440/215/Dinkes/I/2026',
        'AGD-20260130-001',
        '2026-01-30',
        '2026-01-30',
        'Dinas Kesehatan Kota Banjarmasin',
        'Bank Kalsel Divisi Sekretaris Perusahaan',
        'Jl. Jend. Sudirman No. 1, Antasan Besar, Kec. Banjarmasin Tengah, Kota Banjarmasin, Kalimantan Selatan 70117',
        'Pemberitahuan Jadwal Vaksinasi COVID-19 Booster Tahap III untuk Tenaga Kesehatan',
        NULL,
        'baru',
        3,
        '2026-01-30 08:05:00'
    );
-- Surat 9: KONI → Bank Kalsel (Proposal Dana Olahraga)
INSERT INTO `surat` (
        `id`,
        `id_jenis`,
        `nomor_surat`,
        `nomor_agenda`,
        `tanggal_surat`,
        `tanggal_diterima`,
        `dari_instansi`,
        `ke_instansi`,
        `alamat_surat`,
        `perihal`,
        `lampiran_file`,
        `status_surat`,
        `dibuat_oleh`,
        `created_at`
    )
VALUES (
        9,
        3,
        '027/KONI-BJM/II/2026',
        'AGD-20260207-001',
        '2026-02-07',
        '2026-02-07',
        'KONI Kota Banjarmasin',
        'Bank Kalsel Divisi Sekretaris Perusahaan',
        'Jl. Pramuka No. 28, Sungai Miai, Kec. Banjarmasin Utara, Kota Banjarmasin, Kalimantan Selatan 70124',
        'Permohonan Bantuan Dana Penyelenggaraan Pekan Olahraga Kota (Porkot) 2026',
        NULL,
        'proses',
        3,
        '2026-02-07 08:30:00'
    );
-- Surat 10: Bank Kalsel Cab. Utama → Bank Kalsel Sekretaris (UMKM)
INSERT INTO `surat` (
        `id`,
        `id_jenis`,
        `nomor_surat`,
        `nomor_agenda`,
        `tanggal_surat`,
        `tanggal_diterima`,
        `dari_instansi`,
        `ke_instansi`,
        `alamat_surat`,
        `perihal`,
        `lampiran_file`,
        `status_surat`,
        `dibuat_oleh`,
        `created_at`
    )
VALUES (
        10,
        2,
        'S.043/DIR-BKS/II/2026',
        'AGD-20260214-001',
        '2026-02-14',
        '2026-02-14',
        'Bank Kalsel Kantor Cabang Utama',
        'Bank Kalsel Divisi Sekretaris Perusahaan',
        'Jl. Lambung Mangkurat No. 7, Kertak Baru Ilir, Kec. Banjarmasin Tengah, Kota Banjarmasin, Kalimantan Selatan 70111',
        'Penawaran Program Kredit Usaha Mikro Kecil Menengah (UMKM) Banua Sejahtera 2026',
        NULL,
        'proses',
        3,
        '2026-02-14 08:15:00'
    );
-- ============================================================================
-- 3. SURAT STAKEHOLDERS (Pembuat + Penerima disposisi)
-- ============================================================================
-- Semua surat: Magang sebagai pembuat
INSERT INTO `surat_stakeholders` (
        `surat_id`,
        `user_id`,
        `role_type`,
        `assigned_by`,
        `assigned_at`,
        `is_active`
    )
VALUES (1, 3, 'pembuat', NULL, '2025-12-01 08:00:00', 1),
    (2, 3, 'pembuat', NULL, '2025-12-08 08:15:00', 1),
    (4, 3, 'pembuat', NULL, '2025-12-29 08:00:00', 1),
    (5, 3, 'pembuat', NULL, '2026-01-06 08:20:00', 1),
    (7, 3, 'pembuat', NULL, '2026-01-23 08:45:00', 1),
    (8, 3, 'pembuat', NULL, '2026-01-30 08:05:00', 1),
    (9, 3, 'pembuat', NULL, '2026-02-07 08:30:00', 1),
    (10, 3, 'pembuat', NULL, '2026-02-14 08:15:00', 1);
-- Surat yang sudah didisposisi ke Karyawan (penerima_utama)
INSERT INTO `surat_stakeholders` (
        `surat_id`,
        `user_id`,
        `role_type`,
        `assigned_by`,
        `assigned_at`,
        `is_active`
    )
VALUES (
        1,
        2,
        'penerima_utama',
        3,
        '2025-12-01 09:00:00',
        1
    ),
    (
        4,
        2,
        'penerima_utama',
        3,
        '2025-12-29 09:00:00',
        1
    ),
    (
        7,
        2,
        'penerima_utama',
        3,
        '2026-01-23 09:30:00',
        1
    ),
    (
        9,
        2,
        'penerima_utama',
        3,
        '2026-02-07 09:15:00',
        1
    ),
    (
        10,
        2,
        'penerima_utama',
        3,
        '2026-02-14 09:00:00',
        1
    );
-- Surat yang sudah didisposisi lanjut ke Superadmin (penerima_delegasi)
INSERT INTO `surat_stakeholders` (
        `surat_id`,
        `user_id`,
        `role_type`,
        `assigned_by`,
        `assigned_at`,
        `is_active`
    )
VALUES (
        1,
        1,
        'penerima_delegasi',
        2,
        '2025-12-02 08:30:00',
        1
    ),
    (
        4,
        1,
        'penerima_delegasi',
        2,
        '2025-12-30 08:30:00',
        1
    ),
    (
        10,
        1,
        'penerima_delegasi',
        2,
        '2026-02-15 08:30:00',
        1
    );
-- ============================================================================
-- 4. DISPOSISI (Magang → Karyawan → Superadmin)
-- ============================================================================
-- Surat 1 (1 Des): Full chain
INSERT INTO `disposisi` (
        `id`,
        `id_surat`,
        `dari_user_id`,
        `ke_user_id`,
        `status_disposisi`,
        `catatan`,
        `tanggal_disposisi`,
        `tanggal_respon`
    )
VALUES (
        1,
        1,
        3,
        2,
        'diproses',
        'Mohon ditindaklanjuti, undangan HUT SMA Negeri 2 Banjarmasin tanggal 15 Desember 2025.',
        '2025-12-01 09:00:00',
        '2025-12-01 10:30:00'
    ),
    (
        2,
        1,
        2,
        1,
        'diterima',
        'Diteruskan ke Kepala Bagian untuk konfirmasi kehadiran pejabat.',
        '2025-12-02 08:30:00',
        '2025-12-02 09:00:00'
    );
-- Surat 4 (29 Des): Full chain, superadmin sedang proses
INSERT INTO `disposisi` (
        `id`,
        `id_surat`,
        `dari_user_id`,
        `ke_user_id`,
        `status_disposisi`,
        `catatan`,
        `tanggal_disposisi`,
        `tanggal_respon`
    )
VALUES (
        4,
        4,
        3,
        2,
        'diproses',
        'Permohonan data statistik dari Walikota. Prioritas tinggi.',
        '2025-12-29 09:00:00',
        '2025-12-29 09:45:00'
    ),
    (
        5,
        4,
        2,
        1,
        'diproses',
        'Data sudah dikompilasi, mohon Pak untuk ditinjau dan disetujui sebelum dikirim.',
        '2025-12-30 08:30:00',
        '2025-12-30 09:15:00'
    );
-- Surat 7 (23 Jan): Disposisi ke karyawan, baru diterima
INSERT INTO `disposisi` (
        `id`,
        `id_surat`,
        `dari_user_id`,
        `ke_user_id`,
        `status_disposisi`,
        `catatan`,
        `tanggal_disposisi`,
        `tanggal_respon`
    )
VALUES (
        8,
        7,
        3,
        2,
        'diterima',
        'Undangan seminar stunting dari RSUD Ulin. Mohon dikonfirmasi peserta yang akan dikirim.',
        '2026-01-23 09:30:00',
        '2026-01-23 10:15:00'
    );
-- Surat 9 (7 Feb): Disposisi ke karyawan, baru dikirim
INSERT INTO `disposisi` (
        `id`,
        `id_surat`,
        `dari_user_id`,
        `ke_user_id`,
        `status_disposisi`,
        `catatan`,
        `tanggal_disposisi`,
        `tanggal_respon`
    )
VALUES (
        9,
        9,
        3,
        2,
        'dikirim',
        'Surat permohonan bantuan dana Porkot 2026 dari KONI. Mohon dikaji dan ditindaklanjuti.',
        '2026-02-07 09:15:00',
        NULL
    );
-- Surat 10 (14 Feb): Full chain, superadmin sudah terima
INSERT INTO `disposisi` (
        `id`,
        `id_surat`,
        `dari_user_id`,
        `ke_user_id`,
        `status_disposisi`,
        `catatan`,
        `tanggal_disposisi`,
        `tanggal_respon`
    )
VALUES (
        10,
        10,
        3,
        2,
        'diproses',
        'Penawaran program kredit UMKM dari Bank Kalsel. Mohon ditelaah.',
        '2026-02-14 09:00:00',
        '2026-02-14 09:45:00'
    ),
    (
        11,
        10,
        2,
        1,
        'diterima',
        'Proposal kredit UMKM sudah direview. Diteruskan ke Kepala Bagian untuk tindak lanjut.',
        '2026-02-15 08:30:00',
        '2026-02-15 09:00:00'
    );
-- ============================================================================
-- 5. NOTIFICATIONS
-- ============================================================================
-- Notifikasi disposisi baru ke Karyawan (dari Magang)
INSERT INTO `notifications` (
        `user_id`,
        `type`,
        `title`,
        `message`,
        `surat_id`,
        `disposisi_id`,
        `url`,
        `is_read`,
        `read_at`,
        `created_at`
    )
VALUES (
        2,
        'disposisi_baru',
        'Disposisi Surat Baru',
        'Anda menerima disposisi surat: AGD-20251201-001 - Undangan HUT Ke-55 SMA Negeri 2 Banjarmasin dari Magang',
        1,
        1,
        '/surat_detail.php?id=1',
        1,
        '2025-12-01 10:30:00',
        '2025-12-01 09:00:00'
    ),
    (
        2,
        'disposisi_baru',
        'Disposisi Surat Baru',
        'Anda menerima disposisi surat: AGD-20251229-001 - Permohonan Data Statistik Pelayanan Kesehatan dari Magang',
        4,
        4,
        '/surat_detail.php?id=4',
        1,
        '2025-12-29 09:45:00',
        '2025-12-29 09:00:00'
    ),
    (
        2,
        'disposisi_baru',
        'Disposisi Surat Baru',
        'Anda menerima disposisi surat: AGD-20260123-001 - Undangan Seminar Pencegahan Stunting dari Magang',
        7,
        8,
        '/surat_detail.php?id=7',
        1,
        '2026-01-23 10:15:00',
        '2026-01-23 09:30:00'
    ),
    (
        2,
        'disposisi_baru',
        'Disposisi Surat Baru',
        'Anda menerima disposisi surat: AGD-20260207-001 - Permohonan Dana Pekan Olahraga Kota 2026 dari Magang',
        9,
        9,
        '/surat_detail.php?id=9',
        0,
        NULL,
        '2026-02-07 09:15:00'
    ),
    (
        2,
        'disposisi_baru',
        'Disposisi Surat Baru',
        'Anda menerima disposisi surat: AGD-20260214-001 - Penawaran Kredit UMKM Banua Sejahtera dari Magang',
        10,
        10,
        '/surat_detail.php?id=10',
        1,
        '2026-02-14 09:45:00',
        '2026-02-14 09:00:00'
    );
-- Notifikasi disposisi baru ke Superadmin (dari Karyawan)
INSERT INTO `notifications` (
        `user_id`,
        `type`,
        `title`,
        `message`,
        `surat_id`,
        `disposisi_id`,
        `url`,
        `is_read`,
        `read_at`,
        `created_at`
    )
VALUES (
        1,
        'disposisi_baru',
        'Disposisi Surat Baru',
        'Anda menerima disposisi surat: AGD-20251201-001 - Undangan HUT SMA 2 Banjarmasin dari Karyawan',
        1,
        2,
        '/surat_detail.php?id=1',
        1,
        '2025-12-02 09:00:00',
        '2025-12-02 08:30:00'
    ),
    (
        1,
        'disposisi_baru',
        'Disposisi Surat Baru',
        'Anda menerima disposisi surat: AGD-20251229-001 - Data Statistik Kesehatan dari Karyawan',
        4,
        5,
        '/surat_detail.php?id=4',
        1,
        '2025-12-30 09:15:00',
        '2025-12-30 08:30:00'
    ),
    (
        1,
        'disposisi_baru',
        'Disposisi Surat Baru',
        'Anda menerima disposisi surat: AGD-20260214-001 - Kredit UMKM Banua Sejahtera dari Karyawan',
        10,
        11,
        '/surat_detail.php?id=10',
        1,
        '2026-02-15 09:00:00',
        '2026-02-15 08:30:00'
    );
-- Notifikasi surat masuk
INSERT INTO `notifications` (
        `user_id`,
        `type`,
        `title`,
        `message`,
        `surat_id`,
        `disposisi_id`,
        `url`,
        `is_read`,
        `read_at`,
        `created_at`
    )
VALUES (
        2,
        'surat_masuk',
        'Surat Baru Masuk',
        'Surat baru: AGD-20251208-001 - Permohonan Kerjasama Program Magang Mahasiswa',
        2,
        NULL,
        '/surat_detail.php?id=2',
        1,
        '2025-12-08 09:00:00',
        '2025-12-08 08:15:00'
    ),
    (
        2,
        'surat_masuk',
        'Surat Baru Masuk',
        'Surat baru: AGD-20260106-001 - Proposal Kegiatan Donor Darah Massal',
        5,
        NULL,
        '/surat_detail.php?id=5',
        1,
        '2026-01-06 09:00:00',
        '2026-01-06 08:20:00'
    ),
    (
        2,
        'surat_masuk',
        'Surat Baru Masuk',
        'Surat baru: AGD-20260130-001 - Jadwal Vaksinasi COVID-19 Booster Tahap III',
        8,
        NULL,
        '/surat_detail.php?id=8',
        0,
        NULL,
        '2026-01-30 08:05:00'
    );