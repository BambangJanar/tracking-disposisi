<?php
// modules/download/download_handler.php
// Handler untuk proses download/view file dengan pencatatan log

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/download_service.php';

// Pastikan user sudah login
if (!isLoggedIn()) {
    // Jika request AJAX, kembalikan JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Anda harus login terlebih dahulu']);
        exit;
    }
    // Redirect ke login
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$user = getCurrentUser();

try {
    switch ($action) {

        // ===== DOWNLOAD / VIEW FILE =====
        case 'download':
        case 'view':
            $suratId = (int)($_GET['surat_id'] ?? 0);

            if (!$suratId) {
                throw new Exception("ID Surat tidak valid");
            }

            // Ambil data surat
            require_once __DIR__ . '/../surat/surat_service.php';
            $surat = SuratService::getById($suratId);

            if (!$surat) {
                throw new Exception("Surat tidak ditemukan");
            }

            if (empty($surat['lampiran_file'])) {
                throw new Exception("Surat ini tidak memiliki file lampiran");
            }

            // Cek file ada di server
            $filePath = UPLOAD_DIR . $surat['lampiran_file'];

            if (!file_exists($filePath)) {
                throw new Exception("File tidak ditemukan di server");
            }

            // Catat log ke database
            $ipAddress = DownloadService::getClientIp();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            DownloadService::logDownload(
                $user['id'],
                $suratId,
                $surat['lampiran_file'],
                $action,
                $ipAddress,
                $userAgent
            );

            // Log ke tabel aktivitas juga
            $aksiLabel = ($action === 'download') ? 'mengunduh' : 'melihat';
            logActivity(
                $user['id'],
                $action . '_file',
                "User $aksiLabel file lampiran surat: {$surat['nomor_agenda']} ({$surat['lampiran_file']})"
            );

            // Deteksi MIME type
            $ext = strtolower(pathinfo($surat['lampiran_file'], PATHINFO_EXTENSION));
            $mimeTypes = [
                'pdf'  => 'application/pdf',
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png'  => 'image/png',
            ];
            $mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';

            // Set header untuk download atau view
            header('Content-Type: ' . $mimeType);
            header('Content-Length: ' . filesize($filePath));
            header('X-Content-Type-Options: nosniff');

            if ($action === 'download') {
                // Force download
                $downloadName = $surat['nomor_agenda'] . '_' . $surat['lampiran_file'];
                $downloadName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $downloadName);
                header('Content-Disposition: attachment; filename="' . $downloadName . '"');
            } else {
                // View inline (buka di browser)
                header('Content-Disposition: inline; filename="' . $surat['lampiran_file'] . '"');
            }

            // Prevent caching
            header('Cache-Control: private, no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');

            // Stream file
            readfile($filePath);
            exit;

        // ===== AMBIL LOG DOWNLOAD PER SURAT (AJAX) =====
        case 'get_log':
            header('Content-Type: application/json');

            $suratId = (int)($_GET['surat_id'] ?? 0);
            if (!$suratId) {
                throw new Exception("ID Surat tidak valid");
            }

            // Hanya superadmin (1) dan admin/karyawan (2) yang bisa lihat log
            $userRole = $user['id_role'] ?? 3;
            if ($userRole == 3) {
                throw new Exception("Anda tidak memiliki akses untuk melihat log download");
            }

            $logs = DownloadService::getLogBySurat($suratId);

            echo json_encode([
                'status' => 'success',
                'data' => $logs
            ]);
            exit;

        default:
            throw new Exception("Aksi tidak valid");
    }
} catch (Exception $e) {
    // Jika error pada download/view, tampilkan halaman error
    if (in_array($action, ['download', 'view'])) {
        http_response_code(404);
        echo '<!DOCTYPE html><html><head><title>Error</title></head><body style="font-family:sans-serif;text-align:center;padding:50px;">';
        echo '<h2 style="color:#e53e3e;">⚠️ Error</h2>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<a href="javascript:history.back()" style="color:#3182ce;">← Kembali</a>';
        echo '</body></html>';
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}
