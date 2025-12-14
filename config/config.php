<?php
// config/config.php

// ============================================================================
// KONFIGURASI UMUM APLIKASI
// ============================================================================
define('APP_NAME', 'Tracking Disposisi Surat');
define('APP_VERSION', '1.0.0');

// ============================================================================
// DETEKSI ENVIRONMENT (LOCAL vs PRODUCTION)
// ============================================================================
$isLocalhost = in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1', '::1']);

// ============================================================================
// AUTO-DETECT BASE URL (PERBAIKAN LOGIKA)
// ============================================================================
if ($isLocalhost) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['SERVER_NAME'];
    $port = $_SERVER['SERVER_PORT'];
    $portString = '';
    if (($protocol === 'http' && $port != 80) || ($protocol === 'https' && $port != 443)) {
        $portString = ':' . $port;
    }
    
    // PERBAIKAN: Cari posisi folder '/public' di URL untuk menentukan root path yang valid
    // Ini mencegah error path ganda (seperti /public/laporan/public/) saat diakses dari subfolder
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $publicPos = strpos($scriptName, '/public');
    
    if ($publicPos !== false) {
        // Ambil path sebelum '/public' (misal: /tracking-disposisi)
        $basePath = substr($scriptName, 0, $publicPos);
    } else {
        $basePath = dirname($scriptName);
    }
    
    // Hapus trailing slash jika ada
    $basePath = rtrim($basePath, '/\\');
    
    define('BASE_URL', $protocol . '://' . $host . $portString . $basePath . '/public');
} else {
    // Sesuaikan dengan domain production Anda
    define('BASE_URL', 'https://' . $_SERVER['SERVER_NAME'] . '/public');
}

// ============================================================================
// PATH UPLOAD FILE
// ============================================================================
define('UPLOAD_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'surat' . DIRECTORY_SEPARATOR);

// Tentukan URL Upload
// Menggunakan dirname(BASE_URL) untuk naik satu level dari /public ke root, lalu ke /uploads
define('UPLOAD_URL', dirname(BASE_URL) . '/uploads/surat/');

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// ============================================================================
// PATH UPLOAD SETTINGS
// ============================================================================
define('SETTINGS_UPLOAD_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'settings' . DIRECTORY_SEPARATOR);
define('SETTINGS_UPLOAD_URL', dirname(BASE_URL) . '/uploads/settings/');

if (!is_dir(SETTINGS_UPLOAD_DIR)) {
    mkdir(SETTINGS_UPLOAD_DIR, 0755, true);
}

// ============================================================================
// KONFIGURASI FILE UPLOAD
// ============================================================================
define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png']);
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes

// ============================================================================
// TIMEZONE & SESSION
// ============================================================================
date_default_timezone_set('Asia/Makassar');

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

if (!$isLocalhost) {
    ini_set('session.cookie_secure', 1);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================================
// ERROR REPORTING
// ============================================================================
if ($isLocalhost) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', dirname(__DIR__) . '/logs/error.log');
    
    $logDir = dirname(__DIR__) . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
}

define('DEBUG_MODE', $isLocalhost);

// ============================================================================
// FUNGSI HELPER
// ============================================================================

/**
 * Get setting value dari database dengan cache
 */
function getSetting($key, $default = null) {
    static $settings = null;
    
    if ($settings === null) {
        try {
            require_once __DIR__ . '/database.php';
            $query = "SELECT * FROM settings WHERE id = 1 LIMIT 1";
            $settings = dbSelectOne($query);
            
            if (!$settings) {
                $settings = [];
            }
        } catch (Exception $e) {
            error_log("Error loading settings: " . $e->getMessage());
            $settings = [];
        }
    }
    
    return isset($settings[$key]) && !empty($settings[$key]) ? $settings[$key] : $default;
}

/**
 * Get all settings as array (DIBUTUHKAN OLEH FILE LAPORAN PDF)
 */
function getAllSettings() {
    static $settings = null;
    
    if ($settings === null) {
        try {
            require_once __DIR__ . '/database.php';
            $query = "SELECT * FROM settings WHERE id = 1 LIMIT 1";
            $settings = dbSelectOne($query);
            
            if (!$settings) {
                // Default fallback jika database kosong
                $settings = [
                    'app_name' => APP_NAME,
                    'app_description' => 'Aplikasi Pelacakan Surat dan Disposisi',
                    'app_logo' => null,
                    'app_favicon' => null,
                    'instansi_nama' => 'DINAS KOMUNIKASI DAN INFORMATIKA',
                    'instansi_alamat' => '',
                    'instansi_telepon' => '',
                    'instansi_email' => '',
                    'instansi_logo' => null,
                    'ttd_nama_penandatangan' => '',
                    'ttd_nip' => '',
                    'ttd_jabatan' => 'Kepala Dinas',
                    'ttd_kota' => 'Banjarmasin'
                ];
            }
        } catch (Exception $e) {
            error_log("Error loading all settings: " . $e->getMessage());
            $settings = [
                'app_name' => APP_NAME,
                'instansi_nama' => 'DINAS KOMUNIKASI DAN INFORMATIKA',
            ];
        }
    }
    
    return $settings;
}

/**
 * Clear settings cache (call after update)
 */
function clearSettingsCache() {
    // Force reload pada next call (jika diimplementasikan dengan memcached/redis)
    // Untuk static var PHP request-scoped, ini sebenarnya otomatis reset tiap request baru
    $GLOBALS['_settings_cache_cleared'] = true;
}

function getFullUrl($path = '') {
    $path = ltrim($path, '/');
    return BASE_URL . '/' . $path;
}

function getUploadUrl($filename) {
    return UPLOAD_URL . $filename;
}

function getSettingsUploadUrl($filename) {
    return SETTINGS_UPLOAD_URL . $filename;
}
?>