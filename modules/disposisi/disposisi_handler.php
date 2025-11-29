<?php
// modules/disposisi/disposisi_handler.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/disposisi_service.php';
require_once __DIR__ . '/../surat/surat_service.php';

requireLogin();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user = getCurrentUser();

/**
 * Build redirect URL dengan path yang benar
 * Menangani berbagai format input redirect path
 */
function buildRedirectUrl($redirectPath, $params = []) {
    // Normalisasi path
    $redirectPath = trim($redirectPath);
    
    // Parse URL untuk mendapatkan path dan query
    $parsedUrl = parse_url($redirectPath);
    $path = $parsedUrl['path'] ?? $redirectPath;
    
    // Ambil existing query parameters
    $existingParams = [];
    if (isset($parsedUrl['query'])) {
        parse_str($parsedUrl['query'], $existingParams);
    }
    
    // Merge dengan params baru (params baru override existing)
    $allParams = array_merge($existingParams, $params);
    
    // Deteksi dan normalisasi path
    // Jika path sudah absolut dengan BASE_URL, gunakan langsung
    if (strpos($redirectPath, BASE_URL) === 0) {
        $finalPath = $redirectPath;
    }
    // Jika path sudah include '../../public/', gunakan langsung
    elseif (strpos($path, '../../public/') === 0) {
        $finalPath = $path;
    }
    // Jika path sudah mulai dengan 'public/', tambahkan ../../ saja
    elseif (strpos($path, 'public/') === 0) {
        $finalPath = '../../' . $path;
    }
    // Jika path adalah BASE_URL relatif yang include /public/
    elseif (strpos($path, '/public/') !== false) {
        // Extract dari /public/ ke akhir
        $publicPos = strpos($path, '/public/');
        $relativePath = substr($path, $publicPos + 8); // +8 untuk skip '/public/'
        $finalPath = '../../public/' . $relativePath;
    }
    // Jika hanya nama file, tambahkan prefix lengkap
    else {
        $filename = basename($path);
        $finalPath = '../../public/' . $filename;
    }
    
    // Build final URL dengan query string
    if (!empty($allParams)) {
        $finalPath .= '?' . http_build_query($allParams);
    }
    
    return $finalPath;
}

try {
    switch ($action) {
        case 'create':
            $suratId = (int)$_POST['id_surat'];
            $keUserId = (int)$_POST['ke_user_id'];
            $catatan = sanitize($_POST['catatan'] ?? '');
            
            // Validate surat exists
            $surat = SuratService::getById($suratId);
            if (!$surat) {
                throw new Exception('Surat tidak ditemukan');
            }
            
            // Check if user can dispose
            if (!DisposisiService::canDispose($user['id'], $suratId) && $surat['dibuat_oleh'] != $user['id']) {
                throw new Exception('Anda tidak memiliki akses untuk mendisposisi surat ini');
            }
            
            // Validate target user
            $targetUser = dbSelectOne("SELECT id, nama_lengkap FROM users WHERE id = ? AND status_aktif = 1", [$keUserId], 'i');
            if (!$targetUser) {
                throw new Exception('User tujuan tidak valid');
            }
            
            // Prevent disposing to self
            if ($keUserId == $user['id']) {
                throw new Exception('Tidak dapat mendisposisi ke diri sendiri');
            }
            
            $data = [
                'id_surat' => $suratId,
                'dari_user_id' => $user['id'],
                'ke_user_id' => $keUserId,
                'status_disposisi' => 'dikirim',
                'catatan' => $catatan
            ];
            
            $disposisiId = DisposisiService::create($data);
            
            // Update surat status to 'proses' if still 'baru'
            if ($surat['status_surat'] === 'baru') {
                SuratService::updateStatus($suratId, 'proses');
            }
            
            logActivity($user['id'], 'disposisi_surat', "Mendisposisi surat {$surat['nomor_agenda']} ke {$targetUser['nama_lengkap']}");
            
            setFlash('success', 'Disposisi berhasil dikirim');
            
            // Build redirect URL dengan benar
            $redirectBase = $_POST['redirect_url'] ?? $_POST['redirect'] ?? "surat_detail.php?id={$suratId}";
            $redirectUrl = buildRedirectUrl($redirectBase, ['success' => 'sent']);
            
            header("Location: {$redirectUrl}");
            exit;
            break;
            
        case 'update_status':
            $id = (int)$_POST['id'];
            $status = sanitize($_POST['status']);
            $catatan = sanitize($_POST['catatan'] ?? '');
            
            // Validate status
            $allowedStatus = ['diterima', 'diproses', 'selesai', 'ditolak'];
            if (!in_array($status, $allowedStatus)) {
                throw new Exception('Status tidak valid');
            }
            
            // Get disposisi
            $disposisi = DisposisiService::getById($id);
            if (!$disposisi) {
                throw new Exception('Disposisi tidak ditemukan');
            }
            
            // Check if user is the recipient
            if ($disposisi['ke_user_id'] != $user['id']) {
                throw new Exception('Anda tidak memiliki akses untuk mengubah disposisi ini');
            }
            
            DisposisiService::updateStatus($id, $status, $catatan);
            
            // Update surat status based on disposition status
            if ($status === 'selesai') {
                // Check if all dispositions are completed
                $allDispositions = DisposisiService::getHistoryBySurat($disposisi['id_surat']);
                $allCompleted = true;
                foreach ($allDispositions as $disp) {
                    if ($disp['status_disposisi'] !== 'selesai' && $disp['id'] != $id) {
                        $allCompleted = false;
                        break;
                    }
                }
                
                if ($allCompleted) {
                    SuratService::updateStatus($disposisi['id_surat'], 'disetujui');
                }
            } elseif ($status === 'ditolak') {
                SuratService::updateStatus($disposisi['id_surat'], 'ditolak');
            }
            
            logActivity($user['id'], 'update_disposisi', "Mengubah status disposisi ID {$id} menjadi {$status}");
            
            setFlash('success', 'Status disposisi berhasil diperbarui');
            
            // Build redirect URL dengan benar
            $redirectBase = $_POST['redirect'] ?? 'disposisi_inbox.php';
            $redirectUrl = buildRedirectUrl($redirectBase, ['success' => 'updated']);
            
            header("Location: {$redirectUrl}");
            exit;
            break;
            
        default:
            throw new Exception('Action tidak valid');
    }
    
} catch (Exception $e) {
    setFlash('error', $e->getMessage());
    
    $redirectBase = $_POST['redirect'] ?? $_GET['redirect'] ?? 'disposisi_inbox.php';
    $redirectUrl = buildRedirectUrl($redirectBase, ['error' => 'process_failed']);
    
    header("Location: {$redirectUrl}");
    exit;
}
