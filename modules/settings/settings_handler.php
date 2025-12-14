<?php
// modules/settings/settings_handler.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/settings_service.php';

requireLogin();
requireRole('superadmin');

$action = $_POST['action'] ?? '';
$user = getCurrentUser();

try {
    switch ($action) {
        case 'update':
            $currentSettings = SettingsService::getSettings();
            
            if (!$currentSettings) {
                SettingsService::initializeDefaults();
                $currentSettings = SettingsService::getSettings();
            }
            
            // Prepare data
            $data = [
                'app_name' => sanitize($_POST['app_name'] ?? 'Tracking Disposisi'),
                'app_description' => sanitize($_POST['app_description'] ?? ''),
                'app_logo' => $currentSettings['app_logo'],
                'app_favicon' => $currentSettings['app_favicon'],
                'instansi_nama' => sanitize($_POST['instansi_nama'] ?? ''),
                'instansi_alamat' => sanitize($_POST['instansi_alamat'] ?? ''),
                'instansi_telepon' => sanitize($_POST['instansi_telepon'] ?? ''),
                'instansi_email' => sanitize($_POST['instansi_email'] ?? ''),
                'instansi_logo' => $currentSettings['instansi_logo'],
                'ttd_nama_penandatangan' => sanitize($_POST['ttd_nama_penandatangan'] ?? ''),
                'ttd_nip' => sanitize($_POST['ttd_nip'] ?? ''),
                'ttd_jabatan' => sanitize($_POST['ttd_jabatan'] ?? 'Kepala Dinas'),
                'ttd_kota' => sanitize($_POST['ttd_kota'] ?? 'Banjarmasin')
            ];
            
            // Handle app_logo upload
            if (isset($_FILES['app_logo']) && $_FILES['app_logo']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = SettingsService::uploadFile($_FILES['app_logo'], $currentSettings['app_logo']);
                
                if ($uploadResult['success']) {
                    $data['app_logo'] = $uploadResult['filename'];
                } else {
                    throw new Exception($uploadResult['message']);
                }
            }
            
            // Handle app_favicon upload
            if (isset($_FILES['app_favicon']) && $_FILES['app_favicon']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = SettingsService::uploadFile($_FILES['app_favicon'], $currentSettings['app_favicon']);
                
                if ($uploadResult['success']) {
                    $data['app_favicon'] = $uploadResult['filename'];
                } else {
                    throw new Exception($uploadResult['message']);
                }
            }
            
            // Handle instansi_logo upload
            if (isset($_FILES['instansi_logo']) && $_FILES['instansi_logo']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = SettingsService::uploadFile($_FILES['instansi_logo'], $currentSettings['instansi_logo']);
                
                if ($uploadResult['success']) {
                    $data['instansi_logo'] = $uploadResult['filename'];
                } else {
                    throw new Exception($uploadResult['message']);
                }
            }
            
            // Update settings
            SettingsService::update($data);
            
            // Clear settings cache
            clearSettingsCache();
            
            // Log activity
            logActivity($user['id'], 'update_settings', 'Mengupdate pengaturan sistem');
            
            setFlash('success', 'Pengaturan berhasil diperbarui');
            redirect(BASE_URL . '/pengaturan.php?success=updated');
            break;
            
        default:
            throw new Exception('Action tidak valid');
    }
    
} catch (Exception $e) {
    setFlash('error', $e->getMessage());
    redirect(BASE_URL . '/pengaturan.php?error=update_failed');
}