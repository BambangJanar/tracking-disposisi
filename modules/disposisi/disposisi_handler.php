<?php
// modules/disposisi/disposisi_handler.php

// 1. PENTING: Mulai buffering di baris paling atas untuk menangkap output tak terduga (seperti notice/warning)
ob_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/disposisi_service.php';
require_once __DIR__ . '/../surat/surat_service.php';

// Deteksi apakah request adalah AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// 2. Cek session. Jika habis dan ini AJAX, kirim JSON 401 agar JS bisa redirect.
if (!isLoggedIn()) {
    ob_end_clean(); // Hapus output HTML/error sebelumnya
    http_response_code(401); // Unauthorized
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Sesi login Anda telah habis. Silakan refresh halaman.'
    ]);
    exit;
}

requireLogin();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user = getCurrentUser();

/**
 * Fungsi Helper: Generate HTML Timeline
 * Digunakan untuk merender ulang tampilan timeline via AJAX tanpa refresh halaman
 */
function generateTimelineHtml($history) {
    if (empty($history)) {
        return '
        <div class="text-center py-8 text-gray-500">
            <i class="fas fa-inbox text-4xl mb-2"></i>
            <p>Belum ada disposisi untuk surat ini</p>
        </div>';
    }

    $html = '<div class="relative">';
    $html .= '<div class="absolute left-6 top-0 bottom-0 w-0.5 bg-gray-200"></div>'; // Garis vertikal
    $html .= '<div class="space-y-6">';

    foreach ($history as $disp) {
        // Tentukan styling berdasarkan status
        $bgClass = 'bg-blue-100';
        $iconClass = 'fa-paper-plane text-blue-600';
        
        if ($disp['status_disposisi'] === 'selesai') {
            $bgClass = 'bg-green-100'; $iconClass = 'fa-check text-green-600';
        } elseif ($disp['status_disposisi'] === 'ditolak') {
            $bgClass = 'bg-red-100'; $iconClass = 'fa-times text-red-600';
        } elseif ($disp['status_disposisi'] === 'diproses') {
            $bgClass = 'bg-yellow-100'; $iconClass = 'fa-spinner text-yellow-600';
        } elseif ($disp['status_disposisi'] === 'diterima') {
            $bgClass = 'bg-indigo-100'; $iconClass = 'fa-envelope-open text-indigo-600';
        }

        $badgeClass = getDisposisiStatusBadge($disp['status_disposisi']);
        $statusLabel = ucfirst($disp['status_disposisi']);
        
        $catatanHtml = '';
        if ($disp['catatan']) {
            $catatanHtml = '<div class="mt-2 p-2 bg-white rounded border-l-4 border-blue-500"><p class="text-sm text-gray-700">'.nl2br(sanitize($disp['catatan'])).'</p></div>';
        }
        
        $tglDisposisi = formatDateTime($disp['tanggal_disposisi']);
        $tglResponHtml = '';
        if ($disp['tanggal_respon']) {
            $tglResponHtml = '<span class="ml-4"><i class="fas fa-check-circle mr-1"></i> Respon: '.formatDateTime($disp['tanggal_respon']).'</span>';
        }

        // Susun HTML Item
        $html .= '
        <div class="relative pl-14">
            <div class="absolute left-0 w-12 h-12 rounded-full flex items-center justify-center '.$bgClass.'">
                <i class="fas '.$iconClass.'"></i>
            </div>
            
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="flex items-start justify-between mb-2">
                    <div class="flex-1">
                        <p class="font-semibold text-gray-800">
                            '.sanitize($disp['dari_user_nama']).' 
                            <i class="fas fa-arrow-right text-gray-400 mx-2"></i>
                            '.sanitize($disp['ke_user_nama']).'
                        </p>
                        <p class="text-xs text-gray-500">
                            '.getRoleLabel($disp['dari_user_role']).' → '.getRoleLabel($disp['ke_user_role']).'
                        </p>
                    </div>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full '.$badgeClass.'">
                        '.$statusLabel.'
                    </span>
                </div>
                
                '.$catatanHtml.'
                
                <div class="mt-2 flex items-center text-xs text-gray-500">
                    <i class="fas fa-clock mr-1"></i>
                    Dikirim: '.$tglDisposisi.'
                    '.$tglResponHtml.'
                </div>
            </div>
        </div>';
    }

    $html .= '</div></div>';
    return $html;
}

/**
 * Helper: Build Redirect URL
 */
function buildRedirectUrl($redirectPath, $params = []) {
    $redirectPath = trim($redirectPath);
    $parsedUrl = parse_url($redirectPath);
    $path = $parsedUrl['path'] ?? $redirectPath;
    
    $existingParams = [];
    if (isset($parsedUrl['query'])) {
        parse_str($parsedUrl['query'], $existingParams);
    }
    
    $allParams = array_merge($existingParams, $params);
    
    // Normalisasi path
    if (strpos($redirectPath, BASE_URL) === 0) {
        $finalPath = $redirectPath;
    } elseif (strpos($path, '../../public/') === 0) {
        $finalPath = $path;
    } elseif (strpos($path, 'public/') === 0) {
        $finalPath = '../../' . $path;
    } elseif (strpos($path, '/public/') !== false) {
        $publicPos = strpos($path, '/public/');
        $relativePath = substr($path, $publicPos + 8);
        $finalPath = '../../public/' . $relativePath;
    } else {
        $filename = basename($path);
        $finalPath = '../../public/' . $filename;
    }
    
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
            
            // Validasi surat
            $surat = SuratService::getById($suratId);
            if (!$surat) throw new Exception('Surat tidak ditemukan');
            
            // Validasi hak akses
            if (!DisposisiService::canDispose($user['id'], $suratId) && $surat['dibuat_oleh'] != $user['id']) {
                throw new Exception('Anda tidak memiliki akses untuk mendisposisi surat ini');
            }
            
            // Validasi user tujuan
            $targetUser = dbSelectOne("SELECT id, nama_lengkap FROM users WHERE id = ? AND status_aktif = 1", [$keUserId], 'i');
            if (!$targetUser) throw new Exception('User tujuan tidak valid');
            
            // Cek self-disposisi
            if ($keUserId == $user['id']) throw new Exception('Tidak dapat mendisposisi ke diri sendiri');
            
            $data = [
                'id_surat' => $suratId,
                'dari_user_id' => $user['id'],
                'ke_user_id' => $keUserId,
                'status_disposisi' => 'dikirim',
                'catatan' => $catatan
            ];
            
            DisposisiService::create($data);
            
            // Update status surat jika masih baru
            if ($surat['status_surat'] === 'baru') {
                SuratService::updateStatus($suratId, 'proses');
            }
            
            logActivity($user['id'], 'disposisi_surat', "Mendisposisi surat {$surat['nomor_agenda']} ke {$targetUser['nama_lengkap']}");
            
            // --- JIKA REQUEST ADALAH AJAX ---
            if ($isAjax) {
                // Generate timeline terbaru
                $newHistory = DisposisiService::getHistoryBySurat($suratId);
                $html = generateTimelineHtml($newHistory);
                
                // 3. PENTING: Bersihkan buffer sebelum kirim JSON
                ob_end_clean(); 
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Disposisi berhasil dikirim',
                    'html' => $html,
                    'count' => count($newHistory)
                ]);
                exit;
            }

            // Fallback untuk request biasa (non-AJAX)
            setFlash('success', 'Disposisi berhasil dikirim');
            $redirectBase = $_POST['redirect_url'] ?? $_POST['redirect'] ?? "surat_detail.php?id={$suratId}";
            $redirectUrl = buildRedirectUrl($redirectBase, ['success' => 'sent']);
            header("Location: {$redirectUrl}");
            exit;
            break;
            
        case 'update_status':
            $id = (int)$_POST['id'];
            $status = sanitize($_POST['status']);
            $catatan = sanitize($_POST['catatan'] ?? '');
            
            $allowedStatus = ['diterima', 'diproses', 'selesai', 'ditolak'];
            if (!in_array($status, $allowedStatus)) throw new Exception('Status tidak valid');
            
            $disposisi = DisposisiService::getById($id);
            if (!$disposisi) throw new Exception('Disposisi tidak ditemukan');
            
            if ($disposisi['ke_user_id'] != $user['id']) {
                throw new Exception('Anda tidak memiliki akses untuk mengubah disposisi ini');
            }
            
            DisposisiService::updateStatus($id, $status, $catatan);
            
            // Update status surat induk jika perlu
            if ($status === 'selesai') {
                $allDispositions = DisposisiService::getHistoryBySurat($disposisi['id_surat']);
                $allCompleted = true;
                foreach ($allDispositions as $disp) {
                    if ($disp['status_disposisi'] !== 'selesai' && $disp['id'] != $id) {
                        $allCompleted = false; break;
                    }
                }
                if ($allCompleted) SuratService::updateStatus($disposisi['id_surat'], 'disetujui');
            } elseif ($status === 'ditolak') {
                SuratService::updateStatus($disposisi['id_surat'], 'ditolak');
            }
            
            logActivity($user['id'], 'update_disposisi', "Mengubah status disposisi ID {$id} menjadi {$status}");
            
            // Jika AJAX
            if ($isAjax) {
                ob_end_clean();
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Status disposisi berhasil diperbarui'
                ]);
                exit;
            }

            setFlash('success', 'Status disposisi berhasil diperbarui');
            $redirectBase = $_POST['redirect'] ?? 'disposisi_inbox.php';
            $redirectUrl = buildRedirectUrl($redirectBase, ['success' => 'updated']);
            header("Location: {$redirectUrl}");
            exit;
            break;
            
        default:
            throw new Exception('Action tidak valid');
    }
    
} catch (Exception $e) { // Bisa diganti Throwable untuk PHP 7+ jika ingin menangkap fatal error
    if ($isAjax) {
        // 4. PENTING: Bersihkan buffer agar pesan error HTML tidak tercampur JSON
        ob_end_clean();
        
        http_response_code(400); // Bad Request
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }

    setFlash('error', $e->getMessage());
    $redirectBase = $_POST['redirect'] ?? $_GET['redirect'] ?? 'disposisi_inbox.php';
    $redirectUrl = buildRedirectUrl($redirectBase, ['error' => 'process_failed']);
    header("Location: {$redirectUrl}");
    exit;
}
?>