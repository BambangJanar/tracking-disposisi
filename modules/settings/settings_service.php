<?php
// modules/settings/settings_service.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/config.php';

class SettingsService {
    
    /**
     * Get current settings
     * @return array|null
     */
    public static function getSettings() {
        $query = "SELECT * FROM settings WHERE id = 1 LIMIT 1";
        return dbSelectOne($query);
    }
    
    /**
     * Update settings
     * @param array $data
     * @return array
     */
    public static function update($data) {
        $query = "UPDATE settings SET
                    app_name = ?,
                    app_description = ?,
                    app_logo = ?,
                    app_favicon = ?,
                    instansi_nama = ?,
                    instansi_alamat = ?,
                    instansi_telepon = ?,
                    instansi_email = ?,
                    instansi_logo = ?,
                    ttd_nama_penandatangan = ?,
                    ttd_nip = ?,
                    ttd_jabatan = ?,
                    ttd_kota = ?
                  WHERE id = 1";
        
        $params = [
            $data['app_name'],
            $data['app_description'],
            $data['app_logo'],
            $data['app_favicon'],
            $data['instansi_nama'],
            $data['instansi_alamat'],
            $data['instansi_telepon'],
            $data['instansi_email'],
            $data['instansi_logo'],
            $data['ttd_nama_penandatangan'],
            $data['ttd_nip'],
            $data['ttd_jabatan'],
            $data['ttd_kota']
        ];
        
        $types = 'sssssssssssss';
        
        return dbExecute($query, $params, $types);
    }
    
    /**
     * Upload logo atau favicon
     * @param array $file $_FILES array
     * @param string $oldFilename Nama file lama untuk dihapus
     * @return array ['success' => bool, 'filename' => string, 'message' => string]
     */
    public static function uploadFile($file, $oldFilename = null) {
        if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['success' => true, 'filename' => $oldFilename, 'message' => 'No file uploaded'];
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Error saat upload file'];
        }
        
        // Validasi ukuran (max 2MB untuk logo/favicon)
        $maxSize = 2 * 1024 * 1024; // 2MB
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'Ukuran file maksimal 2MB'];
        }
        
        // Validasi ekstensi
        $allowedExt = ['png', 'jpg', 'jpeg', 'ico', 'svg'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowedExt)) {
            return ['success' => false, 'message' => 'Format file harus: ' . implode(', ', $allowedExt)];
        }
        
        // Buat direktori jika belum ada
        if (!is_dir(SETTINGS_UPLOAD_DIR)) {
            mkdir(SETTINGS_UPLOAD_DIR, 0755, true);
        }
        
        // Generate nama file unik
        $filename = uniqid('setting_') . '_' . time() . '.' . $ext;
        $destination = SETTINGS_UPLOAD_DIR . $filename;
        
        // Hapus file lama jika ada
        if ($oldFilename && file_exists(SETTINGS_UPLOAD_DIR . $oldFilename)) {
            unlink(SETTINGS_UPLOAD_DIR . $oldFilename);
        }
        
        // Upload file baru
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return ['success' => true, 'filename' => $filename, 'message' => 'File berhasil diupload'];
        }
        
        return ['success' => false, 'message' => 'Gagal menyimpan file'];
    }
    
    /**
     * Delete uploaded file
     * @param string $filename
     * @return bool
     */
    public static function deleteFile($filename) {
        if (empty($filename)) {
            return false;
        }
        
        $filepath = SETTINGS_UPLOAD_DIR . $filename;
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        
        return false;
    }
    
    /**
     * Initialize default settings if not exists
     * @return bool
     */
    public static function initializeDefaults() {
        $existing = self::getSettings();
        
        if ($existing) {
            return true;
        }
        
        $query = "INSERT INTO settings (
                    id,
                    app_name,
                    app_description,
                    instansi_nama,
                    ttd_jabatan,
                    ttd_kota
                  ) VALUES (1, ?, ?, ?, ?, ?)";
        
        $params = [
            'Tracking Disposisi',
            'Aplikasi Pelacakan Surat dan Disposisi',
            'DINAS KOMUNIKASI DAN INFORMATIKA',
            'Kepala Dinas',
            'Banjarmasin'
        ];
        
        $types = 'sssss';
        
        try {
            dbExecute($query, $params, $types);
            return true;
        } catch (Exception $e) {
            error_log("Error initializing settings: " . $e->getMessage());
            return false;
        }
    }
}