<?php
// modules/surat/surat_handler.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/surat_service.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Anda harus login']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user = getCurrentUser();

if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', __DIR__ . '/../../uploads/surat/');
}

try {
    switch ($action) {
        case 'create':
            $data = [
                'id_jenis' => $_POST['id_jenis'] ?? '',
                'nomor_surat' => $_POST['nomor_surat'] ?? '',
                'tanggal_diterima' => !empty($_POST['tanggal_diterima']) ? $_POST['tanggal_diterima'] : null,
                'dari_instansi' => $_POST['dari_instansi'] ?? '',
                'ke_instansi' => $_POST['ke_instansi'] ?? '',
                'alamat_surat' => $_POST['alamat_surat'] ?? '',
                'perihal' => $_POST['perihal'] ?? '',
                'kegiatan' => $_POST['kegiatan'] ?? '',
                'tingkat_surat' => $_POST['tingkat_surat'] ?? 'biasa',
                'dibuat_oleh' => $user['id'],
                'lampiran_file' => null
            ];

            // Validasi input wajib
            if (empty($data['id_jenis']) || empty($data['perihal'])) {
                throw new Exception("Mohon lengkapi data wajib (Jenis, Perihal)");
            }

            if (isset($_FILES['lampiran_file']) && $_FILES['lampiran_file']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = SuratService::uploadLampiran($_FILES['lampiran_file']);
                if ($uploadResult['success']) {
                    $data['lampiran_file'] = $uploadResult['filename'];
                } else {
                    throw new Exception($uploadResult['message']);
                }
            }

            if (SuratService::create($data)) {
                logActivity($user['id'], 'tambah_surat', "Menambahkan surat baru");
                echo json_encode(['status' => 'success', 'message' => 'Surat berhasil ditambahkan']);
            } else {
                throw new Exception("Gagal menyimpan data surat ke database");
            }
            break;

        case 'update':
            $id = $_POST['id'] ?? 0;
            if (!$id) throw new Exception("ID Surat tidak valid");

            $oldData = SuratService::getById($id);
            if (!$oldData) throw new Exception("Data surat tidak ditemukan");

            $data = [
                'id_jenis' => $_POST['id_jenis'] ?? '',
                'nomor_surat' => $_POST['nomor_surat'] ?? '',
                'tanggal_diterima' => !empty($_POST['tanggal_diterima']) ? $_POST['tanggal_diterima'] : null,
                'dari_instansi' => $_POST['dari_instansi'] ?? '',
                'ke_instansi' => $_POST['ke_instansi'] ?? '',
                'alamat_surat' => $_POST['alamat_surat'] ?? '',
                'perihal' => $_POST['perihal'] ?? '',
                'kegiatan' => $_POST['kegiatan'] ?? '',
                'tingkat_surat' => $_POST['tingkat_surat'] ?? 'biasa',
                'lampiran_file' => $oldData['lampiran_file']
            ];

            if (isset($_FILES['lampiran_file']) && $_FILES['lampiran_file']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = SuratService::uploadLampiran($_FILES['lampiran_file']);
                if ($uploadResult['success']) {
                    $data['lampiran_file'] = $uploadResult['filename'];
                    if ($oldData['lampiran_file'] && file_exists(UPLOAD_PATH . $oldData['lampiran_file'])) {
                        unlink(UPLOAD_PATH . $oldData['lampiran_file']);
                    }
                } else {
                    throw new Exception($uploadResult['message']);
                }
            }

            if (SuratService::update($id, $data)) {
                logActivity($user['id'], 'edit_surat', "Mengupdate surat ID: $id");
                echo json_encode(['status' => 'success', 'message' => 'Surat berhasil diperbarui']);
            } else {
                throw new Exception("Gagal mengupdate surat");
            }
            break;

        case 'delete':
            $id = $_POST['id'] ?? 0;
            if (!$id) throw new Exception("ID Surat tidak valid");

            $surat = SuratService::getById($id);
            if (!$surat) throw new Exception("Surat tidak ditemukan");

            if (SuratService::delete($id)) {
                if ($surat['lampiran_file'] && file_exists(UPLOAD_PATH . $surat['lampiran_file'])) {
                    unlink(UPLOAD_PATH . $surat['lampiran_file']);
                }
                logActivity($user['id'], 'hapus_surat', "Menghapus surat ID: $id");
                echo json_encode(['status' => 'success', 'message' => 'Surat berhasil dihapus']);
            } else {
                throw new Exception("Gagal menghapus surat");
            }
            break;

        case 'arsipkan':
            $id = $_POST['id'] ?? 0;
            if (!$id) throw new Exception("ID Surat tidak valid");

            if (SuratService::arsipkan($id)) {
                logActivity($user['id'], 'arsip_surat', "Mengarsipkan surat ID: $id");
                echo json_encode(['status' => 'success', 'message' => 'Surat berhasil diarsipkan']);
            } else {
                throw new Exception("Gagal mengarsipkan surat");
            }
            break;

        case 'update_status':
            $id = $_POST['id'] ?? 0;
            $status = $_POST['status'] ?? '';
            $alasanPenolakan = isset($_POST['alasan_penolakan']) ? trim($_POST['alasan_penolakan']) : null;

            if (!$id) throw new Exception("ID Surat tidak valid");
            if (!in_array($status, ['baru', 'proses', 'disetujui', 'ditolak', 'arsip'])) {
                throw new Exception("Status tidak valid");
            }

            // Validasi: jika ditolak, alasan wajib diisi
            if ($status === 'ditolak' && empty($alasanPenolakan)) {
                throw new Exception("Alasan penolakan wajib diisi");
            }

            if (SuratService::updateStatus($id, $status, $alasanPenolakan)) {
                $logMsg = "Mengubah status surat ID: $id menjadi $status";
                if ($alasanPenolakan) {
                    $logMsg .= " | Alasan: $alasanPenolakan";
                }
                logActivity($user['id'], 'update_status_surat', $logMsg);
                echo json_encode(['status' => 'success', 'message' => 'Status surat berhasil diubah']);
            } else {
                throw new Exception("Gagal mengubah status surat");
            }
            break;

        case 'unarsip':
            // Mengeluarkan surat dari arsip (kembali ke status 'baru')
            $id = $_POST['id'] ?? 0;
            if (!$id) throw new Exception("ID Surat tidak valid");

            $surat = SuratService::getById($id);
            if (!$surat) throw new Exception("Surat tidak ditemukan");
            if ($surat['status_surat'] !== 'arsip') throw new Exception("Surat ini tidak sedang diarsipkan");

            // Hanya Admin (role 1) dan Karyawan (role 2) yang bisa unarsip
            $userRole = $user['id_role'] ?? 3;
            if ($userRole == 3) {
                throw new Exception("Anda tidak memiliki akses untuk mengeluarkan surat dari arsip");
            }

            if (SuratService::updateStatus($id, 'baru')) {
                logActivity($user['id'], 'unarsip_surat', "Mengeluarkan surat ID: $id dari arsip");
                echo json_encode(['status' => 'success', 'message' => 'Surat berhasil dikeluarkan dari arsip']);
            } else {
                throw new Exception("Gagal mengeluarkan surat dari arsip");
            }
            break;

        case 'delete_permanent':
            // Hapus surat secara permanen (dari arsip)
            $id = $_POST['id'] ?? 0;
            if (!$id) throw new Exception("ID Surat tidak valid");

            $surat = SuratService::getById($id);
            if (!$surat) throw new Exception("Surat tidak ditemukan");

            // Hanya Admin (role 1) dan Karyawan (role 2) yang bisa hapus permanen
            $userRole = $user['id_role'] ?? 3;
            if ($userRole == 3) {
                throw new Exception("Anda tidak memiliki akses untuk menghapus surat secara permanen");
            }

            if (SuratService::delete($id)) {
                // Hapus file lampiran jika ada
                if ($surat['lampiran_file'] && file_exists(UPLOAD_PATH . $surat['lampiran_file'])) {
                    unlink(UPLOAD_PATH . $surat['lampiran_file']);
                }
                logActivity($user['id'], 'hapus_permanen_surat', "Menghapus permanen surat ID: $id dari arsip");
                echo json_encode(['status' => 'success', 'message' => 'Surat berhasil dihapus secara permanen']);
            } else {
                throw new Exception("Gagal menghapus surat");
            }
            break;

        case 'import_csv':
            // Hanya Kepala Bagian (superadmin) dan Karyawan (admin) yang bisa import
            if (!hasRole(['superadmin', 'admin'])) {
                throw new Exception("Anda tidak memiliki akses untuk mengimpor data");
            }

            // Validasi file upload
            if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("File CSV tidak ditemukan atau gagal diupload");
            }

            $file = $_FILES['csv_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($ext !== 'csv') {
                throw new Exception("Format file harus CSV (.csv)");
            }

            // Baca file CSV
            $handle = fopen($file['tmp_name'], 'r');
            if (!$handle) {
                throw new Exception("Gagal membaca file CSV");
            }

            // Ambil daftar jenis_surat untuk mapping nama -> ID
            $conn = getConnection();
            $jenisResult = $conn->query("SELECT id, nama_jenis FROM jenis_surat");
            $jenisMap = [];
            while ($row = $jenisResult->fetch_assoc()) {
                $jenisMap[strtolower(trim($row['nama_jenis']))] = $row['id'];
            }

            // Daftar tingkat yang valid
            $validTingkat = ['biasa', 'sedang', 'penting', 'mendesak'];

            // Skip baris header
            $header = fgetcsv($handle);

            $successCount = 0;
            $errorRows = [];
            $lineNumber = 1; // Header = baris 1

            while (($row = fgetcsv($handle)) !== false) {
                $lineNumber++;

                // Lewati baris kosong
                if (empty(array_filter($row))) continue;

                // Pastikan jumlah kolom cukup (minimal 9)
                if (count($row) < 9) {
                    $errorRows[] = "Baris $lineNumber: Jumlah kolom kurang (butuh 9 kolom)";
                    continue;
                }

                // Mapping kolom CSV ke data surat
                $jenisNama   = strtolower(trim($row[0]));
                $nomorSurat  = trim($row[1]);
                $tglDiterima = trim($row[2]);
                $dariInst    = trim($row[3]);
                $keInst      = trim($row[4]);
                $alamat      = trim($row[5]);
                $perihal     = trim($row[6]);
                $kegiatan    = trim($row[7]);
                $tingkat     = strtolower(trim($row[8] ?? 'biasa'));

                // Validasi: Perihal wajib ada
                if (empty($perihal)) {
                    $errorRows[] = "Baris $lineNumber: Kolom Perihal wajib diisi";
                    continue;
                }

                // Parsing dan Validasi Tanggal
                if (!empty($tglDiterima)) {
                    $parsedDate = strtotime($tglDiterima);
                    if ($parsedDate !== false) {
                        $tglDiterima = date('Y-m-d', $parsedDate);
                    } else {
                        $errorRows[] = "Baris $lineNumber: Format tanggal '$tglDiterima' tidak dikenali";
                        continue;
                    }
                }

                // Resolve jenis surat
                $idJenis = $jenisMap[$jenisNama] ?? null;
                if (!$idJenis) {
                    // Coba cari parsial (misal "masuk" cocok dengan "surat masuk")
                    foreach ($jenisMap as $key => $val) {
                        if (strpos($key, $jenisNama) !== false || strpos($jenisNama, $key) !== false) {
                            $idJenis = $val;
                            break;
                        }
                    }
                    if (!$idJenis) {
                        $errorRows[] = "Baris $lineNumber: Jenis surat '$row[0]' tidak ditemukan di master data";
                        continue;
                    }
                }

                // Validasi tingkat
                if (!in_array($tingkat, $validTingkat)) {
                    $tingkat = 'biasa';
                }

                // Siapkan data untuk create
                $data = [
                    'id_jenis'        => $idJenis,
                    'nomor_surat'     => $nomorSurat,
                    'tanggal_diterima'=> !empty($tglDiterima) ? $tglDiterima : null,
                    'dari_instansi'   => $dariInst,
                    'ke_instansi'     => $keInst,
                    'alamat_surat'    => $alamat,
                    'perihal'         => $perihal,
                    'kegiatan'        => $kegiatan,
                    'tingkat_surat'   => $tingkat,
                    'dibuat_oleh'     => $user['id'],
                    'lampiran_file'   => null
                ];

                try {
                    if (SuratService::create($data)) {
                        $successCount++;
                    } else {
                        $errorRows[] = "Baris $lineNumber: Gagal menyimpan ke database";
                    }
                } catch (Exception $rowErr) {
                    $errorRows[] = "Baris $lineNumber: " . $rowErr->getMessage();
                }
            }

            fclose($handle);

            // Log aktivitas
            logActivity($user['id'], 'import_csv', "Import CSV: $successCount surat berhasil");

            // Kirim respons
            $message = "$successCount data surat berhasil diimpor.";
            if (!empty($errorRows)) {
                $message .= " " . count($errorRows) . " baris gagal.";
            }

            echo json_encode([
                'status'  => $successCount > 0 ? 'success' : 'error',
                'message' => $message,
                'details' => $errorRows
            ]);
            break;

        default:
            throw new Exception("Aksi tidak valid");
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
