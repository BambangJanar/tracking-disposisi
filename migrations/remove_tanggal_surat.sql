-- Migration: Remove tanggal_surat column
-- Date: 2026-04-02
-- Description: Menghapus kolom tanggal_surat, semua tanggal menggunakan tanggal_diterima saja

-- Step 1: Salin nilai tanggal_surat ke tanggal_diterima jika tanggal_diterima kosong
UPDATE surat SET tanggal_diterima = tanggal_surat WHERE tanggal_diterima IS NULL;

-- Step 2: Untuk data yang tanggal_diterima masih NULL (fallback), isi dengan created_at
UPDATE surat SET tanggal_diterima = DATE(created_at) WHERE tanggal_diterima IS NULL;

-- Step 3: Hapus kolom tanggal_surat
ALTER TABLE surat DROP COLUMN tanggal_surat;
