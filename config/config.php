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
// AUTO-DETECT BASE URL
// ============================================================================
if ($isLocalhost) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['SERVER_NAME'];
    $port = $_SERVER['SERVER_PORT'];
    $portString = '';
    if (($protocol === 'http' && $port != 80) || ($protocol === 'https' && $port != 443)) {
        $portString = ':' . $port;
    }
    
    $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
    if (basename($scriptPath) === 'public') {
        $scriptPath = dirname($scriptPath);
    }
    $basePath = ($scriptPath === '/' || $scriptPath === '\\') ? '' : $scriptPath;
    
    define('BASE_URL', $protocol . '://' . $host . $portString . $basePath . '/public');
} else {
    define('BASE_URL', 'https://' . $_SERVER['SERVER_NAME'] . '/public');
}

// ============================================================================
// PATH UPLOAD FILE
// ============================================================================
define('UPLOAD_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'surat' . DIRECTORY_SEPARATOR);

if ($isLocalhost) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['SERVER_NAME'];
    $port = $_SERVER['SERVER_PORT'];
    $portString = '';
    if (($protocol === 'http' && $port != 80) || ($protocol === 'https' && $port != 443)) {
        $portString = ':' . $port;
    }
    
    $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
    if (basename($scriptPath) === 'public') {
        $scriptPath = dirname($scriptPath);
    }
    $basePath = ($scriptPath === '/' || $scriptPath === '\\') ? '' : $scriptPath;
    
    define('UPLOAD_URL', $protocol . '://' . $host . $portString . $basePath . '/uploads/surat/');
} else {
    define('UPLOAD_URL', 'https://' . $_SERVER['SERVER_NAME'] . '/uploads/surat/');
}

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// ============================================================================
// PATH UPLOAD SETTINGS (Logo, Favicon)
// ============================================================================
define('SETTINGS_UPLOAD_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'settings' . DIRECTORY_SEPARATOR);

if ($isLocalhost) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['SERVER_NAME'];
    $port = $_SERVER['SERVER_PORT'];
    $portString = '';
    if (($protocol === 'http' && $port != 80) || ($protocol === 'https' && $port != 443)) {
        $portString = ':' . $port;
    }
    
    $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
    if (basename($scriptPath) === 'public') {
        $scriptPath = dirname($scriptPath);
    }
    $basePath = ($scriptPath === '/' || $scriptPath === '\\') ? '' : $scriptPath;
    
    define('SETTINGS_UPLOAD_URL', $protocol . '://' . $host . $portString . $basePath . '/uploads/settings/');
} else {
    define('SETTINGS_UPLOAD_URL', 'https://' . $_SERVER['SERVER_NAME'] . '/uploads/settings/');
}

if (!is_dir(SETTINGS_UPLOAD_DIR)) {
    mkdir(SETTINGS_UPLOAD_DIR, 0755, true);
}

// ============================================================================
// KONFIGURASI FILE UPLOAD
// ============================================================================
define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png']);
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes

// ============================================================================
// TIMEZONE
// ============================================================================
date_default_timezone_set('Asia/Makassar');

// ============================================================================
// SESSION CONFIGURATION
// ============================================================================
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
    ini_set('display_startup_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', dirname(__DIR__) . '/logs/error.log');
    
    $logDir = dirname(__DIR__) . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
}

define('DEBUG_MODE', $isLocalhost);

// ============================================================================
// FUNGSI HELPER - GET SETTINGS DINAMIS
// ============================================================================

/**
 * Get setting value dari database dengan cache
 * @param string $key Nama setting
 * @param mixed $default Default value jika tidak ada
 * @return mixed
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
 * Get all settings as array
 * @return array
 */
function getAllSettings() {
    static $settings = null;
    
    if ($settings === null) {
        try {
            require_once __DIR__ . '/database.php';
            $query = "SELECT * FROM settings WHERE id = 1 LIMIT 1";
            $settings = dbSelectOne($query);
            
            if (!$settings) {
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
                'app_description' => 'Aplikasi Pelacakan Surat dan Disposisi',
                'instansi_nama' => 'DINAS KOMUNIKASI DAN INFORMATIKA',
                'ttd_jabatan' => 'Kepala Dinas',
                'ttd_kota' => 'Banjarmasin'
            ];
        }
    }
    
    return $settings;
}

/**
 * Clear settings cache (call after update)
 */
function clearSettingsCache() {
    // Force reload pada next call
    $GLOBALS['_settings_cache_cleared'] = true;
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

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