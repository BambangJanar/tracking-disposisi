<?php
// modules/surat/surat_handler.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/surat_service.php';

requireLogin();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user = getCurrentUser();

try {
    switch ($action) {
        case 'create':
            // 1. Validasi Input
            if (empty($_POST['id_jenis']) || empty($_POST['nomor_surat']) || 
                empty($_POST['tanggal_surat']) || empty($_POST['perihal'])) {
                throw new Exception('Mohon lengkapi semua field wajib (*) field');
            }

            $jenisId = (int)$_POST['id_jenis'];
            
            // 2. Generate Nomor Agenda Otomatis
            $nomorAgenda = generateNomorAgenda($jenisId);

            // 3. Siapkan Data
            $data = [
                'id_jenis'         => $jenisId,
                'nomor_agenda'     => $nomorAgenda,
                'nomor_surat'      => sanitize($_POST['nomor_surat']),
                'tanggal_surat'    => sanitize($_POST['tanggal_surat']),
                'tanggal_diterima' => !empty($_POST['tanggal_diterima']) ? sanitize($_POST['tanggal_diterima']) : null,
                'dari_instansi'    => !empty($_POST['dari_instansi']) ? sanitize($_POST['dari_instansi']) : null,
                'ke_instansi'      => !empty($_POST['ke_instansi']) ? sanitize($_POST['ke_instansi']) : null,
                'alamat_surat'     => sanitize($_POST['alamat_surat'] ?? ''),
                'perihal'          => sanitize($_POST['perihal']),
                'status_surat'     => 'baru',
                'dibuat_oleh'      => $user['id'],
                'lampiran_file'    => null
            ];

            // 4. Upload File (Jika ada)
            if (isset($_FILES['lampiran_file']) && $_FILES['lampiran_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $upload = uploadFile($_FILES['lampiran_file']);
                if (!$upload['success']) {
                    throw new Exception($upload['message']);
                }
                $data['lampiran_file'] = $upload['filename'];
            }

            // 5. Simpan ke Database
            $suratId = SuratService::create($data);
            
            if (!$suratId) {
                throw new Exception('Gagal menyimpan data surat');
            }

            logActivity($user['id'], 'tambah_surat', "Menambah surat: $nomorAgenda");
            setFlash('success', 'Surat berhasil ditambahkan');
            
            // Redirect kembali ke halaman surat
            header("Location: " . BASE_URL . "/surat.php?success=added");
            exit;
            break;

        case 'update':
            // Pastikan user punya akses
            requireRole(['admin', 'superadmin']);

            $id = (int)($_POST['id'] ?? 0);
            if ($id < 1) throw new Exception('ID Surat tidak valid');

            $surat = SuratService::getById($id);
            if (!$surat) throw new Exception('Surat tidak ditemukan');

            // Validasi Input
            if (empty($_POST['id_jenis']) || empty($_POST['nomor_surat']) || empty($_POST['perihal'])) {
                throw new Exception('Data wajib tidak boleh kosong');
            }

            $data = [
                'id_jenis'         => (int)$_POST['id_jenis'],
                'nomor_surat'      => sanitize($_POST['nomor_surat']),
                'tanggal_surat'    => sanitize($_POST['tanggal_surat']),
                'tanggal_diterima' => !empty($_POST['tanggal_diterima']) ? sanitize($_POST['tanggal_diterima']) : null,
                'dari_instansi'    => !empty($_POST['dari_instansi']) ? sanitize($_POST['dari_instansi']) : null,
                'ke_instansi'      => !empty($_POST['ke_instansi']) ? sanitize($_POST['ke_instansi']) : null,
                'alamat_surat'     => sanitize($_POST['alamat_surat'] ?? ''),
                'perihal'          => sanitize($_POST['perihal']),
                'status_surat'     => $surat['status_surat'], // Status tidak berubah lewat edit biasa
                'lampiran_file'    => $surat['lampiran_file']
            ];

            // Cek Upload File Baru
            if (isset($_FILES['lampiran_file']) && $_FILES['lampiran_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $upload = uploadFile($_FILES['lampiran_file'], $surat['lampiran_file']); // Replace file lama
                if (!$upload['success']) {
                    throw new Exception($upload['message']);
                }
                $data['lampiran_file'] = $upload['filename'];
            }

            SuratService::update($id, $data);
            
            logActivity($user['id'], 'edit_surat', "Mengedit surat ID: $id");
            setFlash('success', 'Surat berhasil diperbarui');
            
            header("Location: " . BASE_URL . "/surat.php?success=updated");
            exit;
            break;

        case 'delete':
            requireRole(['superadmin']); // Hanya superadmin yg bisa hapus

            $id = (int)($_POST['id'] ?? 0);
            $surat = SuratService::getById($id);
            if (!$surat) throw new Exception('Surat tidak ditemukan');

            // Hapus file fisik jika ada
            if (!empty($surat['lampiran_file']) && file_exists(UPLOAD_DIR . $surat['lampiran_file'])) {
                unlink(UPLOAD_DIR . $surat['lampiran_file']);
            }

            SuratService::delete($id);
            
            logActivity($user['id'], 'hapus_surat', "Menghapus surat ID: $id");
            setFlash('success', 'Surat berhasil dihapus');
            
            header("Location: " . BASE_URL . "/surat.php?success=deleted");
            exit;
            break;

        case 'arsipkan':
            requireRole(['admin', 'superadmin']);

            $id = (int)($_POST['id'] ?? 0);
            SuratService::updateStatus($id, 'arsip');
            
            logActivity($user['id'], 'arsip_surat', "Mengarsipkan surat ID: $id");
            setFlash('success', 'Surat berhasil diarsipkan');
            
            header("Location: " . BASE_URL . "/surat.php?success=archived");
            exit;
            break;

        default:
            throw new Exception('Action tidak valid');
    }

} catch (Exception $e) {
    setFlash('error', $e->getMessage());
    // Redirect balik ke halaman surat jika error
    header("Location: " . BASE_URL . "/surat.php?error=process_failed");
    exit;
}
?>